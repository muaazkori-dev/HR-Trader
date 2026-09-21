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
  Receipt,
  Navigation,
  ExternalLink,
  Compass
} from 'lucide-react';

export const extractGpsLocation = (address?: string | null, notes?: string | null): { url: string; lat: string; lng: string } | null => {
  const combined = `${address || ''} ${notes || ''}`;
  const match = combined.match(/https:\/\/(?:www\.)?(?:google\.com\/maps\?q=|maps\.google\.com\/\?q=)(-?\d+\.\d+),(-?\d+\.\d+)/i);
  if (match) {
    return {
      url: match[0],
      lat: match[1],
      lng: match[2]
    };
  }
  return null;
};

export const cleanAddressForPrint = (addr?: string | null): string => {
  if (!addr) return '';
  return addr
    // Remove GPS label with URL and optional coordinates
    .replace(/📍\s*Live\s*(?:GPS\s*)?Location:?\s*https?:\/\/\S+/gi, '')
    // Remove any standalone Google Maps or other URLs
    .replace(/https?:\/\/(?:www\.)?(?:google\.com\/maps[^\s,)]*|maps\.google\.com[^\s,)]*|\S+)/gi, '')
    // Remove coordinates block like (GPS: ...) or [GPS: ...]
    .replace(/[\(\[]\s*GPS:\s*[^)\]]+[\)\]]/gi, '')
    // Remove any leftover "📍 Live GPS Location:" prefix without link
    .replace(/📍\s*Live\s*(?:GPS\s*)?Location:?/gi, '')
    // Split lines, strip commas/dots and empty lines
    .split('\n')
    .map(line => line.replace(/^[\s,.-]+|[\s,.-]+$/g, '').trim())
    .filter(line => line.length > 0)
    .join('\n')
    .trim();
};

