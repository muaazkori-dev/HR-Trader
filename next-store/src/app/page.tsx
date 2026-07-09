import Image from 'next/image';
import React from 'react';
import Link from 'next/link';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { supabase } from '@/lib/supabase';
import { ShoppingCart, Star, Eye, Tag, Sparkles } from 'lucide-react';
import { HomeSlider } from '@/components/HomeSlider';
import { AddToCartButton } from '@/components/AddToCartButton';

// Categories list
const CATEGORIES = [
  { id: 'anaj', name: 'Grains & Rice', icon: '🌾', desc: 'Pulses & Rice' },
  { id: 'shampoo', name: 'Hair Care', icon: '🧴', desc: 'Premium Shampoos' },
  { id: 'soap', name: 'Soaps & Care', icon: '🧼', desc: 'Body Soaps & Bars' },
  { id: 'cold_drinks', name: 'Beverages', icon: '🥤', desc: 'Carbonated Drinks' },
  { id: 'water', name: 'Mineral Water', icon: '💧', desc: 'Pure Drinking Water' },
  { id: 'ice_cream', name: 'Ice Creams', icon: '🍦', desc: 'Desserts & Cups' },
  { id: 'milk', name: 'Dairy & Milk', icon: '🥛', desc: 'UHT Milk packs' },
];

export const revalidate = 30; // Cache on edge CDN for 30 seconds for instant transition

