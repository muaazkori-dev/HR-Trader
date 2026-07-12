import React, { Suspense } from 'react';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { ShopContent } from '@/components/ShopContent';
import { supabase } from '@/lib/supabase';
import { Sparkles } from 'lucide-react';

export const revalidate = 0; // Disable static cache to fetch fresh dynamic configurations from Supabase on every load

export default async function Shop() {
  let products: any[] = [];
  let categories: any[] = [];
  
  try {
    const { data: prodData, error } = await supabase
      .from('products')
      .select('*')
      .order('id', { ascending: false });
      
    if (!error && prodData) {
      products = prodData;
    }

    const { data: catSetting } = await supabase
      .from('settings')
      .select('val_value')
      .eq('key_name', 'store_categories')
      .maybeSingle();

    if (catSetting?.val_value) {
      try {
        const parsed = JSON.parse(catSetting.val_value);
        if (typeof parsed === 'object' && !Array.isArray(parsed)) {
          categories = Object.entries(parsed).map(([id, val]: any) => ({
            id,
            name: val.name,
            urdu: val.urdu || '',
            image: val.image || `/assets/images/categories/${id}.png`
          }));
        } else if (Array.isArray(parsed)) {
          categories = parsed.map((cat: any) => ({
            id: cat.id || cat.key,
            name: cat.name,
            urdu: cat.urdu || '',
            image: cat.image || `/assets/images/categories/${cat.id || cat.key}.png`
          }));
        }
      } catch (err) {
        console.error('Error parsing categories:', err);
      }
    }
  } catch (err) {
    console.error('Error fetching shop products:', err);
  }

  return (
    <div className="flex flex-col min-h-screen bg-slate-50/50">
      {/* Global Header */}
      <Header />

      {/* Main shop layout */}
      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-8">
        
        {/* Banner Title */}
        <section className="text-left py-2 border-b border-slate-200">
          <h2 className="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
            <Sparkles className="w-4 h-4 text-emerald-500" />
            Catalog Directory
          </h2>
          <p className="text-xs text-slate-400 mt-1">Browse our complete register of high-grade grains, fresh beverages, and skin care essentials.</p>
        </section>

        {/* Suspense wrapper for SearchParams client component */}
        <Suspense fallback={
          <div className="flex items-center justify-center p-24 text-slate-400 font-semibold text-xs">
            Loading Catalog Items...
          </div>
        }>
          <ShopContent initialProducts={products} categories={categories} />
        </Suspense>

      </main>

      {/* Global Footer */}
      <Footer />
    </div>
  );
}