export const cleanNotesForPrint = (notes?: string | null): string => {
  if (!notes) return '';
  return notes
    .replace(/\[\s*GPS:\s*https?:\/\/[^\]]+\]/gi, '')
    .replace(/\[\s*GPS:[^\]]+\]/gi, '')
    .replace(/\(\s*GPS:\s*[^)]+\)/gi, '')
    .replace(/https?:\/\/\S+/gi, '')
    .replace(/\|\s*$/g, '')
    .replace(/^\s*\|/g, '')
    .trim();
};

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
  const [copiedGpsLink, setCopiedGpsLink] = useState(false);

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
      .replace(/{address}/g, cleanAddressForPrint(ord.customer_address) || ord.customer_address);
      
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

  const copyGpsLinkToClipboard = (url: string) => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url);
      setCopiedGpsLink(true);
      setTimeout(() => setCopiedGpsLink(false), 2000);
    }
  };

  const printThermalSlip = (ord: Order) => {
    const ref = `#HRT-${String(ord.id).padStart(5, '0')}`;
    const pricing = getOrderPricing(ord);
    const formattedDate = formatDateTime(ord.created_at);
    const cleanAddress = cleanAddressForPrint(ord.customer_address) || ord.customer_address || 'Address not provided';
    const cleanNotes = ord.notes ? cleanNotesForPrint(ord.notes) : '';

    // Reuse or create hidden iframe dedicated exclusively to printing single thermal receipt
    let printFrame = document.getElementById('thermal-print-iframe') as HTMLIFrameElement | null;
    if (!printFrame) {
      printFrame = document.createElement('iframe');
      printFrame.id = 'thermal-print-iframe';
      printFrame.style.position = 'fixed';
      printFrame.style.right = '0';
      printFrame.style.bottom = '0';
      printFrame.style.width = '0';
      printFrame.style.height = '0';
      printFrame.style.border = 'none';
      printFrame.style.visibility = 'hidden';
      document.body.appendChild(printFrame);
    }

    const doc = printFrame.contentWindow?.document || printFrame.contentDocument;
    if (!doc) return;

    const items = ord.order_items || [];
    const itemsHtml = items.length > 0 
      ? items.map((it, idx) => {
          const lineTotal = Number(it.price || 0) * Number(it.quantity || 1);
          return `
            <tr>
              <td style="padding: 3px 0; vertical-align: top; word-break: break-word;">
                <div style="font-weight: bold; font-size: 11.5px; color: #000;">${idx + 1}. ${it.product_name}</div>
                <div style="font-size: 10.5px; color: #000;">Rs. ${Number(it.price || 0).toFixed(0)} × ${it.quantity}</div>
              </td>
              <td style="text-align: right; padding: 3px 0; vertical-align: top; font-weight: bold; font-size: 11.5px; white-space: nowrap; color: #000;">
                Rs. ${lineTotal.toFixed(0)}
              </td>
            </tr>
          `;
        }).join('')
      : '<tr><td colspan="2" style="text-align: center; padding: 6px 0; color: #000;">No items recorded</td></tr>';

    const thermalHtml = `
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8" />
          <title>Receipt ${ref}</title>
          <style>
            @page {
              size: 80mm auto;
              margin: 0;
            }
            @media print {
              html, body {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 1mm 2.5mm !important;
              }
            }
            * {
              box-sizing: border-box;
              margin: 0;
              padding: 0;
            }
            body {
              width: 100%;
              max-width: 80mm;
              margin: 0 auto;
              background: #fff;
              color: #000;
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
              font-size: 12px;
              line-height: 1.35;
              padding: 2mm 3mm;
              -webkit-print-color-adjust: exact;
              print-color-adjust: exact;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            .bold { font-weight: bold; }
            .divider {
              border-top: 1px dashed #000;
              margin: 5px 0;
            }
            .double-divider {
              border-top: 2px solid #000;
              margin: 5px 0;
            }
            table {
              width: 100%;
              border-collapse: collapse;
              font-size: 11.5px;
              color: #000;
            }
            .meta-table td {
              padding: 1.5px 0;
              color: #000;
            }
          </style>
        </head>
        <body>
          <div class="text-center" style="margin-bottom: 4px;">
            <h2 style="font-size: 17px; font-weight: 900; letter-spacing: 0.5px;">HR TRADERS</h2>
            <p style="font-size: 10.5px; font-weight: 600; margin-top: 1px;">ONLINE DELIVERY ORDER SLIP</p>
            <p style="font-size: 10.5px;">Ph: +92 333 7155323</p>
          </div>

          <div class="double-divider"></div>

          <table class="meta-table">
            <tr>
              <td><strong>Order No:</strong></td>
              <td class="text-right bold" style="font-size: 14px;">${ref}</td>
            </tr>
            <tr>
              <td><strong>Date:</strong></td>
              <td class="text-right">${formattedDate}</td>
            </tr>
            <tr>
              <td><strong>Payment:</strong></td>
              <td class="text-right bold">${ord.payment_method || 'Cash on Delivery (COD)'}</td>
            </tr>
            <tr>
              <td><strong>Status:</strong></td>
              <td class="text-right bold" style="text-transform: uppercase;">${ord.status.replace(/_/g, ' ')}</td>
            </tr>
          </table>

          <div class="divider"></div>

          <div style="margin: 4px 0;">
            <div style="font-size: 10px; font-weight: bold; text-transform: uppercase;">RIDER / DELIVERY DISPATCH:</div>
            <div style="font-size: 12.5px; font-weight: bold; margin-top: 1px;">Customer: ${ord.customer_name}</div>
            <div style="font-size: 12.5px; font-weight: bold; font-family: monospace;">Phone: ${ord.customer_phone}</div>
            <div style="margin-top: 4px; padding: 4px; border: 1px dashed #000; font-size: 11.5px; line-height: 1.4;">
              <strong>Delivery Address:</strong><br/>
              ${cleanAddress.replace(/\n/g, '<br/>')}
            </div>
            ${cleanNotes ? `<div style="margin-top: 3px; font-size: 10.5px; font-style: italic;">Note: ${cleanNotes.replace(/\n/g, '<br/>')}</div>` : ''}
          </div>

          <div class="divider"></div>

          <table style="margin: 4px 0;">
            <thead>
              <tr style="border-bottom: 1px solid #000;">
                <th class="text-left" style="padding-bottom: 3px; font-size: 11px;">ITEM</th>
                <th class="text-right" style="padding-bottom: 3px; font-size: 11px;">TOTAL</th>
              </tr>
            </thead>
            <tbody>
              ${itemsHtml}
            </tbody>
          </table>

          <div class="divider"></div>

          <table>
            <tr>
              <td>Items Subtotal:</td>
              <td class="text-right">Rs. ${pricing.itemsSubtotal.toFixed(0)}</td>
            </tr>
            ${pricing.discount > 0 ? `
            <tr>
              <td>Discount:</td>
              <td class="text-right">- Rs. ${pricing.discount.toFixed(0)}</td>
            </tr>
            ` : ''}
            <tr>
              <td style="font-weight: bold;">Delivery Charges:</td>
              <td class="text-right bold">${pricing.deliveryFee > 0 ? `+ Rs. ${pricing.deliveryFee.toFixed(0)}` : 'FREE'}</td>
            </tr>
          </table>

          <div class="double-divider"></div>

          <div style="margin: 4px 0;">
            <table style="font-size: 14.5px;">
              <tr>
                <td style="font-weight: 900;">TOTAL PAYABLE:</td>
                <td class="text-right" style="font-weight: 900; font-size: 15.5px;">Rs. ${pricing.totalAmount.toFixed(0)}</td>
              </tr>
            </table>
            <div class="text-right" style="font-size: 9.5px; font-weight: bold; margin-top: 1px;">
              (COLLECT CASH ON DELIVERY)
            </div>
          </div>

          <div class="divider"></div>

          <div class="text-center" style="font-size: 10px; margin-top: 6px; line-height: 1.4;">
            <div class="bold">Thank you for ordering with us!</div>
            <div>HR Traders • Quality Guaranteed</div>
            <div style="font-size: 9px; margin-top: 2px;">*** Rider Delivery Slip ***</div>
          </div>
        </body>
      </html>
    `;

    doc.open();
    doc.write(thermalHtml);
    doc.close();

    setTimeout(() => {
      try {
        printFrame?.contentWindow?.focus();
        printFrame?.contentWindow?.print();
      } catch (err) {
        console.error('Thermal print error:', err);
      }
    }, 250);
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
                  <div className="flex items-center gap-2.5 text-xs flex-wrap">
                    {/* Clickable #HRT Order Number */}
                    <button
                      onClick={() => openOrderModal(ord)}
                      className="font-mono text-sm font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 hover:border-emerald-400 border border-emerald-200 px-2.5 py-1 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer shadow-xs active:scale-95 group"
                      title="Click to view complete order details & invoice"
                    >
                      <span className="group-hover:underline underline-offset-2">{ref}</span>
                      <Eye className="w-3.5 h-3.5 text-emerald-600 group-hover:scale-110 transition-transform" />
                    </button>

                    {/* Quick Print Thermal Slip Button */}
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        printThermalSlip(ord);
                      }}
                      className="px-2 py-1 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 text-slate-650 border border-slate-250 rounded-lg flex items-center gap-1 text-[11px] font-bold transition-all cursor-pointer active:scale-95 shadow-2xs"
                      title="Print on Cashier 80mm/58mm Thermal Printer"
                    >
                      <Printer className="w-3 h-3 text-emerald-600" />
                      <span className="hidden sm:inline">Print Slip</span>
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
                  <div className="text-xs text-left space-y-1.5">
                    <span className="text-slate-400 block uppercase font-semibold text-[10px] mb-0.5">Complete Delivery Address</span>
                    <p className="text-slate-800 font-medium whitespace-normal break-words leading-relaxed bg-amber-50/40 p-2.5 rounded-xl border border-amber-200/50 select-text">
                      📍 {cleanAddressForPrint(ord.customer_address) || ord.customer_address}
                    </p>
                    {(() => {
                      const gps = extractGpsLocation(ord.customer_address, ord.notes);
                      if (!gps) return null;
                      return (
                        <div className="flex items-center gap-2 pt-0.5">
                          <a
                            href={gps.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-black shadow-2xs transition-all"
                            title="Open exact pin in Google Maps"
                          >
                            <Navigation className="w-3.5 h-3.5" />
                            <span>📍 Open Live Location in Google Maps</span>
                            <ExternalLink className="w-3 h-3 ml-0.5" />
                          </a>
                        </div>
                      );
                    })()}
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

                <div className="flex items-center gap-2">
                  <button
                    onClick={() => printThermalSlip(ord)}
                    className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg flex items-center gap-1.5 transition-all cursor-pointer active:scale-95 shadow-xs"
                    title="Print on Cashier Thermal Printer (80mm/58mm)"
                  >
                    <Printer className="w-3.5 h-3.5" />
                    <span>Print Thermal Slip</span>
                  </button>
                  <button
                    onClick={() => setSelectedOrder(null)}
                    className="p-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition-colors cursor-pointer"
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>
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
                  <div className="pt-2 border-t border-slate-200 space-y-2">
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-slate-400 uppercase font-semibold text-[10px]">Full Delivery Address</span>
                      <button
                        onClick={() => copyAddressToClipboard(cleanAddressForPrint(ord.customer_address) || ord.customer_address)}
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
                      📍 {cleanAddressForPrint(ord.customer_address) || ord.customer_address}
                    </div>

                    {(() => {
                      const gps = extractGpsLocation(ord.customer_address, ord.notes);
                      if (!gps) return null;
                      return (
                        <div className="bg-emerald-50/70 border border-emerald-200 rounded-xl p-3 space-y-2.5 print:border-slate-300">
                          <div className="flex flex-wrap items-center justify-between gap-2">
                            <div className="flex items-center gap-1.5">
                              <Navigation className="w-4 h-4 text-emerald-600" />
                              <span className="text-xs font-black text-emerald-900">
                                Customer Live GPS Location (کسٹمر کی پن لوکیشن)
                              </span>
                              <span className="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.2 rounded">
                                {gps.lat}, {gps.lng}
                              </span>
                            </div>
                            <div className="flex items-center gap-1.5 print:hidden">
                              <button
                                onClick={() => copyGpsLinkToClipboard(gps.url)}
                                className="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1 cursor-pointer"
                                title="Copy Google Maps link to send to delivery rider"
                              >
                                {copiedGpsLink ? (
                                  <>
                                    <Check className="w-3 h-3 text-emerald-600" />
                                    <span className="text-emerald-700 font-extrabold">Link Copied!</span>
                                  </>
                                ) : (
                                  <>
                                    <Copy className="w-3 h-3 text-slate-500" />
                                    <span>Copy Link for Rider</span>
                                  </>
                                )}
                              </button>
                              <a
                                href={gps.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black rounded-lg transition-all flex items-center gap-1 shadow-2xs"
                              >
                                <span>🗺 Open in Google Maps</span>
                                <ExternalLink className="w-3 h-3" />
                              </a>
                            </div>
                          </div>

                          {/* Embedded Interactive Map Preview */}
                          <div className="rounded-lg overflow-hidden border border-emerald-200 shadow-2xs print:hidden">
                            <iframe
                              title="Order Live Location"
                              width="100%"
                              height="160"
                              loading="lazy"
                              className="w-full border-0 block"
                              src={`https://www.openstreetmap.org/export/embed.html?bbox=${parseFloat(gps.lng) - 0.005}%2C${parseFloat(gps.lat) - 0.004}%2C${parseFloat(gps.lng) + 0.005}%2C${parseFloat(gps.lat) + 0.004}&layer=mapnik&marker=${gps.lat}%2C${gps.lng}`}
                            />
                          </div>
                        </div>
                      );
                    })()}
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
                    onClick={() => printThermalSlip(ord)}
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg flex items-center gap-1.5 transition-all cursor-pointer active:scale-95 shadow-sm"
                    title="Print on Cashier Thermal Printer (80mm/58mm)"
                  >
                    <Printer className="w-4 h-4" />
                    <span>Print Thermal Slip (کیشئر سلپ)</span>
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
