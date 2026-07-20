import React from 'react';
import { supabase } from '@/lib/supabase';
import { OrdersContent } from '@/components/admin/OrdersContent';

export const revalidate = 0; // Fresh orders data always

export default async function AdminOrdersPage() {
  let orders: any[] = [];

  try {
    // Query orders from DB with preloaded items
    const { data, error } = await supabase
      .from('orders')
      .select('*, order_items(*)')
      .order('id', { ascending: false });

    if (!error && data) {
      orders = data;
    }
  } catch (err) {
    console.error('Error fetching orders logs:', err);
  }

  return (
    <div className="w-full flex-grow flex flex-col">
      <OrdersContent initialOrders={orders} />
    </div>
  );
}
