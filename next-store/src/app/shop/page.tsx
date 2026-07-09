import React, { Suspense } from 'react';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { ShopContent } from '@/components/ShopContent';
import { supabase } from '@/lib/supabase';
import { Sparkles } from 'lucide-react';

export const revalidate = 30; // Cache on edge CDN for 30 seconds for instant transition

export default async function Shop() {
  let products: any[] = [];
  
  try {
    const { data, error } = await supabase
      .from('products')
      .select('*')
      .order('id', { ascending: false });
      
    if (!error && data) {
      products = data;
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
          <ShopContent initialProducts={products} />
        </Suspense>

      </main>

      {/* Global Footer */}
      <Footer />
    </div>
  );
}
