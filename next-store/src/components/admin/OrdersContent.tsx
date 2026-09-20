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
  AlertCircle,
  Eye,
  Printer,
  X,
  Copy,
  Check,
  Package,
  Receipt
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
  coupon_code?: string;
  discount_amount?: number;
}

interface OrdersContentProps {
  initialOrders: Order[];
}

export const OrdersContent: React.FC<OrdersContentProps> = ({ initialOrders }) => {
  const [orders, setOrders] = useState<Order[]>(initialOrders);
  const [statusFilter, setStatusFilter] = useState<string>(''); // empty means All
  const [updatingId, setUpdatingId] = useState<number | null>(null);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [copiedAddress, setCopiedAddress] = useState(false);

  // WhatsApp Alert Template
  const [whatsappTemplate, setWhatsappTemplate] = useState('');

  const fetchFreshOrders = async () => {
    try {
      const { data, error } = await supabase
        .from('orders')
        .select('*, order_items(*)')
        .order('id', { ascending: false });

      if (!error && data) {
        setOrders(data as Order[]);
      }
    } catch (err) {
      console.error('Error fetching fresh orders on mount:', err);
    }
  };

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
    fetchFreshOrders();

    // Live sync channel to automatically load newly placed/updated orders in the list
    const channel = supabase
      .channel('admin-orders-live-sync')
      .on(
        'postgres_changes',
        {
          event: '*',
          schema: 'public',
          table: 'orders',
        },
        async () => {
          await fetchFreshOrders();
        }
      )
      .subscribe();

    return () => {
      supabase.removeChannel(channel);
    };
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

  const getOrderPricing = (ord: Order) => {
    const items = ord.order_items || [];
    const itemsSubtotal = items.reduce((sum, it) => sum + (Number(it.price || 0) * Number(it.quantity || 1)), 0);
    const discount = Number((ord as any).discount_amount || 0);
    const totalAmount = Number(ord.total_amount || 0);
    // Explicit delivery charges: total_amount - (itemsSubtotal - discount)
    const deliveryFee = itemsSubtotal > 0 ? Math.max(0, Math.round((totalAmount - (itemsSubtotal - discount)) * 100) / 100) : 0;
    return {
      itemsSubtotal,
      discount,
      deliveryFee,
      totalAmount
    };
  };

  const openOrderModal = async (ord: Order) => {
    setSelectedOrder(ord);
    if (!ord.order_items || ord.order_items.length === 0) {
      try {
        const { data, error } = await supabase
          .from('order_items')
          .select('*')
          .eq('order_id', ord.id);
        if (!error && data && data.length > 0) {
          setSelectedOrder(prev => prev && prev.id === ord.id ? { ...prev, order_items: data } : prev);
          setOrders(prev => prev.map(o => o.id === ord.id ? { ...o, order_items: data } : o));
        }
      } catch (err) {
        console.error('Error fetching order items:', err);
      }
    }
  };

  const copyAddressToClipboard = (address: string) => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(address);
      setCopiedAddress(true);
      setTimeout(() => setCopiedAddress(false), 2000);
    }
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
      setSelectedOrder(prev => (prev && prev.id === id ? { ...prev, status: newStatus } : prev));

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
            const pricing = getOrderPricing(ord);

            // Items formatted list
            const itemsString = ord.order_items && ord.order_items.length > 0
              ? ord.order_items.map(item => `${item.product_name} (x${item.quantity})`).join(', ')
              : 'No items';

            return (
              <div 
                key={ord.id} 
                className="bg-white shadow-sm p-5 rounded-2xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-5 transition-all hover:border-slate-300"
              >
                
                {/* Details Section */}
                <div className="space-y-2 flex-1">
                  <div className="flex items-center gap-3 text-xs flex-wrap">
                    {/* Clickable #HRT Order Number */}
                    <button
                      onClick={() => openOrderModal(ord)}
                      className="font-mono text-sm font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 hover:border-emerald-400 border border-emerald-200 px-2.5 py-1 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer shadow-xs active:scale-95 group"
                      title="Click to view complete order details & invoice"
                    >
                      <span className="group-hover:underline underline-offset-2">{ref}</span>
                      <Eye className="w-3.5 h-3.5 text-emerald-600 group-hover:scale-110 transition-transform" />
                    </button>

                    <span className={`px-2.5 py-0.5 rounded text-[10px] uppercase font-black border ${getStatusClass(ord.status)}`}>
                      {ord.status.replace(/_/g, ' ')}
                    </span>
                    <span className="text-slate-400 font-medium">
                      {formatDateTime(ord.created_at)}
                    </span>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-slate-600 pt-1">
                    <div>
                      <span className="text-slate-400 block uppercase font-semibold text-[10px]">Recipient</span>
                      <strong className="text-slate-800 text-[13px]">{ord.customer_name}</strong>
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
                  </div>

                  {/* Full Complete Shipping Address */}
                  <div className="text-xs text-left">
                    <span className="text-slate-400 block uppercase font-semibold text-[10px] mb-0.5">Complete Delivery Address</span>
                    <p className="text-slate-800 font-medium whitespace-normal break-words leading-relaxed bg-amber-50/40 p-2.5 rounded-xl border border-amber-200/50 select-text">
                      📍 {ord.customer_address}
                    </p>
                  </div>

                  {/* Purchased Items List */}
                  <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 mt-2 text-xs text-slate-700 text-left">
                    <div className="flex items-center justify-between mb-1">
                      <span className="font-bold text-slate-500">Purchased Items:</span>
                      <button 
                        onClick={() => openOrderModal(ord)}
                        className="text-[11px] text-emerald-600 hover:text-emerald-700 font-bold hover:underline cursor-pointer flex items-center gap-1"
                      >
                        View Full Details →
                      </button>
                    </div>
                    <span className="font-medium text-slate-800 leading-relaxed">{itemsString}</span>
                  </div>
                </div>

                {/* Status Trigger Action Panel */}
                <div className="flex flex-col sm:flex-row md:flex-col items-stretch sm:items-center md:items-end justify-between gap-4 border-t md:border-t-0 md:border-l border-slate-200 pt-4 md:pt-0 md:pl-6 md:w-64">
                  <div className="text-left md:text-right">
                    <span className="text-[10px] text-slate-400 uppercase font-semibold block tracking-wider">Total Invoice</span>
                    <span className="text-xl font-black text-emerald-600">
                      Rs. {pricing.totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </span>
                    <div className="text-[11px] text-slate-500 font-medium mt-0.5 flex items-center justify-start md:justify-end gap-1 flex-wrap">
                      <span>Items: Rs. {pricing.itemsSubtotal.toLocaleString()}</span>
                      <span className="text-slate-300">•</span>
                      <span className={pricing.deliveryFee > 0 ? "text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/60" : "text-slate-500 font-medium"}>
                        Delivery: {pricing.deliveryFee > 0 ? `+ Rs. ${pricing.deliveryFee.toLocaleString()}` : 'Free'}
                      </span>
                    </div>
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

      {/* Complete Order Details & Invoice Slip Modal */}
      {selectedOrder && (() => {
        const ord = selectedOrder;
        const ref = `#HRT-${String(ord.id).padStart(5, '0')}`;
        const pricing = getOrderPricing(ord);
        const isPending = ord.status === 'pending';
        const isPackaging = ord.status === 'packaging';
        const isShipping = ord.status === 'out_for_delivery';
        const isDelivered = ord.status === 'delivered';
        const isCancelled = ord.status === 'cancelled';

        return (
          <div 
            className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
            onClick={(e) => {
              if (e.target === e.currentTarget) setSelectedOrder(null);
            }}
          >
            <div className="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col my-auto max-h-[92vh] animate-in fade-in zoom-in-95 duration-150 print:max-w-none print:shadow-none print:border-none print:m-0 print:p-0">
              
              {/* Modal Header */}
              <div className="px-6 py-4 bg-slate-900 text-white flex items-center justify-between gap-4 border-b border-slate-800">
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-emerald-500/20 text-emerald-400 rounded-xl border border-emerald-500/30">
                    <Receipt className="w-5 h-5" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <h2 className="text-lg font-black tracking-tight">{ref}</h2>
                      <span className={`px-2 py-0.5 rounded text-[10px] uppercase font-black border ${getStatusClass(ord.status)}`}>
                        {ord.status.replace(/_/g, ' ')}
                      </span>
                    </div>
                    <p className="text-xs text-slate-400 mt-0.5">Order Invoice & Customer Slip • {formatDateTime(ord.created_at)}</p>
                  </div>
                </div>

                <div className="flex items-center gap-2 print:hidden">
                  <button
                    onClick={() => window.print()}
                    className="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold rounded-lg flex items-center gap-1.5 transition-all cursor-pointer active:scale-95"
                    title="Print Receipt Slip"
                  >
                    <Printer className="w-3.5 h-3.5" />
                    <span>Print Slip</span>
                  </button>
                  <button
                    onClick={() => setSelectedOrder(null)}
                    className="p-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition-colors cursor-pointer"
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>
              </div>

              {/* Printable Receipt Banner */}
              <div className="hidden print:block p-4 border-b text-center">
                <h1 className="text-2xl font-black">HR TRADERS</h1>
                <p className="text-xs text-slate-500">Official Order Delivery Slip</p>
                <div className="text-sm font-bold mt-1">Invoice: {ref} • Date: {formatDateTime(ord.created_at)}</div>
              </div>

              {/* Modal Body - Scrollable */}
              <div className="p-6 overflow-y-auto space-y-6 flex-1 text-slate-800 text-left">
                
                {/* Customer Information Grid */}
                <div className="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-3">
                  <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <span>Customer & Delivery Details</span>
                  </h3>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                      <span className="text-slate-400 block uppercase font-semibold text-[10px]">Customer Name</span>
                      <strong className="text-slate-900 text-sm">{ord.customer_name}</strong>
                    </div>

                    <div>
                      <span className="text-slate-400 block uppercase font-semibold text-[10px]">Phone / Contact</span>
                      <div className="flex items-center gap-2 mt-0.5">
                        <a 
                          href={`tel:${ord.customer_phone}`} 
                          className="font-mono text-emerald-700 font-bold hover:underline"
                        >
                          {ord.customer_phone}
                        </a>
                        <button
                          onClick={() => sendWhatsAppAlert(ord)}
                          className="px-2 py-0.5 bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-bold rounded-md flex items-center gap-1 transition-all cursor-pointer active:scale-95 print:hidden"
                        >
                          WhatsApp Alert
                        </button>
                      </div>
                    </div>

                    <div>
                      <span className="text-slate-400 block uppercase font-semibold text-[10px]">Payment Method</span>
                      <span className="font-semibold text-slate-700">{ord.payment_method || 'Cash on Delivery (COD)'}</span>
                    </div>

                    <div>
                      <span className="text-slate-400 block uppercase font-semibold text-[10px]">Order Date & Time</span>
                      <span className="font-medium text-slate-700">{formatDateTime(ord.created_at)}</span>
                    </div>
                  </div>

                  {/* Delivery Address Box */}
                  <div className="pt-2 border-t border-slate-200">
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-slate-400 uppercase font-semibold text-[10px]">Full Delivery Address</span>
                      <button
                        onClick={() => copyAddressToClipboard(ord.customer_address)}
                        className="text-[10px] text-emerald-700 hover:text-emerald-800 font-bold flex items-center gap-1 cursor-pointer transition-colors print:hidden"
                      >
                        {copiedAddress ? (
                          <>
                            <Check className="w-3 h-3 text-emerald-600" />
                            <span>Address Copied!</span>
                          </>
                        ) : (
                          <>
                            <Copy className="w-3 h-3" />
                            <span>Copy Address</span>
                          </>
                        )}
                      </button>
                    </div>
                    <div className="bg-amber-50/60 p-3 rounded-lg border border-amber-200/70 text-xs font-medium text-slate-900 leading-relaxed select-text">
                      📍 {ord.customer_address}
                    </div>
                  </div>

                  {/* Customer Notes */}
                  {ord.notes && (
                    <div className="pt-2 border-t border-slate-200">
                      <span className="text-slate-400 uppercase font-semibold text-[10px] block mb-0.5">Special Instructions / Customer Notes</span>
                      <div className="bg-blue-50/50 p-2.5 rounded-lg border border-blue-200/60 text-xs text-blue-900">
                        {ord.notes}
                      </div>
                    </div>
                  )}
                </div>

                {/* Ordered Items Table */}
                <div>
                  <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                    <Package className="w-3.5 h-3.5 text-slate-400" />
                    <span>Ordered Items ({ord.order_items?.length || 0})</span>
                  </h3>

                  <div className="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                    <table className="w-full text-left text-xs border-collapse">
                      <thead className="bg-slate-100 text-slate-600 font-semibold border-b border-slate-200">
                        <tr>
                          <th className="py-2.5 px-3 w-10 text-center">#</th>
                          <th className="py-2.5 px-3">Product Name</th>
                          <th className="py-2.5 px-3 text-right">Price</th>
                          <th className="py-2.5 px-3 text-center">Qty</th>
                          <th className="py-2.5 px-3 text-right">Total</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100 bg-white">
                        {ord.order_items && ord.order_items.length > 0 ? (
                          ord.order_items.map((it, idx) => {
                            const lineTotal = Number(it.price || 0) * Number(it.quantity || 1);
                            return (
                              <tr key={it.id || idx} className="hover:bg-slate-50/60">
                                <td className="py-2.5 px-3 text-center font-mono text-slate-400">{idx + 1}</td>
                                <td className="py-2.5 px-3 font-semibold text-slate-800">{it.product_name}</td>
                                <td className="py-2.5 px-3 text-right font-mono text-slate-600">
                                  Rs. {Number(it.price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </td>
                                <td className="py-2.5 px-3 text-center font-bold text-slate-800">
                                  x{it.quantity}
                                </td>
                                <td className="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                  Rs. {lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </td>
                              </tr>
                            );
                          })
                        ) : (
                          <tr>
                            <td colSpan={5} className="py-4 text-center text-slate-400">
                              No items recorded for this order.
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>

                {/* Pricing & Delivery Charges Breakdown */}
                <div className="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-2">
                  <div className="flex justify-between items-center text-xs text-slate-600">
                    <span>Items Subtotal</span>
                    <span className="font-mono font-semibold">
                      Rs. {pricing.itemsSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </span>
                  </div>

                  {pricing.discount > 0 && (
                    <div className="flex justify-between items-center text-xs text-rose-600">
                      <span>Coupon Discount ({ord.coupon_code || 'PROMO'})</span>
                      <span className="font-mono font-semibold">
                        - Rs. {pricing.discount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                      </span>
                    </div>
                  )}

                  {/* Explicit Delivery Charges row */}
                  <div className="flex justify-between items-center text-xs p-2.5 rounded-lg bg-emerald-50/80 border border-emerald-200">
                    <span className="font-bold text-emerald-800 flex items-center gap-1.5">
                      <Truck className="w-4 h-4 text-emerald-600" />
                      Delivery Charges (شامل شدہ ڈیلیوری چارجز):
                    </span>
                    <span className="font-mono font-bold text-emerald-700 text-sm">
                      {pricing.deliveryFee > 0 
                        ? `+ Rs. ${pricing.deliveryFee.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` 
                        : 'FREE DELIVERY (Rs. 0.00)'}
                    </span>
                  </div>

                  <div className="pt-2.5 border-t border-slate-200 flex justify-between items-center">
                    <div>
                      <span className="text-xs uppercase font-black text-slate-800 block">Total Bill / Net Payable</span>
                      <span className="text-[10px] text-slate-400">Amount to collect upon delivery (COD)</span>
                    </div>
                    <span className="text-2xl font-black text-emerald-600 font-mono">
                      Rs. {pricing.totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </span>
                  </div>
                </div>

              </div>

              {/* Modal Footer with Actions */}
              <div className="px-6 py-4 bg-slate-100 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 print:hidden">
                <div className="flex items-center gap-2 w-full sm:w-auto">
                  {updatingId === ord.id ? (
                    <div className="flex items-center gap-1.5 text-xs text-slate-500 py-1">
                      <Loader2 className="w-4 h-4 animate-spin" /> Updating Status...
                    </div>
                  ) : (
                    <>
                      {isPending && (
                        <button 
                          onClick={() => handleStatusChange(ord.id, 'packaging')}
                          className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition-colors cursor-pointer shadow-xs active:scale-95"
                        >
                          Start Packaging
                        </button>
                      )}
                      {isPackaging && (
                        <button 
                          onClick={() => handleStatusChange(ord.id, 'out_for_delivery')}
                          className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-lg transition-colors cursor-pointer shadow-xs active:scale-95"
                        >
                          Dispatch / Ship
                        </button>
                      )}
                      {isShipping && (
                        <button 
                          onClick={() => handleStatusChange(ord.id, 'delivered')}
                          className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition-colors cursor-pointer shadow-xs active:scale-95"
                        >
                          Mark Delivered
                        </button>
                      )}
                      {!isCancelled && !isDelivered && (
                        <button 
                          onClick={() => handleStatusChange(ord.id, 'cancelled')}
                          className="px-3 py-2 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 hover:border-rose-300 text-xs rounded-lg transition-colors cursor-pointer active:scale-95"
                        >
                          Cancel Order
                        </button>
                      )}
                      {isDelivered && (
                        <span className="text-emerald-700 text-xs font-bold flex items-center gap-1">
                          <CheckCircle2 className="w-4 h-4" /> Delivered Successfully
                        </span>
                      )}
                      {isCancelled && (
                        <span className="text-rose-600 text-xs font-bold flex items-center gap-1">
                          <XCircle className="w-4 h-4" /> Order Cancelled
                        </span>
                      )}
                    </>
                  )}
                </div>

                <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
                  <button
                    onClick={() => window.print()}
                    className="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg flex items-center gap-1.5 transition-colors cursor-pointer active:scale-95"
                  >
                    <Printer className="w-3.5 h-3.5" />
                    <span>Print Slip</span>
                  </button>
                  <button
                    onClick={() => setSelectedOrder(null)}
                    className="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg transition-colors cursor-pointer active:scale-95"
                  >
                    Close
                  </button>
                </div>
              </div>

            </div>
          </div>
        );
      })()}

    </div>
  );
};