export default async function Home() {
  let products: any[] = [];
  let featuredProducts: any[] = [];

  try {
    const { data } = await supabase
      .from('products')
      .select('*')
      .order('id', { ascending: false });

    if (data) {
      products = data;
      // Featured: products with stock > 0, showing first 8
      featuredProducts = data.slice(0, 8);
    }
  } catch (error) {
    console.error('Error fetching storefront products:', error);
  }

  return (
    <div className="flex flex-col min-h-screen bg-slate-50/50">
      {/* Header */}
      <Header />

      {/* 1. HERO SLIDER CAROUSEL (Full Width with side peek) */}
      <HomeSlider />

      {/* Main Container */}
      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-12">
        
        {/* 2. DYNAMIC HORIZONTAL CATEGORY SCROLL */}
        <section className="space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
              <Sparkles className="w-4 h-4 text-emerald-500" />
              Shop by Category
            </h2>
          </div>
          <div className="flex gap-4 overflow-x-auto pb-4 scrollbar-thin scrollbar-thumb-slate-200">
            {CATEGORIES.map((cat) => (
              <Link
                key={cat.id}
                href={`/shop?category=${cat.id}`}
                className="flex-shrink-0 w-36 p-4 bg-white border border-slate-200 rounded-2xl text-center hover:shadow-md hover:border-emerald-300 transition-all group"
              >
                <span className="text-3xl block group-hover:scale-110 transition-transform duration-200">
                  {cat.icon}
                </span>
                <h3 className="text-xs font-bold text-slate-800 mt-3 truncate">{cat.name}</h3>
                <p className="text-[9px] text-slate-400 mt-1 truncate">{cat.desc}</p>
              </Link>
            ))}
          </div>
        </section>

        {/* 3. PROMOTIONAL DISCOUNTS BANNER */}
        <section className="bg-gradient-to-r from-emerald-600 to-teal-500 rounded-3xl p-6 md:p-8 text-white relative overflow-hidden shadow-lg">
          <div className="absolute top-0 right-0 translate-x-12 -translate-y-6 w-48 h-48 bg-white/10 rounded-full blur-xl" />
          <div className="relative z-10 max-w-lg space-y-4 text-left">
            <span className="bg-white/20 px-3 py-1 rounded-full text-[10px] uppercase tracking-widest font-extrabold">
              Flat Discounts
            </span>
            <h2 className="text-2xl md:text-3xl font-extrabold leading-tight">
              Fresh Organic Groceries & Premium Cosmetics Delivered Today!
            </h2>
            <p className="text-xs text-emerald-50 font-normal leading-relaxed">
              Order premium Basmati rice, hand hygiene soaps, and refreshing beverages online. Pay Cash on Delivery (COD) right at your doorstep in Tando Adam.
            </p>
            <div className="pt-2">
              <Link
                href="/shop"
                className="px-6 py-2.5 bg-white hover:bg-emerald-50 text-emerald-700 text-xs font-extrabold rounded-xl shadow-md transition-all inline-block"
              >
                Explore Offer Catalog
              </Link>
            </div>
          </div>
        </section>

        {/* 4. FEATURED PRODUCTS GRID */}
        <section className="space-y-6">
          <div className="flex items-center justify-between">
            <h2 className="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
              <Tag className="w-4 h-4 text-emerald-500" />
              Featured Grocery Deals
            </h2>
          </div>

          {featuredProducts.length === 0 ? (
            <div className="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
              No products found in catalog. Add some items in the admin panel!
            </div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
              {featuredProducts.map((p) => {
                const discount = p.discount_percentage || 0;
                const oldPrice = p.old_price || 0;
                
                return (
                  <div
                    key={p.id}
                    className="bg-white border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md hover:border-emerald-200 transition-all group relative"
                  >
                    {/* Discount badge */}
                    {discount > 0 && (
                      <span className="absolute top-3 left-3 bg-rose-500 text-white text-[9px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider z-10 shadow-sm animate-pulse">
                        {discount}% OFF
                      </span>
                    )}

                    {/* Image Link */}
                    <Link href={`/product/${p.id}`} className="block relative aspect-square rounded-2xl overflow-hidden bg-slate-50 border border-slate-100 mb-4 flex-shrink-0">
                      <Image
                        src={p.image ? `/${p.image}` : '/assets/images/placeholder.svg'}
                        alt={p.name}
                        fill
                        className="object-cover group-hover:scale-105 transition-transform duration-300"
                        sizes="(max-width: 768px) 50vw, (max-width: 1200px) 33vw, 25vw"
                      />
                    </Link>

                    {/* Info */}
                    <div className="flex-1 flex flex-col justify-between text-left">
                      <div className="space-y-1">
                        <span className="text-[9px] text-emerald-600 font-extrabold uppercase tracking-wider">
                          {p.category.replace('_', ' ')}
                        </span>
                        <Link href={`/product/${p.id}`} className="block font-bold text-slate-800 text-xs sm:text-sm hover:text-emerald-600 transition-colors line-clamp-1">
                          {p.name}
                        </Link>
                        {p.weight && (
                          <span className="text-[10px] text-slate-400 block font-normal">
                            {p.weight} ({p.unit})
                          </span>
                        )}
                      </div>

                      {/* Pricing / Action */}
                      <div className="flex items-center justify-between mt-4">
                        <div className="text-left">
                          <span className="text-xs sm:text-sm font-mono font-extrabold text-slate-900">
                            Rs. {p.price.toFixed(0)}
                          </span>
                          {oldPrice > p.price && (
                            <span className="text-[10px] text-slate-400 line-through font-normal block">
                              Rs. {oldPrice.toFixed(0)}
                            </span>
                          )}
                        </div>

                        {/* Add to Cart client component button */}
                        <AddToCartButton product={{
                          id: p.id,
                          name: p.name,
                          price: p.price,
                          image: p.image,
                          weight: p.weight,
                          unit: p.unit
                        }} />
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          <div className="text-center pt-8">
            <Link
              href="/shop"
              className="inline-flex items-center justify-center px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-2xl shadow-md hover:shadow-lg transition-all uppercase tracking-widest"
            >
              View All Products Catalog
            </Link>
          </div>
        </section>

      </main>

      {/* Footer */}
      <Footer />
    </div>
  );
}

// Trigger Vercel rebuild for layout updates

