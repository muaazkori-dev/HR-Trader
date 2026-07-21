'use client';

import React, { useState, useEffect } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { supabase } from '@/lib/supabase';
import { ShoppingCart, Star, Eye, Tag, Sparkles, Loader2 } from 'lucide-react';
import { HomeSlider } from '@/components/HomeSlider';
import { AddToCartButton } from '@/components/AddToCartButton';
import { getProductImageUrl } from '@/lib/utils';

// Default Categories list
const DEFAULT_CATEGORIES = [
  { id: 'anaj', name: 'Anaj', urdu: 'اناج', image: '/assets/images/categories/anaj.png' },
  { id: 'grocery', name: 'Grocery', urdu: 'گروسری', image: '/assets/images/categories/grocery.png' },
  { id: 'ice_cream', name: 'Ice Cream', urdu: 'آئس کریم', image: '/assets/images/categories/ice_cream.png' },
  { id: 'beverages', name: 'Beverages', urdu: 'مشروبات', image: '/assets/images/categories/cold_drinks.png' },
  { id: 'milk', name: 'Milk', urdu: 'دودھ', image: '/assets/images/categories/milk.png' },
  { id: 'cosmetics', name: 'Cosmetics', urdu: 'کاسمیٹکس', image: '/assets/images/categories/cosmetics.png' },
  { id: 'confectionary', name: 'Snacks', urdu: 'سنیکس', image: '/assets/images/categories/snacks.png' },
  { id: 'bakery', name: 'Bakery', urdu: 'بیکری', image: '/assets/images/categories/bakery.png' },
  { id: 'sauce', name: 'Sauces', urdu: 'سوس', image: '/assets/images/categories/sauce.png' },
];

// Default Hero Banners list
const DEFAULT_BANNERS = [
  {
    id: 1,
    tag: 'Premium Choice',
    title: 'Your Premium Grocery Partner',
    desc: 'Fresh organic crops, groceries, and premium household brands delivered straight to your home.',
    link: '/shop',
    image: '/assets/images/hero_grocery_banner.png',
    theme: 'emerald'
  },
  {
    id: 2,
    tag: 'Beat The Heat',
    title: 'Quench Your Thirst',
    desc: 'Soft drinks, juices, mineral water bottles, and energy drinks delivered straight to your doorstep ice cold.',
    link: '/shop?category=beverages',
    image: null,
    theme: 'teal'
  },
  {
    id: 3,
    tag: 'Frozen Delights',
    title: 'Frozen Ice Creams',
    desc: 'Family pack ice creams and chicken frozen snacks. *Available for nearby locations to maintain cold chain.',
    link: '/shop?category=ice_cream',
    image: null,
    theme: 'cyan'
  }
];

