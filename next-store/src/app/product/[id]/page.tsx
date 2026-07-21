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
  Star
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
                  src={getProductImageUrl(product.image)}
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
                      className="block relative aspect-square rounded-2xl overflow-hidden bg-slate-50 border border-slate-100 mb-3 flex-shrink-0"
                    >
                      <Image
                        src={getProductImageUrl(p.image)}
                        alt={p.name}
                        fill
                        className="object-cover group-hover:scale-105 transition-transform duration-300"
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
