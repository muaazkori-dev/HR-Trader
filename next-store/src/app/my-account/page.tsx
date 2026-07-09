'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { 
  ClipboardList, 
  MapPin, 
  Phone, 
  Mail, 
  User, 
  Clock, 
  ChevronDown, 
  ChevronUp, 
  Truck, 
  AlertCircle
} from 'lucide-react';

interface OrderItem {
  id: number;
  product_name: string;
  price: number;
  quantity: number;
}

interface Order {
  id: number;
  created_at: string;
  total_amount: number;
  status: 'pending' | 'packaging' | 'out_for_delivery' | 'delivered' | 'cancelled';
  payment_method: string;
  customer_address: string;
  customer_phone: string;
  notes?: string;
  order_items?: OrderItem[];
}

export default function MyAccount() {
  const router = useRouter();
  const { user, profile, loading } = useAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loadingOrders, setLoadingOrders] = useState(true);
  const [expandedOrderId, setExpandedOrderId] = useState<number | null>(null);

  // 1. Redirect if not logged in and auth finished loading
  useEffect(() => {
    if (!loading && !user) {
      router.replace('/login');
    }
  }, [user, loading, router]);

  // 2. Fetch user orders
  useEffect(() => {
    if (user) {
      const fetchUserOrders = async () => {
        try {
          setLoadingOrders(true);
          // Query orders
          const { data: ordersData, error: ordersErr } = await supabase
            .from('orders')
            .select('*')
            .eq('customer_id', user.id)
            .order('id', { ascending: false });

          if (ordersErr) throw ordersErr;

          if (ordersData) {
            // For each order, fetch items
            const compiledOrders: Order[] = [];
            for (const order of ordersData) {
              const { data: itemsData } = await supabase
                .from('order_items')
                .select('*')
                .eq('order_id', order.id);

              compiledOrders.push({
                ...order,
                order_items: itemsData || [],
              });
            }
            setOrders(compiledOrders);
          }
        } catch (err) {
          console.error('Error fetching orders:', err);
        } finally {
          setLoadingOrders(false);
        }
      };

      fetchUserOrders();
    }
  }, [user]);

  const toggleExpandOrder = (id: number) => {
    setExpandedOrderId(expandedOrderId === id ? null : id);
  };

  const getStatusClass = (status: Order['status']) => {
    switch (status) {
      case 'pending':
        return 'bg-amber-50 text-amber-700 border-amber-200';
      case 'packaging':
        return 'bg-blue-50 text-blue-700 border-blue-200';
      case 'out_for_delivery':
        return 'bg-purple-50 text-purple-700 border-purple-200 animate-pulse';
      case 'delivered':
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'cancelled':
        return 'bg-rose-50 text-rose-705 border-rose-200';
      default:
        return 'bg-slate-50 text-slate-700 border-slate-200';
    }
  };

  if (loading || (!user && loadingOrders)) {
    return (
      <div className="flex flex-col min-h-screen bg-slate-50/50">
        <Header />
        <div className="flex-grow flex items-center justify-center p-24 text-slate-400 font-semibold text-xs">
          Loading User Session...
        </div>
        <Footer />
      </div>
    );
  }

  return (
    <div className="flex flex-col min-h-screen bg-slate-50/50">
      <Header />

      <main className="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-8">
        
        {/* Title */}
        <section className="text-left border-b border-slate-200 pb-4">
          <h1 className="text-xl sm:text-2xl font-black text-slate-800 leading-none">Customer Account</h1>
          <p className="text-[11px] text-slate-400 mt-1">Manage profile and check real-time order history tracking</p>
        </section>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          {/* Left: Profile Summary */}
          <div className="space-y-6">
            <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6 text-left">
              <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2 pb-3 border-b border-slate-100">
                <User className="w-4 h-4 text-emerald-600" />
                Profile Particulars
              </h3>

              <div className="space-y-4 text-xs">
                <div className="flex items-start gap-3">
                  <User className="w-4.5 h-4.5 text-slate-400 mt-0.5" />
                  <div>
                    <span className="text-[9px] text-slate-400 uppercase font-extrabold tracking-wider block">Full Name</span>
                    <strong className="text-slate-800 font-bold block">{profile?.name || 'Loading name...'}</strong>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <Mail className="w-4.5 h-4.5 text-slate-400 mt-0.5" />
                  <div>
                    <span className="text-[9px] text-slate-400 uppercase font-extrabold tracking-wider block">Email Address</span>
                    <span className="text-slate-700 block font-semibold">{user?.email}</span>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <Phone className="w-4.5 h-4.5 text-slate-400 mt-0.5" />
                  <div>
                    <span className="text-[9px] text-slate-400 uppercase font-extrabold tracking-wider block">Phone Number</span>
                    <span className="text-slate-700 block font-mono font-bold">{profile?.phone || 'Not added'}</span>
                  </div>
                </div>

                <div className="flex items-start gap-3">
                  <MapPin className="w-4.5 h-4.5 text-slate-400 mt-0.5" />
                  <div>
                    <span className="text-[9px] text-slate-400 uppercase font-extrabold tracking-wider block">Default Address</span>
                    <span className="text-slate-650 block leading-normal font-semibold">{profile?.address || 'Not added'}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Right: Order History */}
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6 text-left">
              <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2 pb-3 border-b border-slate-100">
                <ClipboardList className="w-4 h-4 text-emerald-600" />
                Order Tracking Logs
              </h3>

              {loadingOrders ? (
                <p className="text-xs text-slate-400 text-center py-12 font-semibold">Loading orders history...</p>
              ) : orders.length === 0 ? (
                <div className="text-center py-16 space-y-4">
                  <div className="w-16 h-16 bg-slate-50 border border-dashed border-slate-350 rounded-full flex items-center justify-center text-slate-350 mx-auto">
                    <ClipboardList className="w-8 h-8" />
                  </div>
                  <h4 className="font-bold text-slate-800 text-sm">No Orders Placed Yet</h4>
                  <p className="text-xs text-slate-400 max-w-xs mx-auto">
                    You haven't placed any order on HR Traders yet. Start browsing our catalog to buy groceries.
                  </p>
                  <Link
                    href="/shop"
                    className="mt-6 px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-colors inline-block"
                  >
                    Browse Catalog
                  </Link>
                </div>
              ) : (
                <div className="space-y-4">
                  {orders.map((ord) => {
                    const isExpanded = expandedOrderId === ord.id;
                    const formattedDate = new Date(ord.created_at).toLocaleDateString('en-US', {
                      month: 'short',
                      day: 'numeric',
                      year: 'numeric',
                    });

                    return (
                      <div
                        key={ord.id}
                        className="border border-slate-200 rounded-2xl overflow-hidden shadow-sm"
                      >
                        {/* Header summary info */}
                        <div
                          onClick={() => toggleExpandOrder(ord.id)}
                          className="p-4 bg-slate-50/50 hover:bg-slate-50 flex flex-wrap items-center justify-between gap-4 cursor-pointer transition-colors text-xs"
                        >
                          <div className="flex items-center gap-4">
                            <div>
                              <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Order Ref</span>
                              <strong className="text-slate-850 block font-bold">#HRT-{String(ord.id).padStart(5, '0')}</strong>
                            </div>
                            <div>
                              <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Date Placed</span>
                              <span className="text-slate-650 block font-semibold">{formattedDate}</span>
                            </div>
                            <div>
                              <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Total Bill</span>
                              <span className="text-emerald-700 block font-mono font-extrabold">Rs. {ord.total_amount.toFixed(0)}</span>
                            </div>
                          </div>

                          <div className="flex items-center gap-3">
                            <span className={`px-2.5 py-0.5 rounded-full text-[9px] uppercase font-extrabold border ${getStatusClass(ord.status)}`}>
                              {ord.status.replace(/_/g, ' ')}
                            </span>
                            {isExpanded ? <ChevronUp className="w-4 h-4 text-slate-500" /> : <ChevronDown className="w-4 h-4 text-slate-500" />}
                          </div>
                        </div>

                        {/* Collapsed item details */}
                        {isExpanded && (
                          <div className="p-4 border-t border-slate-100 bg-white space-y-4">
                            <div className="space-y-2">
                              <h4 className="font-extrabold text-[10px] text-slate-400 uppercase tracking-wider block">Items Invoice</h4>
                              <div className="divide-y divide-slate-100 text-xs">
                                {ord.order_items?.map((item) => (
                                  <div
                                    key={item.id}
                                    className="py-2.5 flex items-center justify-between gap-4"
                                  >
                                    <div className="min-w-0">
                                      <strong className="text-slate-800 font-bold block truncate">{item.product_name}</strong>
                                      <span className="text-[10px] text-slate-400 font-mono">Rs. {item.price.toFixed(0)} x {item.quantity}</span>
                                    </div>
                                    <span className="font-mono font-extrabold text-slate-800">
                                      Rs. {(item.price * item.quantity).toFixed(0)}
                                    </span>
                                  </div>
                                ))}
                              </div>
                            </div>

                            {/* Additional metadata */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100 text-xs">
                              <div className="space-y-1">
                                <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Shipping Address</span>
                                <span className="text-slate-650 block leading-relaxed">{ord.customer_address}</span>
                              </div>
                              <div className="space-y-1">
                                <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Contact details</span>
                                <span className="text-slate-650 block font-mono">{ord.customer_phone}</span>
                                {ord.notes && (
                                  <span className="text-[10px] text-slate-450 block italic mt-1 leading-normal">
                                    Notes: "{ord.notes}"
                                  </span>
                                )}
                              </div>
                            </div>

                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>

        </div>

      </main>

      <Footer />
    </div>
  );
}