export default function Home() {
  const [loading, setLoading] = useState(true);
  const [featuredProducts, setFeaturedProducts] = useState<any[]>([]);
  const [categories, setCategories] = useState<any[]>([]);
  const [banners, setBanners] = useState<any[]>([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        // 1. Fetch products sorted by ID descending (Latest Arrivals first)
        const { data: prodData } = await supabase
          .from('products')
          .select('*')
          .order('id', { ascending: false });

        if (prodData) {
          setFeaturedProducts(prodData.slice(0, 8));
        }

        // 2. Fetch categories setting
        const { data: catSetting } = await supabase
          .from('settings')
          .select('val_value')
          .eq('key_name', 'store_categories')
          .maybeSingle();

        if (catSetting?.val_value) {
          try {
            const parsed = JSON.parse(catSetting.val_value);
            if (typeof parsed === 'object' && !Array.isArray(parsed)) {
              setCategories(Object.entries(parsed).map(([id, val]: any) => ({
                id,
                name: val.name,
                urdu: val.urdu || '',
                image: val.image || `/assets/images/categories/${id}.png`
              })));
            } else if (Array.isArray(parsed)) {
              setCategories(parsed.map((cat: any) => ({
                id: cat.id || cat.key,
                name: cat.name,
                urdu: cat.urdu || '',
                image: cat.image || `/assets/images/categories/${cat.id || cat.key}.png`
              })));
            }
          } catch (err) {
            console.error('Error parsing categories:', err);
          }
        }

        // 3. Fetch hero banners setting
        const { data: bannerSetting } = await supabase
          .from('settings')
          .select('val_value')
          .eq('key_name', 'store_hero_banners')
          .maybeSingle();

        if (bannerSetting?.val_value) {
          try {
            setBanners(JSON.parse(bannerSetting.val_value));
          } catch (err) {
            console.error('Error parsing banners:', err);
          }
        }
      } catch (error) {
        console.error('Error fetching storefront configs:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const activeCategories = categories.length > 0 ? categories : DEFAULT_CATEGORIES;
  const activeBanners = banners.length > 0 ? banners : DEFAULT_BANNERS;

  return (
    <div className="flex flex-col min-h-screen bg-slate-50/50">
      {/* Header */}
      <Header />

      {/* 1. HERO SLIDER CAROUSEL (Dynamic Banners from DB) */}
      <HomeSlider banners={activeBanners} />

      {/* Main Container */}
      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-12">
        
        {/* 2. CATEGORIES SECTION */}
        <section className="space-y-6 text-center">
          <div className="space-y-1">
            <h2 className="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
              Browse By Categories
            </h2>
            <p className="text-xs text-slate-500">
              Select a category to filter your grocery requirements
            </p>
          </div>
          <div className="grid grid-cols-3 md:grid-cols-9 gap-3 sm:gap-4 md:gap-6">
            {activeCategories.map((cat) => (
              <Link
                key={cat.id}
                href={`/shop?category=${cat.id}`}
                className="flex flex-col items-center justify-center p-3 sm:p-4 bg-white border border-slate-200/80 rounded-[2rem] text-center hover:shadow-md hover:border-emerald-300 transition-all duration-200 group shadow-sm"
              >
                <div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full overflow-hidden border border-slate-100 bg-slate-50 flex items-center justify-center mb-2 flex-shrink-0 group-hover:scale-105 transition-transform duration-200 shadow-inner">
                  <img 
                    src={cat.image} 
                    alt={cat.name} 
                    className="w-8 h-8 sm:w-12 sm:h-12 object-contain"
                  />
                </div>
                <h3 className="text-[10px] sm:text-xs font-bold text-slate-805 tracking-tight leading-none">
                  {cat.name}
                </h3>
                <span 
                  className="text-[9px] sm:text-[10px] text-slate-400 mt-1 block leading-tight font-medium"
                  style={{ fontFamily: 'var(--font-urdu)' }}
                >
                  {cat.urdu}
                </span>
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
              Featured Grocery Deals (Latest Arrivals)
            </h2>
          </div>

          {loading ? (
            <div className="flex flex-col items-center justify-center p-24 gap-3 bg-white border border-slate-200 rounded-3xl shadow-sm">
              <Loader2 className="w-8 h-8 text-emerald-600 animate-spin" />
              <span className="text-xs text-slate-450 font-semibold">Loading Deals Catalog...</span>
            </div>
          ) : featuredProducts.length === 0 ? (
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
                        src={getProductImageUrl(p.image)}
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

                      {/* Pricing */}
                      <div className="mt-4 flex items-baseline gap-2">
                        <span className="text-sm sm:text-base font-mono font-black text-emerald-600">
                          Rs. {p.price.toFixed(0)}
                        </span>
                        {oldPrice > p.price && (
                          <span className="text-[10px] text-slate-400 line-through font-normal font-mono">
                            Rs. {oldPrice.toFixed(0)}
                          </span>
                        )}
                      </div>

                      {/* Add/Buy Buttons */}
                      <div className="mt-3 w-full">
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
