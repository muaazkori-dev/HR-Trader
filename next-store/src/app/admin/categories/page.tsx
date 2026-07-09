import React from 'react';
import { supabase } from '@/lib/supabase';
import { 
  Folder, 
  ShoppingBag, 
  Sparkles,
  Layers,
  ArrowRight
} from 'lucide-react';
import Link from 'next/link';

export const revalidate = 0;

const CATEGORIES: Record<string, { label: string; icon: string }> = {
  anaj: { label: 'Grains & Rice (اناج)', icon: '🌾' },
  shampoo: { label: 'Hair Care (شیمپو)', icon: '🧴' },
  soap: { label: 'Soaps & Care (صابن)', icon: '🧼' },
  cold_drinks: { label: 'Beverages (کولڈ ڈرنکس)', icon: '🥤' },
  water: { label: 'Mineral Water (پانی)', icon: '💧' },
  ice_cream: { label: 'Ice Creams (آئس کریم)', icon: '🍦' },
  milk: { label: 'Dairy & Milk (دودھ)', icon: '🥛' },
};

export default async function AdminCategoriesPage() {
  const categoryStats: Record<string, number> = {};

  try {
    // Fetch counts of products grouped by category
    const { data: prodData } = await supabase
      .from('products')
      .select('category');

    if (prodData) {
      prodData.forEach((p) => {
        categoryStats[p.category] = (categoryStats[p.category] || 0) + 1;
      });
    }
  } catch (err) {
    console.error('Error fetching categories stats:', err);
  }

  return (
    <div className="space-y-6 text-left w-full flex-grow flex flex-col">
      {/* Header */}
      <section className="pb-4 border-b border-slate-200">
        <h1 className="text-xl font-black text-slate-800 uppercase tracking-wider">Categories Desk</h1>
        <p className="text-xs text-slate-400 mt-1">Review active category tags and verify product distribution counts.</p>
      </section>

      {/* Grid List */}
      <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {Object.entries(CATEGORIES).map(([key, info]) => {
          const count = categoryStats[key] || 0;
          return (
            <div
              key={key}
              className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4 flex flex-col justify-between hover:border-emerald-250 hover:shadow-md transition-all group"
            >
              <div className="flex items-center justify-between">
                <span className="text-3xl filter drop-shadow-sm select-none">
                  {info.icon}
                </span>
                <span className="text-[10px] bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-full border border-slate-200 font-bold font-mono">
                  {count} Products
                </span>
              </div>

              <div className="space-y-1">
                <h3 className="font-extrabold text-slate-800 text-sm tracking-tight capitalize group-hover:text-emerald-600 transition-colors">
                  {info.label}
                </h3>
                <span className="text-[9px] font-mono text-slate-400 uppercase tracking-widest block">
                  Tag: {key}
                </span>
              </div>

              <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
                <span className="text-[10px] text-slate-400 font-semibold leading-none">
                  Online and Active
                </span>
                <Link
                  href={`/admin/products?category=${key}`}
                  className="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-650 hover:underline"
                >
                  View Inventory <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                </Link>
              </div>
            </div>
          );
        })}
      </section>
    </div>
  );
}
