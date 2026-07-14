import React from 'react';
import { supabase } from '@/lib/supabase';
import { DashboardTabsContent } from '@/components/admin/DashboardTabsContent';

export const revalidate = 0; // Fresh metrics always

export default async function AdminDashboard() {
  let grossSales = 0;
  let netExpense = 0;
  let netProfit = 0;
  let activeFulfillments = 0;
  
  let posSales = 0;
  let posProfit = 0;
  let onlineSales = 0;
  let onlineProfit = 0;

  let initialProducts: any[] = [];
  let initialSettings: any[] = [];
  let initialStaffList: any[] = [];
  let initialDemands: any[] = [];

  try {
    // 1. Fetch POS transactions stats
    const { data: posData } = await supabase
      .from('sales')
      .select('total_amount, total_profit');

    if (posData) {
      posData.forEach((s) => {
        posSales += parseFloat(s.total_amount);
        posProfit += parseFloat(s.total_profit);
      });
    }

    // 2. Fetch completed online orders total
    let onlineCost = 0;
    const { data: onlineDelivered } = await supabase
      .from('orders')
      .select('id, total_amount')
      .eq('status', 'delivered');

    if (onlineDelivered) {
      onlineSales = onlineDelivered.reduce((sum, ord) => sum + parseFloat(ord.total_amount), 0);
      const onlineDeliveredIds = onlineDelivered.map((o) => o.id);
      
      if (onlineDeliveredIds.length > 0) {
        const { data: items } = await supabase
          .from('order_items')
          .select('price, quantity, products(purchase_price)')
          .in('order_id', onlineDeliveredIds);

        if (items) {
          items.forEach((item: any) => {
            const purchasePrice = item.products?.purchase_price ? parseFloat(item.products.purchase_price) : 0;
            onlineCost += item.quantity * purchasePrice;
          });
        }
      }
    }

    onlineProfit = onlineSales - onlineCost;

    // Sum overall totals
    grossSales = posSales + onlineSales;
    netProfit = posProfit + onlineProfit;
    netExpense = grossSales - netProfit;

    // 3. Count active non-cancelled orders
    const { data: activeOrders } = await supabase
      .from('orders')
      .select('id')
      .neq('status', 'cancelled');
    if (activeOrders) {
      activeFulfillments = activeOrders.length;
    }

    // 4. Fetch all products (sorted)
    const { data: productsData } = await supabase
      .from('products')
      .select('id, barcode, name, description, price, stock_quantity, unit, weight, category, image')
      .order('stock_quantity', { ascending: true });
    if (productsData) {
      initialProducts = productsData;
    }

    // 5. Fetch settings
    const { data: settingsData } = await supabase
      .from('settings')
      .select('key_name, val_value');
    if (settingsData) {
      initialSettings = settingsData;
    }

    // 6. Fetch profiles (owner / manager list)
    const { data: staffData } = await supabase
      .from('profiles')
      .select('id, name, phone, address, role, created_at, username')
      .in('role', ['owner', 'manager'])
      .order('name', { ascending: true });
    if (staffData) {
      initialStaffList = staffData;
    }

    // 7. Fetch demands
    const { data: demandsData } = await supabase
      .from('product_demands')
      .select('id, customer_name, customer_phone, demand_details, status, created_at')
      .order('id', { ascending: false });
    if (demandsData) {
      initialDemands = demandsData;
    }

  } catch (err) {
    console.error('Error fetching dashboard statistics:', err);
  }

  const initialStats = {
    grossSales,
    netExpense,
    netProfit,
    activeFulfillments,
    posSales,
    onlineSales,
    posProfit,
    onlineProfit
  };

  return (
    <div className="space-y-6 text-left w-full flex-grow flex flex-col">
      {/* Page Header */}
      <section className="pb-4 border-b border-slate-200">
        <h1 className="text-xl font-black text-slate-800 uppercase tracking-wider">Dashboard Portal</h1>
        <p className="text-xs text-slate-400 mt-1">Real-time statistics metrics, stock alerts inventory restocker, and storefront custom settings engine.</p>
      </section>

      {/* Tabs Layout Content */}
      <DashboardTabsContent
        initialStats={initialStats}
        initialProducts={initialProducts}
        initialSettings={initialSettings}
        initialStaffList={initialStaffList}
        initialDemands={initialDemands}
      />
    </div>
  );
}
