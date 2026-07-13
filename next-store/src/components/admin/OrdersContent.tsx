'use client';

import React, { useState } from 'react';
import { supabase } from '@/lib/supabase';
import { 
  Search, 
  ChevronDown, 
  ChevronUp, 
  MapPin, 
  Phone, 
  Calendar,
  ClipboardList,
  Filter,
  DollarSign
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
  customer_name: string;
  customer_address: string;
  customer_phone: string;
  notes?: string;
  order_items?: OrderItem[];
}

interface OrdersContentProps {
  initialOrders: Order[];
}

export const OrdersContent: React.FC<OrdersContentProps> = ({ initialOrders }) => {
  const [orders, setOrders] = useState<Order[]>(initialOrders);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [expandedOrderId, setExpandedOrderId] = useState<number | null>(null);
  const [updatingId, setUpdatingId] = useState<number | null>(null);

  // WhatsApp Alert Template
  const [whatsappTemplate, setWhatsappTemplate] = useState('');

  React.useEffect(() => {
    const fetchTemplate = async () => {
      try {
        const { data } = await supabase
          .from('settings')
          .select('val_value')
          .eq('key_name', 'whatsapp_dispatch_template')
          .maybeSingle();
        if (data?.val_value) {
          setWhatsappTemplate(data.val_value);
        }
      } catch (err) {
        console.error(err);
      }
    };
    fetchTemplate();
  }, []);

  const sendWhatsAppAlert = (ord: Order) => {
    const template = whatsappTemplate || "Hi {name}, your order #HRT-{ref} total Bill Rs. {total} is dispatched for delivery at {address}. Thank you!";
    const formattedRef = String(ord.id).padStart(5, '0');
    const msg = template
      .replace(/{name}/g, ord.customer_name)
      .replace(/{ref}/g, formattedRef)
      .replace(/{total}/g, ord.total_amount.toFixed(0))
      .replace(/{address}/g, ord.customer_address);
      
    let cleanPhone = ord.customer_phone.replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '92' + cleanPhone.substring(1);
    } else if (!cleanPhone.startsWith('92') && cleanPhone.length === 10) {
      cleanPhone = '92' + cleanPhone;
    }
    
    const waUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`;
    window.open(waUrl, '_blank');
  };

  // Filter orders
  const filteredOrders = orders.filter((o) => {
    const matchesSearch =
      searchQuery.trim() === '' ||
      o.customer_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      o.customer_phone.toLowerCase().includes(searchQuery.toLowerCase()) ||
      String(o.id).includes(searchQuery);
    const matchesStatus = statusFilter === '' || o.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const toggleExpandOrder = async (id: number) => {
    if (expandedOrderId === id) {
      setExpandedOrderId(null);
      return;
    }

    // Expand & fetch items if not loaded
    const orderIndex = orders.findIndex((o) => o.id === id);
    if (orderIndex !== -1 && !orders[orderIndex].order_items) {
      try {
        const { data: itemsData } = await supabase
          .from('order_items')
          .select('*')
          .eq('order_id', id);

        setOrders(
          orders.map((o) =>
            o.id === id ? { ...o, order_items: (itemsData as OrderItem[]) || [] } : o
          )
        );
      } catch (err) {
        console.error('Failed to load order items:', err);
      }
    }
    setExpandedOrderId(id);
  };

  const handleStatusChange = async (id: number, newStatus: Order['status']) => {
    setUpdatingId(id);
    try {
      const { error } = await supabase
        .from('orders')
        .update({ status: newStatus })
        .eq('id', id);

      if (error) throw error;

      // Update state
      setOrders(
        orders.map((o) => (o.id === id ? { ...o, status: newStatus } : o))
      );

      // Trigger Web Push Notification alert to customer device
      const orderObj = orders.find((o) => o.id === id);
      if (orderObj) {
        let messageBody = '';
        switch (newStatus) {
          case 'packaging':
            messageBody = `Hi ${orderObj.customer_name}, your order #HRT-${String(id).padStart(5, '0')} is currently being packed!`;
            break;
          case 'out_for_delivery':
            messageBody = `Hi ${orderObj.customer_name}, your order #HRT-${String(id).padStart(5, '0')} is out for delivery! Our rider is on their way.`;
            break;
          case 'delivered':
            messageBody = `Hi ${orderObj.customer_name}, your order #HRT-${String(id).padStart(5, '0')} has been successfully delivered! Thank you.`;
            break;
          case 'cancelled':
            messageBody = `Hi ${orderObj.customer_name}, your order #HRT-${String(id).padStart(5, '0')} has been cancelled.`;
            break;
          default:
            messageBody = `Hi ${orderObj.customer_name}, your order #HRT-${String(id).padStart(5, '0')} status updated to: ${newStatus}`;
        }

        fetch('/api/push-notify', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            phone: orderObj.customer_phone,
            title: `Order Update - HR Traders`,
            body: messageBody,
            url: `/my-account`
          })
        }).catch(err => console.error('Error triggering push notify:', err));
      }
    } catch (err: any) {
      alert('Failed to update status: ' + err.message);
    } finally {
      setUpdatingId(null);
    }
  };

  const getStatusClass = (status: Order['status']) => {
    switch (status) {
      case 'pending':
        return 'bg-amber-50 text-amber-705 border-amber-200';
      case 'packaging':
        return 'bg-blue-50 text-blue-705 border-blue-200';
      case 'out_for_delivery':
        return 'bg-purple-50 text-purple-705 border-purple-200 animate-pulse';
      case 'delivered':
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'cancelled':
        return 'bg-rose-50 text-rose-700 border-rose-200';
      default:
        return 'bg-slate-50 text-slate-705 border-slate-200';
    }
  };

  return (
    <div className="space-y-6 w-full flex flex-col flex-1 text-left">
      
      {/* Header */}
      <section className="pb-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-black text-slate-800 uppercase tracking-wider">Order Register</h1>
          <p className="text-xs text-slate-400 mt-1">Track customer order dispatch cycles, update fulfillment status levels.</p>
        </div>
      </section>

      {/* Filters row */}
      <section className="flex flex-col sm:flex-row items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          {/* Search */}
          <div className="relative w-full sm:w-64">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search name, phone, ref..."
              className="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 text-slate-800 shadow-sm"
            />
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          </div>

          {/* Status Filter */}
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="bg-white border border-slate-200 px-3.5 py-2 rounded-xl text-xs text-slate-650 focus:outline-none focus:border-emerald-500 shadow-sm w-full sm:w-auto font-semibold"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="packaging">Packaging</option>
            <option value="out_for_delivery">Out for Delivery</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </section>

      {/* Orders List Table */}
      <section className="space-y-4">
        {filteredOrders.length === 0 ? (
          <div className="bg-white border border-slate-200 rounded-3xl p-16 text-center shadow-sm">
            <div className="w-16 h-16 bg-slate-50 border border-dashed border-slate-350 rounded-full flex items-center justify-center text-slate-350 mx-auto mb-4">
              <ClipboardList className="w-8 h-8" />
            </div>
            <h4 className="font-extrabold text-slate-800 text-sm">No Orders Found</h4>
            <p className="text-xs text-slate-400 mt-1">There are no orders registered or matching your active search filters.</p>
          </div>
        ) : (
          filteredOrders.map((ord) => {
            const isExpanded = expandedOrderId === ord.id;
            const formattedDate = new Date(ord.created_at).toLocaleString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
              hour: 'numeric',
              minute: '2-digit',
            });

            return (
              <div
                key={ord.id}
                className="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm transition-all"
              >
                {/* Header overview row */}
                <div
                  onClick={() => toggleExpandOrder(ord.id)}
                  className="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 cursor-pointer hover:bg-slate-50/50 transition-colors text-xs"
                >
                  <div className="flex flex-wrap items-center gap-4 sm:gap-6 text-left">
                    <div>
                      <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Order Ref</span>
                      <strong className="text-slate-800 font-bold block text-sm">#HRT-{String(ord.id).padStart(5, '0')}</strong>
                    </div>
                    <div>
                      <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Customer Name</span>
                      <strong className="text-slate-800 font-bold block">{ord.customer_name}</strong>
                    </div>
                    <div>
                      <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Date Placed</span>
                      <span className="text-slate-550 block font-semibold">{formattedDate}</span>
                    </div>
                    <div>
                      <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Total Bill</span>
                      <span className="text-emerald-700 block font-mono font-extrabold">Rs. {ord.total_amount.toFixed(0)}</span>
                    </div>
                  </div>

                  <div className="flex items-center gap-3" onClick={(e) => e.stopPropagation()}>
                    {/* Status dropdown selector */}
                    <select
                      value={ord.status}
                      disabled={updatingId === ord.id}
                      onChange={(e) => handleStatusChange(ord.id, e.target.value as Order['status'])}
                      className={`px-3 py-1 rounded-full text-[10px] uppercase font-bold border focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer ${getStatusClass(ord.status)}`}
                    >
                      <option value="pending">Pending</option>
                      <option value="packaging">Packaging</option>
                      <option value="out_for_delivery">Out for Delivery</option>
                      <option value="delivered">Delivered</option>
                      <option value="cancelled">Cancelled</option>
                    </select>

                    <button
                      onClick={() => toggleExpandOrder(ord.id)}
                      className="p-1 text-slate-400 hover:text-slate-700 transition-colors"
                    >
                      {isExpanded ? <ChevronUp className="w-5 h-5" /> : <ChevronDown className="w-5 h-5" />}
                    </button>
                  </div>
                </div>

                {/* Collapsed items view */}
                {isExpanded && (
                  <div className="p-5 border-t border-slate-100 bg-slate-50/20 space-y-5 text-xs text-left">
                    <div className="space-y-2">
                      <h4 className="font-extrabold text-[10px] text-slate-400 uppercase tracking-wider block">Items Bill Invoice</h4>
                      <div className="bg-white border border-slate-200/60 rounded-2xl p-4 divide-y divide-slate-100">
                        {ord.order_items ? (
                          ord.order_items.map((item) => (
                            <div key={item.id} className="py-2.5 flex items-center justify-between gap-4 first:pt-0 last:pb-0">
                              <div className="min-w-0">
                                <strong className="text-slate-800 font-bold block truncate">{item.product_name}</strong>
                                <span className="text-[10px] text-slate-400 font-mono">Rs. {item.price.toFixed(0)} x {item.quantity}</span>
                              </div>
                              <span className="font-mono font-extrabold text-slate-700">
                                Rs. {(item.price * item.quantity).toFixed(0)}
                              </span>
                            </div>
                          ))
                        ) : (
                          <p className="text-slate-400 text-center py-2">Loading items details...</p>
                        )}
                      </div>
                    </div>

                    {/* Customer shipping details */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                      <div className="space-y-1.5">
                        <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Shipping Address</span>
                        <div className="flex items-start gap-2 text-slate-650">
                          <MapPin className="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" />
                          <span className="leading-relaxed font-semibold">{ord.customer_address}</span>
                        </div>
                      </div>
                      <div className="space-y-3">
                        <div className="space-y-1">
                          <span className="text-[9px] text-slate-400 uppercase font-extrabold block">Contact Mobile</span>
                          <div className="flex flex-wrap items-center gap-2">
                            <div className="flex items-center gap-2 text-slate-650">
                              <Phone className="w-4 h-4 text-emerald-500 flex-shrink-0" />
                              <span className="font-mono font-bold">{ord.customer_phone}</span>
                            </div>
                            <button
                              type="button"
                              onClick={() => sendWhatsAppAlert(ord)}
                              className="px-2 py-0.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-250 rounded-lg text-[9px] font-black uppercase transition-all flex items-center gap-1 active:scale-95 shadow-sm"
                              title="Send WhatsApp Alert"
                            >
                              <svg className="w-3 h-3 fill-current flex-shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.454 5.709 1.455h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                              </svg>
                              WhatsApp Alert
                            </button>
                          </div>
                        </div>
                        {ord.notes && (
                          <div className="space-y-1.5 p-3 bg-amber-50/50 border border-amber-100 rounded-xl">
                            <span className="text-[9px] text-amber-800 uppercase font-extrabold block">Customer Instructions</span>
                            <p className="text-[10px] text-amber-700 italic font-medium leading-relaxed">
                              "{ord.notes}"
                            </p>
                          </div>
                        )}
                      </div>
                    </div>

                  </div>
                )}
              </div>
            );
          })
        )}
      </section>

    </div>
  );
};
