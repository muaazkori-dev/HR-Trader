import React from 'react';
import { supabase } from '@/lib/supabase';
import { 
  TrendingUp, 
  ShoppingBag, 
  ClipboardList, 
  AlertTriangle,
  ArrowRight,
  TrendingDown
} from 'lucide-react';
import Link from 'next/link';

export const revalidate = 0; // Fresh metrics always

export default async function AdminDashboard() {
  let totalSales = 0;
  let totalOrdersCount = 0;
  let catalogCount = 0;
  let lowStockProducts: any[] = [];
  let recentOrders: any[] = [];

  try {
    // 1. Fetch completed orders stats
    const { data: orders } = await supabase
      .from('orders')
      .select('total_amount, status')
      .neq('status', 'cancelled');

    if (orders) {
      totalOrdersCount = orders.length;
      totalSales = orders.reduce((sum, ord) => sum + parseFloat(ord.total_amount), 0);
    }

    // 2. Fetch catalog items count
    const { count } = await supabase
      .from('products')
      .select('*', { count: 'exact', head: true });
    
    if (count !== null) catalogCount = count;

    // 3. Fetch products with low stock (<= 5 items)
    const { data: lowStock } = await supabase
      .from('products')
      .select('id, name, stock_quantity, unit, weight, category')
      .lte('stock_quantity', 5)
      .order('stock_quantity', { ascending: true })
      .limit(6);

    if (lowStock) lowStockProducts = lowStock;

    // 4. Fetch recent 5 orders for activity logs
    const { data: recent } = await supabase
      .from('orders')
      .select('id, customer_name, total_amount, status, created_at')
      .order('id', { ascending: false })
      .limit(5);

    if (recent) recentOrders = recent;

  } catch (err) {
    console.error('Error fetching admin dashboard statistics:', err);
  }

  const statCards = [
    {
      label: 'Gross Sales Revenue',
      value: `Rs. ${totalSales.toFixed(0)}`,
      desc: 'Sum of all active online orders',
      icon: TrendingUp,
      color: 'text-emerald-600 bg-emerald-50 border-emerald-100',
    },
    {
      label: 'Fulfillment Orders',
      value: totalOrdersCount.toString(),
      desc: 'Active customer billing cycles',
      icon: ClipboardList,
      color: 'text-blue-600 bg-blue-50 border-blue-100',
    },
    {
      label: 'Catalog Registry',
      value: catalogCount.toString(),
      desc: 'Active products in local store',
      icon: ShoppingBag,
      color: 'text-indigo-600 bg-indigo-50 border-indigo-100',
    },
    {
      label: 'Low Stock Warnings',
      value: lowStockProducts.length.toString(),
      desc: 'Items with quantity <= 5 units',
      icon: AlertTriangle,
      color: lowStockProducts.length > 0 
        ? 'text-rose-600 bg-rose-50 border-rose-100 animate-pulse' 
        : 'text-slate-500 bg-slate-50 border-slate-100',
    },
  ];

  const getStatusClass = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-amber-50 text-amber-700 border-amber-200';
      case 'packaging': return 'bg-blue-50 text-blue-705 border-blue-200';
      case 'out_for_delivery': return 'bg-purple-50 text-purple-750 border-purple-200';
      case 'delivered': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'cancelled': return 'bg-rose-50 text-rose-700 border-rose-200';
      default: return 'bg-slate-50 text-slate-700 border-slate-200';
    }
  };

  return (
    <div className="space-y-6 text-left w-full flex-grow flex flex-col">
      {/* Page Header */}
      <section className="pb-4 border-b border-slate-200">
        <h1 className="text-xl font-black text-slate-800 uppercase tracking-wider">Dashboard Overview</h1>
        <p className="text-xs text-slate-400 mt-1">Real-time metrics, low stock alerts, and recent storefront orders activity.</p>
      </section>

      {/* Grid Stats */}
      <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {statCards.map((card, i) => {
          const Icon = card.icon;
          return (
            <div
              key={i}
              className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4 flex flex-col justify-between"
            >
              <div className="flex items-center justify-between">
                <span className="text-[10px] text-slate-450 uppercase font-extrabold tracking-wider">
                  {card.label}
                </span>
                <div className={`w-8 h-8 rounded-xl border flex items-center justify-center ${card.color}`}>
                  <Icon className="w-4.5 h-4.5" />
                </div>
              </div>
              <div className="space-y-1">
                <h3 className="text-xl font-black text-slate-850 font-mono tracking-tight">{card.value}</h3>
                <p className="text-[10px] text-slate-400 font-normal leading-normal">{card.desc}</p>
              </div>
            </div>
          );
        })}
      </section>

      {/* Main Grid split */}
      <section className="grid grid-cols-1 lg:grid-cols-2 gap-6 flex-1 items-start">
        
        {/* Recent Orders logs */}
        <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4 flex flex-col h-full">
          <div className="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 className="font-extrabold text-slate-800 text-xs uppercase tracking-wider">
              Recent Activity Logs
            </h3>
            <Link href="/admin/orders" className="text-[10px] font-bold text-emerald-600 hover:underline flex items-center gap-1">
              View All <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>

          <div className="divide-y divide-slate-100 flex-grow">
            {recentOrders.length === 0 ? (
              <p className="text-xs text-slate-400 text-center py-16">No recent orders recorded.</p>
            ) : (
              recentOrders.map((ord) => (
                <div key={ord.id} className="py-3 flex items-center justify-between gap-4 first:pt-0 last:pb-0 text-xs">
                  <div className="min-w-0 text-left">
                    <strong className="text-slate-800 font-bold block truncate">{ord.customer_name}</strong>
                    <span className="text-[10px] text-slate-400">
                      #HRT-{String(ord.id).padStart(5, '0')} • {new Date(ord.created_at).toLocaleDateString('en-US')}
                    </span>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="font-mono font-extrabold text-slate-700">Rs. {parseFloat(ord.total_amount).toFixed(0)}</span>
                    <span className={`px-2 py-0.5 rounded-full text-[9px] uppercase font-extrabold border ${getStatusClass(ord.status)}`}>
                      {ord.status}
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Low Stock Warning List */}
        <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4 flex flex-col h-full">
          <div className="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 className="font-extrabold text-slate-800 text-xs uppercase tracking-wider text-left">
              Low Stock Warnings
            </h3>
            <Link href="/admin/products" className="text-[10px] font-bold text-emerald-600 hover:underline flex items-center gap-1">
              Refill Stock <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>

          <div className="divide-y divide-slate-100 flex-grow">
            {lowStockProducts.length === 0 ? (
              <div className="text-center py-16 text-xs text-slate-400 space-y-1">
                <p>🎉 All products are fully stocked!</p>
                <p className="text-[10px]">No items fall below the threshold level of 5.</p>
              </div>
            ) : (
              lowStockProducts.map((p) => (
                <div key={p.id} className="py-3 flex items-center justify-between gap-4 first:pt-0 last:pb-0 text-xs">
                  <div className="min-w-0 text-left">
                    <strong className="text-slate-800 font-bold block truncate">{p.name}</strong>
                    <span className="text-[10px] text-slate-400 capitalize">
                      {p.category.replace('_', ' ')} {p.weight ? `• ${p.weight}` : ''}
                    </span>
                  </div>
                  <span className={`px-2.5 py-0.5 rounded font-bold font-mono text-[10px] border ${
                    p.stock_quantity === 0 
                      ? 'bg-rose-50 text-rose-705 border-rose-200' 
                      : 'bg-amber-50 text-amber-705 border-amber-250 animate-pulse'
                  }`}>
                    {p.stock_quantity} left
                  </span>
                </div>
              ))
            )}
          </div>
        </div>

      </section>
    </div>
  );
}
