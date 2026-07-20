'use client';

import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/supabase';
import { 
  Phone, 
  MapPin, 
  Calendar, 
  Truck, 
  CheckCircle2, 
  XCircle, 
  Loader2,
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
  const [statusFilter, setStatusFilter] = useState<string>(''); // empty means All
  const [updatingId, setUpdatingId] = useState<number | null>(null);

  // WhatsApp Alert Template
  const [whatsappTemplate, setWhatsappTemplate] = useState('');

  useEffect(() => {
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

  const handleStatusChange = async (id: number, newStatus: Order['status']) => {
    setUpdatingId(id);
    try {
      const { error } = await supabase
        .from('orders')
        .update({ status: newStatus })
        .eq('id', id);

      if (error) throw error;

      // Update local state
      setOrders(prev => 
        prev.map((o) => (o.id === id ? { ...o, status: newStatus } : o))
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
        return 'bg-amber-50 text-amber-700 border-amber-200';
      case 'packaging':
        return 'bg-blue-50 text-blue-700 border-blue-200';
      case 'out_for_delivery':
        return 'bg-purple-50 text-purple-700 border-purple-200';
      case 'delivered':
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'cancelled':
        return 'bg-rose-50 text-rose-700 border-rose-200';
      default:
        return 'bg-slate-50 text-slate-700 border-slate-200';
    }
  };

  // Filter orders by status
  const filteredOrders = statusFilter === '' 
    ? orders 
    : orders.filter(o => o.status === statusFilter);

  const formatDateTime = (dateStr: string) => {
    const d = new Date(dateStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const day = String(d.getDate()).padStart(2, '0');
    const month = months[d.getMonth()];
    const year = d.getFullYear();
    
    let hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    
    return `${day}-${month}-${year} ${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
  };

  return (
    <div className="space-y-6 w-full flex flex-col flex-1 text-left select-none">
      
      {/* Header Title Section */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-4">
        <div>
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Fulfillment Desk</h1>
          <p className="text-xs text-slate-500 mt-1">Monitor, pack, and mark delivery status for online customer orders</p>
        </div>

        {/* Filter tabs */}
        <div className="flex flex-wrap items-center gap-2">
          <button
            onClick={() => setStatusFilter('')}
            className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all ${
              statusFilter === '' 
                ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/10' 
                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
            }`}
          >
            All Orders
          </button>
          {(['pending', 'packaging', 'out_for_delivery', 'delivered', 'cancelled'] as const).map((st) => (
            <button
              key={st}
              onClick={() => setStatusFilter(st)}
              className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all ${
                statusFilter === st 
                  ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/10' 
                  : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
              }`}
            >
              {st === 'out_for_delivery' ? 'Out for delivery' : st.charAt(0).toUpperCase() + st.slice(1)}
            </button>
          ))}
        </div>
      </div>

      {/* Orders queue grid list */}
      <div className="space-y-4">
        {filteredOrders.length === 0 ? (
          <div className="bg-white py-16 text-center text-slate-400 rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center gap-3">
            <Truck className="w-12 h-12 opacity-25 text-slate-405 animate-pulse" />
            <h3 className="font-bold text-slate-650 text-base">No orders in queue</h3>
            <p className="text-xs mt-1 text-slate-400">There are no incoming customer orders matching this filter.</p>
          </div>
        ) : (
          filteredOrders.map((ord) => {
            const ref = `#HRT-${String(ord.id).padStart(5, '0')}`;
            const isPending = ord.status === 'pending';
            const isPackaging = ord.status === 'packaging';
            const isShipping = ord.status === 'out_for_delivery';
            const isDelivered = ord.status === 'delivered';
            const isCancelled = ord.status === 'cancelled';

            // Items formatted list
            const itemsString = ord.order_items
              ? ord.order_items.map(item => `${item.product_name} (x${item.quantity})`).join(', ')
              : 'No items';

            return (
              <div 
                key={ord.id} 
                className="bg-white shadow-sm p-5 rounded-2xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-5 transition-all hover:border-slate-300"
              >
                
                {/* Details Section */}
                <div className="space-y-2 flex-1">
                  <div className="flex items-center gap-3 text-xs">
                    <span className="font-mono text-sm font-bold text-slate-800">{ref}</span>
                    <span className={`px-2.5 py-0.5 rounded text-[10px] uppercase font-black border ${getStatusClass(ord.status)}`}>
                      {ord.status.replace(/_/g, ' ')}
                    </span>
                    <span className="text-slate-400 font-medium">
                      {formatDateTime(ord.created_at)}
                    </span>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs text-slate-600 pt-1">
                    <div>
                      <span className="text-slate-400 block uppercase font-semibold text-[10px]">Recipient</span>
                      <strong className="text-slate-805 text-[13px]">{ord.customer_name}</strong>
                    </div>
                    <div>
                      <span className="text-slate-400 block uppercase font-semibold text-[10px]">Contact</span>
                      <div className="flex items-center gap-1.5 mt-0.5">
                        <span className="font-mono text-slate-700">{ord.customer_phone}</span>
                        <button 
                          onClick={() => sendWhatsAppAlert(ord)}
                          className="px-1.5 py-0.5 bg-emerald-50 hover:bg-emerald-500 hover:text-white text-emerald-600 border border-emerald-250 text-[9px] font-bold rounded flex items-center gap-0.5 transition-all cursor-pointer"
                          title="Send WhatsApp dispatch notification"
                        >
                          Alert
                        </button>
                      </div>
                    </div>
                    <div className="col-span-1 sm:col-span-2 md:col-span-1">
                      <span className="text-slate-400 block uppercase font-semibold text-[10px]">Address</span>
                      <span className="truncate block max-w-xs text-slate-700" title={ord.customer_address}>
                        {ord.customer_address}
                      </span>
                    </div>
                  </div>

                  {/* Purchased Grid Preview */}
                  <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 mt-2 text-xs text-slate-700 text-left">
                    <span className="font-bold text-slate-500 block mb-1">Purchased Gird:</span>
                    <span>{itemsString}</span>
                  </div>
                </div>

                {/* Status Trigger Action Panel */}
                <div className="flex flex-col sm:flex-row md:flex-col items-stretch sm:items-center md:items-end justify-between gap-4 border-t md:border-t-0 md:border-l border-slate-200 pt-4 md:pt-0 md:pl-6 md:w-64">
                  <div className="text-left md:text-right">
                    <span className="text-[10px] text-slate-450 uppercase font-semibold block">Total Invoice</span>
                    <span className="text-lg font-black text-emerald-600">Rs. {ord.total_amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                  </div>

                  <div className="flex flex-wrap gap-2 w-full md:justify-end">
                    {updatingId === ord.id ? (
                      <div className="flex items-center gap-1.5 text-xs text-slate-400 py-1">
                        <Loader2 className="w-4.5 h-4.5 animate-spin text-slate-400" /> Updating Status...
                      </div>
                    ) : (
                      <>
                        {isPending && (
                          <>
                            <button 
                              onClick={() => handleStatusChange(ord.id, 'packaging')}
                              className="flex-1 sm:flex-initial px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition-colors active:scale-95"
                            >
                              Start Packaging
                            </button>
                            <button 
                              onClick={() => handleStatusChange(ord.id, 'cancelled')}
                              className="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 hover:border-rose-300 text-xs rounded-lg transition-colors active:scale-95"
                            >
                              Cancel
                            </button>
                          </>
                        )}
                        {isPackaging && (
                          <>
                            <button 
                              onClick={() => handleStatusChange(ord.id, 'out_for_delivery')}
                              className="flex-1 sm:flex-initial px-3.5 py-1.5 bg-purple-650 hover:bg-purple-700 text-white font-bold text-xs rounded-lg transition-colors active:scale-95"
                            >
                              Dispatch / Ship
                            </button>
                            <button 
                              onClick={() => handleStatusChange(ord.id, 'cancelled')}
                              className="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 hover:border-rose-300 text-xs rounded-lg transition-colors active:scale-95"
                            >
                              Cancel
                            </button>
                          </>
                        )}
                        {isShipping && (
                          <>
                            <button 
                              onClick={() => handleStatusChange(ord.id, 'delivered')}
                              className="flex-1 sm:flex-initial px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition-colors active:scale-95"
                            >
                              Mark Delivered
                            </button>
                            <button 
                              onClick={() => handleStatusChange(ord.id, 'cancelled')}
                              className="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 hover:border-rose-300 text-xs rounded-lg transition-colors active:scale-95"
                            >
                              Cancel
                            </button>
                          </>
                        )}
                        {isDelivered && (
                          <span className="text-emerald-600 text-xs font-bold py-1 flex items-center gap-1">
                            <CheckCircle2 className="w-4 h-4" /> Order Fulfill Completed
                          </span>
                        )}
                        {isCancelled && (
                          <span className="text-rose-600 text-xs font-bold py-1 flex items-center gap-1">
                            <XCircle className="w-4 h-4" /> Order Cancelled & Stock Synced
                          </span>
                        )}
                      </>
                    )}
                  </div>
                </div>

              </div>
            );
          })
        )}
      </div>

    </div>
  );
};
