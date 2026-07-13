import React, { Suspense } from 'react';
import { supabase } from '@/lib/supabase';
import { InventoryContent } from '@/components/admin/InventoryContent';

export const revalidate = 0; // Fresh inventory details always

export default async function AdminProductsPage() {
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
    console.error('Error fetching inventory catalog:', err);
  }

  return (
    <div className="w-full flex-grow flex flex-col">
      <Suspense fallback={<div className="p-8 text-center text-xs text-slate-400 font-semibold">Loading Catalog Desk...</div>}>
        <InventoryContent initialProducts={products} />
      </Suspense>
    </div>
  );
}
