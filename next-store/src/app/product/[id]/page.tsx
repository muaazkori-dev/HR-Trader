import React from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { notFound } from 'next/navigation';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { ProductReviews } from '@/components/ProductReviews';
import { AddToCartButton } from '@/components/AddToCartButton';
import { supabase } from '@/lib/supabase';
import { getProductImageUrl } from '@/lib/utils';
import { 
  ArrowLeft, 
  ShoppingBag, 
  ShieldCheck, 
  Truck, 
  Sparkles,
  AlertTriangle,
  Star,
  Layers,
  Link2
} from 'lucide-react';

interface ProductPageProps {
  params: Promise<{
    id: string;
  }>;
}

export const revalidate = 30; // Revalidate cache in the background every 30 seconds

export async function generateStaticParams() {
  try {
    const { data: products } = await supabase
      .from('products')
      .select('id');
    if (!products) return [];
    return products.map((p) => ({
      id: p.id.toString(),
    }));
  } catch (error) {
    console.error('Error in generateStaticParams:', error);
    return [];
  }
}

export default async function ProductDetails({ params }: ProductPageProps) {
  const resolvedParams = await params;
  const productId = parseInt(resolvedParams.id, 10);

  if (isNaN(productId)) {
    return notFound();
  }

  // 1. Fetch Product details
  const { data: product, error: prodError } = await supabase
    .from('products')
    .select('*')
    .eq('id', productId)
    .single();

  if (prodError || !product) {
    return notFound();
  }

  // 2. Fetch reviews
  const { data: reviews } = await supabase
    .from('reviews')
    .select('*')
    .eq('product_id', productId)
    .order('id', { ascending: false });

  const productReviews = reviews || [];

  // Calculate review statistics
  const totalReviews = productReviews.length;
  let averageRating = 0.0;
  if (totalReviews > 0) {
    const sum = productReviews.reduce((acc, rev) => acc + rev.rating, 0);
    averageRating = parseFloat((sum / totalReviews).toFixed(1));
  }

  // 3. Fetch related products (same category, excluding current product)
  const { data: related } = await supabase
    .from('products')
    .select('*')
    .eq('category', product.category)
    .neq('id', productId)
    .limit(4);

  const relatedProducts = related || [];

  // 4. Fetch linked product variant groups (Flavours & Sizes)
  let variantProducts: any[] = [];
  try {
    const { data: variantSettings } = await supabase
      .from('settings')
      .select('val_value')
      .eq('key_name', 'product_variant_groups')
      .maybeSingle();

    if (variantSettings?.val_value) {
      const parsed = JSON.parse(variantSettings.val_value);
      const groupsList: any[] = Array.isArray(parsed) ? parsed : Object.values(parsed || {});
      const matchedGroup = groupsList.find((g: any) => Array.isArray(g.product_ids) && g.product_ids.includes(productId));

      if (matchedGroup && matchedGroup.product_ids.length > 1) {
        const { data: vProds } = await supabase
          .from('products')
          .select('*')
          .in('id', matchedGroup.product_ids)
          .order('price', { ascending: true });

        if (vProds && vProds.length > 1) {
          variantProducts = vProds;
        }
      }
    }
  } catch (err) {
    console.error('Error fetching variant groups:', err);
  }

  // Smart fallback matching if not manually grouped yet
  if (variantProducts.length === 0) {
    const cleaned = (product.name || '')
      .replace(/\b\d+(\.\d+)?\s*(ml|ltr|litre|liter|kg|gm|g|gram|grams|pcs|pc|pack|pouch|tin|can|box|bottle|tablet|tablets|sachet|sachets)\b/gi, '')
      .replace(/[\(\)\[\]\-–\/\\,\.]/g, ' ')
      .trim();
    const words = cleaned.split(/\s+/).filter((w: string) => w.length > 2);
    const brandStem = words[0];

    if (brandStem) {
      const { data: autoMatched } = await supabase
        .from('products')
        .select('*')
        .ilike('name', `%${brandStem}%`)
        .eq('category', product.category)
        .limit(8);

      if (autoMatched && autoMatched.length > 1) {
        variantProducts = autoMatched;
      }
    }
  }

  const isFrozen = product.category === 'ice_cream';
  const isOutOfStock = product.stock_quantity <= 0;

  return (
    <div className="flex flex-col min-h-screen bg-slate-50/50">
      {/* Global Header */}
      <Header />

      {/* Main product view wrapper */}
      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-8">
        
        {/* Breadcrumb / Back Navigation */}
        <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
          <nav className="flex text-[10px] font-extrabold text-slate-400 gap-1.5 uppercase tracking-wider">
            <Link href="/" className="hover:text-emerald-650 transition-colors">Home</Link>
            <span>/</span>
            <Link href="/shop" className="hover:text-emerald-650 transition-colors">Shop</Link>
            <span>/</span>
            <span className="text-slate-700 truncate max-w-[150px]">{product.name}</span>
          </nav>
          
          <Link
            href="/shop"
            className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 hover:text-emerald-600 transition-colors border border-slate-200 bg-white px-3 py-1.5 rounded-xl shadow-sm"
          >
            <ArrowLeft className="w-4 h-4" /> Back to Shop
          </Link>
        </div>

        {/* Dynamic Warning Alert for Frozen Foods */}
        {isFrozen && (
          <div className="frozen-alert-border bg-rose-50 border border-rose-200 rounded-3xl p-5 flex items-center gap-4 text-left">
            <div className="w-12 h-12 rounded-full bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-600 flex-shrink-0">
              <AlertTriangle className="w-6 h-6" />
            </div>
            <div className="space-y-1">
              <h3 className="font-extrabold text-rose-700 text-xs uppercase tracking-wider">Available For Nearby Locations Only</h3>
              <p className="text-xs text-rose-600 leading-normal">
                To maintain standard food safety and cold-chain temperature, frozen products are delivered to surrounding shop zones only.
              </p>
              <p className="urdu-text text-sm text-rose-700 font-extrabold mt-1 tracking-wide">
                یہ فروزن پروڈکٹس صرف قریبی علاقوں میں ہوم ڈیلیوری کے لئے دستیاب ہیں۔
              </p>
            </div>
          </div>
        )}

        {/* Product Details Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* Left Column: Image wrapper */}
          <div className="lg:col-span-5">
            <div className="p-6 rounded-3xl border border-slate-200 bg-white shadow-sm flex items-center justify-center relative min-h-[300px] sm:min-h-[400px] w-full">
              <div className="relative w-full h-[280px] sm:h-[360px]">
                <Image
                  src={getProductImageUrl(product.image, { width: 700, quality: 85 })}
                  alt={product.name}
                  fill
                  className="object-contain rounded-2xl drop-shadow-md hover:scale-105 transition-transform duration-300"
                  sizes="(max-width: 768px) 100vw, 50vw"
                  priority
                />
              </div>
              <span className="absolute top-4 left-4 px-3 py-1 rounded-xl text-[9px] uppercase font-extrabold bg-white/90 backdrop-blur-sm border border-slate-250 text-slate-500 shadow-sm">
                {product.category.replace('_', ' ')}
              </span>

              {isOutOfStock ? (
                <span className="absolute top-4 right-4 px-3 py-1 rounded-xl text-[9px] uppercase font-extrabold bg-rose-600 text-white shadow-sm">
                  Sold Out
                </span>
              ) : (
                <span className="absolute top-4 right-4 px-3 py-1 rounded-xl text-[9px] uppercase font-extrabold bg-emerald-50/90 backdrop-blur-sm border border-emerald-250 text-emerald-700 shadow-sm">
                  In Stock ({product.stock_quantity})
                </span>
              )}
            </div>
          </div>

          {/* Right Column: Title, pricing details, and reviews summary */}
          <div className="lg:col-span-7 space-y-6">
            <div className="p-6 sm:p-8 rounded-3xl border border-slate-200 bg-white shadow-sm text-left space-y-4">
              
              <div className="space-y-2">
                <h1 className="text-xl sm:text-2xl font-black text-slate-800 leading-tight">
                  {product.name}
                </h1>
                
                {/* Rating score */}
                <div className="flex items-center gap-2">
                  <div className="flex text-amber-400 gap-0.5">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <Star
                        key={i}
                        className={`w-4 h-4 ${
                          i < Math.round(averageRating) ? 'fill-amber-400 text-amber-400' : 'text-slate-200'
                        }`}
                      />
                    ))}
                  </div>
                  {totalReviews > 0 ? (
                    <span className="text-xs font-bold text-slate-600">
                      {averageRating} / 5.0 ({totalReviews} Reviews)
                    </span>
                  ) : (
                    <span className="text-[10px] text-slate-400 font-semibold uppercase tracking-wide">
                      No Reviews Yet
                    </span>
                  )}
                </div>
              </div>

              {/* Pricing section */}
              <div className="flex items-baseline gap-4 py-2 border-y border-slate-100">
                <span className="text-2xl font-mono font-black text-slate-900">
                  Rs. {product.price.toFixed(0)}
                </span>
                {product.old_price > product.price && (
                  <span className="text-sm text-slate-400 line-through font-normal font-mono">
                    Rs. {product.old_price.toFixed(0)}
                  </span>
                )}
                {product.discount_percentage > 0 && (
                  <span className="px-2 py-0.5 bg-rose-50 text-rose-600 border border-rose-150 text-[9px] font-extrabold rounded-full uppercase tracking-wider animate-pulse">
                    Save {product.discount_percentage}%
                  </span>
                )}
              </div>

              {/* Product specifications metadata */}
              <div className="grid grid-cols-2 gap-4 text-xs">
                <div className="space-y-1">
                  <span className="text-[9px] font-bold text-slate-405 uppercase tracking-wider block">Unit Weight</span>
                  <strong className="text-slate-800 font-bold block">
                    {product.weight ? `${product.weight} (${product.unit})` : product.unit}
                  </strong>
                </div>
                <div className="space-y-1">
                  <span className="text-[9px] font-bold text-slate-405 uppercase tracking-wider block">Barcode Reference</span>
                  <strong className="text-slate-800 font-mono block">
                    {product.barcode ? product.barcode : 'None'}
                  </strong>
                </div>
              </div>

              {/* Available Flavours & Sizes Selector Chips */}
              {variantProducts.length > 1 && (
                <div className="space-y-2 pt-3 border-t border-slate-100 text-left">
                  <div className="flex items-center justify-between">
                    <span className="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                      <Layers className="w-3.5 h-3.5 text-emerald-600" />
                      Available Flavours & Sizes / فلیورز اور سائز ({variantProducts.length})
                    </span>
                    <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                      Tap to switch
                    </span>
                  </div>

                  <div className="flex flex-wrap gap-2 pt-0.5">
                    {variantProducts.map((v) => {
                      const isCurrent = v.id === productId;
                      return (
                        <Link
                          key={v.id}
                          href={`/product/${v.id}`}
                          className={`group flex items-center gap-2.5 p-1.5 pr-3 rounded-2xl border transition-all text-xs ${
                            isCurrent
                              ? 'bg-emerald-50/80 border-emerald-500 ring-2 ring-emerald-500/20 shadow-xs'
                              : 'bg-white border-slate-200 hover:border-emerald-400 hover:bg-slate-50 text-slate-700 shadow-2xs'
                          }`}
                        >
                          <div className="relative w-8 h-8 rounded-xl bg-white border border-slate-200/80 p-0.5 flex-shrink-0 flex items-center justify-center overflow-hidden">
                            <Image
                              src={getProductImageUrl(v.image, { width: 100, quality: 75 })}
                              alt={v.name}
                              width={32}
                              height={32}
                              className="object-contain w-full h-full"
                            />
                          </div>
                          <div className="flex flex-col text-left leading-tight">
                            <span className={`text-[11px] font-bold truncate max-w-[130px] sm:max-w-[170px] ${
                              isCurrent ? 'text-emerald-900 font-extrabold' : 'text-slate-800'
                            }`}>
                              {v.weight ? `${v.weight} (${v.name.split(' ').slice(1, 3).join(' ') || v.unit})` : v.name}
                            </span>
                            <div className="flex items-center gap-1.5 mt-0.5">
                              <span className="text-[10px] font-black font-mono text-emerald-600">
                                Rs. {v.price}
                              </span>
                              {isCurrent && (
                                <span className="text-[8px] font-extrabold text-emerald-700 bg-emerald-100 px-1 rounded uppercase">
                                  Selected
                                </span>
                              )}
                            </div>
                          </div>
                        </Link>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Add to Basket Action */}
              <div className="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center gap-3">
                <div className="w-full sm:flex-1">
                  <AddToCartButton product={{
                    id: product.id,
                    name: product.name,
                    price: product.price,
                    image: product.image,
                    weight: product.weight,
                    unit: product.unit
                  }} />
                </div>
              </div>

              {/* Description summary */}
              {product.description && (
                <div className="pt-4 border-t border-slate-100 space-y-1.5">
                  <span className="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider block">Description Details</span>
                  <p className="text-xs text-slate-600 leading-relaxed font-normal whitespace-pre-line">
                    {product.description}
                  </p>
                </div>
              )}

              {/* Trust assurances */}
              <div className="pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 text-[10px] text-slate-400">
                <div className="flex items-center gap-2">
                  <ShieldCheck className="w-4 h-4 text-emerald-500" />
                  <span>100% Quality Assured</span>
                </div>
                <div className="flex items-center gap-2">
                  <Truck className="w-4 h-4 text-emerald-500" />
                  <span>Cash on Delivery (COD)</span>
                </div>
              </div>

            </div>

            {/* Dynamic Interactive reviews client block */}
            <div className="p-6 sm:p-8 rounded-3xl border border-slate-200 bg-white shadow-sm">
              <ProductReviews productId={product.id} initialReviews={productReviews} />
            </div>

          </div>

        </div>

        {/* Dedicated Flavours & Sizes Showcase Grid */}
        {variantProducts.length > 1 && (
          <section className="space-y-4 pt-6 border-t border-slate-200 text-left">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div>
                <h2 className="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">
                  <Layers className="w-5 h-5 text-emerald-600" />
                  All Flavours & Variations of this Product
                </h2>
                <p className="text-xs text-slate-500 font-medium urdu-text mt-0.5">
                  اس پروڈکٹ کے تمام سائز اور فلیورز — جو چاہیں براہ راست کارٹ میں ایڈ کریں:
                </p>
              </div>
              <span className="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-xl">
                {variantProducts.length} Options Available
              </span>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              {variantProducts.map((v) => {
                const isCurrent = v.id === productId;
                const isOutOfStock = v.stock_quantity <= 0;
                return (
                  <div
                    key={v.id}
                    className={`bg-white rounded-3xl border p-4 flex flex-col justify-between transition-all relative ${
                      isCurrent
                        ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
                        : 'border-slate-200 hover:border-emerald-300 hover:shadow-md'
                    }`}
                  >
                    {isCurrent && (
                      <span className="absolute top-3 left-3 bg-emerald-600 text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider shadow-sm z-10">
                        Currently Selected
                      </span>
                    )}

                    <Link href={`/product/${v.id}`} className="block space-y-3 group">
                      <div className="relative w-full h-32 sm:h-36 bg-slate-50/50 rounded-2xl flex items-center justify-center p-2 border border-slate-100 group-hover:scale-102 transition-transform">
                        <Image
                          src={getProductImageUrl(v.image, { width: 300, quality: 80 })}
                          alt={v.name}
                          width={140}
                          height={140}
                          className="object-contain max-h-full max-w-full drop-shadow-sm"
                        />
                      </div>
                      
                      <div className="space-y-1 text-left">
                        <h3 className="text-xs font-extrabold text-slate-800 line-clamp-2 leading-tight group-hover:text-emerald-700 transition-colors">
                          {v.name}
                        </h3>
                        <p className="text-[10px] text-slate-500 font-semibold">
                          {v.weight ? `${v.weight} (${v.unit})` : v.unit}
                        </p>
                      </div>
                    </Link>

                    <div className="pt-3 border-t border-slate-100 mt-3 space-y-2.5">
                      <div className="flex items-baseline justify-between">
                        <span className="text-sm font-mono font-black text-slate-900">
                          Rs. {v.price}
                        </span>
                        {v.old_price > v.price && (
                          <span className="text-[10px] text-slate-400 line-through font-mono">
                            Rs. {v.old_price}
                          </span>
                        )}
                      </div>

                      {isOutOfStock ? (
                        <div className="w-full py-2 bg-slate-100 text-slate-400 font-bold text-center text-xs rounded-xl">
                          Sold Out
                        </div>
                      ) : (
                        <AddToCartButton product={{
                          id: v.id,
                          name: v.name,
                          price: v.price,
                          image: v.image,
                          weight: v.weight,
                          unit: v.unit
                        }} />
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </section>
        )}

        {/* Related Products Section */}
        {relatedProducts.length > 0 && (
          <section className="space-y-6 pt-6 border-t border-slate-200">
            <h2 className="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 text-left">
              <Sparkles className="w-4 h-4 text-emerald-500" />
              Related Recommendations
            </h2>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
              {relatedProducts.map((p) => {
                const discount = p.discount_percentage || 0;
                return (
                  <div
                    key={p.id}
                    className="bg-white border border-slate-200 rounded-3xl p-3 flex flex-col justify-between hover:shadow-md hover:border-emerald-250 transition-all group relative"
                  >
                    {discount > 0 && (
                      <span className="absolute top-2 left-2 bg-rose-500 text-white text-[8px] font-extrabold px-1.5 py-0.5 rounded-full z-10 shadow-sm">
                        {discount}%
                      </span>
                    )}
                    <Link
                      href={`/product/${p.id}`}
                      className="block relative aspect-square rounded-2xl overflow-hidden bg-white border border-slate-100 mb-3 flex-shrink-0 p-2 flex items-center justify-center"
                    >
                      <Image
                        src={getProductImageUrl(p.image, { width: 400, quality: 80, resize: 'contain' })}
                        alt={p.name}
                        fill
                        className="object-contain p-2 group-hover:scale-105 transition-transform duration-300"
                        sizes="(max-width: 768px) 50vw, 25vw"
                      />
                    </Link>
                    <div className="text-left space-y-0.5">
                      <Link
                        href={`/product/${p.id}`}
                        className="block font-bold text-slate-800 text-xs hover:text-emerald-600 transition-colors line-clamp-1"
                      >
                        {p.name}
                      </Link>
                      <span className="text-[10px] font-mono font-bold text-slate-700 block">
                        Rs. {p.price.toFixed(0)}
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>
          </section>
        )}

      </main>

      {/* Global Footer */}
      <Footer />
    </div>
  );
}
