'use client';

import React, { useEffect, useState, Suspense } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { ConfettiTrigger } from '@/components/ConfettiTrigger';
import { supabase } from '@/lib/supabase';
import { 
  CheckCircle, 
  Printer, 
  Home, 
  MessageSquare,
  MapPin,
  Calendar,
  CreditCard,
  CheckCircle2,
  Loader2
} from 'lucide-react';

function SuccessPageContent() {
  const searchParams = useSearchParams();
  const orderIdStr = searchParams?.get('id') || '';
  const orderId = parseInt(orderIdStr, 10);

  const [loading, setLoading] = useState(true);
  const [order, setOrder] = useState<any>(null);
  const [items, setItems] = useState<any[]>([]);

  useEffect(() => {
    if (isNaN(orderId)) {
      setLoading(false);
      return;
    }

    // 1. Try local storage first to bypass RLS policies for anonymous checkout
    try {
      const stored = localStorage.getItem('last_order_details');
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed && parsed.order && parsed.order.id === orderId) {
          setOrder(parsed.order);
          setItems(parsed.items || []);
          setLoading(false);
          return;
        }
      }
    } catch (e) {
      console.error('Error reading localStorage order:', e);
    }

    // 2. Fetch from Supabase as fallback
    const fetchOrder = async () => {
      try {
        const { data: orderData, error: orderErr } = await supabase
          .from('orders')
          .select('*')
          .eq('id', orderId)
          .single();

        if (!orderErr && orderData) {
          setOrder(orderData);
          
          const { data: itemsData, error: itemsErr } = await supabase
            .from('order_items')
            .select('*')
            .eq('order_id', orderId);

          if (!itemsErr && itemsData) {
            setItems(itemsData);
          }
        }
      } catch (err) {
        console.error('Error fetching order from database:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchOrder();
  }, [orderId]);

  if (isNaN(orderId)) {
    return (
      <div className="flex flex-col min-h-screen bg-slate-50/50">
        <Header />
        <main className="flex-grow flex items-center justify-center p-8">
          <div className="text-center space-y-4 max-w-md">
            <div className="text-rose-500 text-5xl">⚠️</div>
            <h1 className="text-xl font-black text-slate-800">Invalid Order</h1>
            <p className="text-xs text-slate-400">Order ID is missing or invalid. Please check your URL.</p>
            <Link href="/" className="inline-block py-2.5 px-6 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs uppercase rounded-xl transition-all">
              Return Home
            </Link>
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  if (loading) {
    return (
      <div className="flex flex-col min-h-screen bg-slate-50/50">
        <Header />
        <main className="flex-grow flex flex-col items-center justify-center p-8 gap-3">
          <Loader2 className="w-8 h-8 text-emerald-600 animate-spin" />
          <span className="text-xs text-slate-450 font-semibold">Loading Invoice Bill Details...</span>
        </main>
        <Footer />
      </div>
    );
  }

  const padRef = String(orderId).padStart(5, '0');
  
  // Prepare fallback or full data
  const hasOrderDetails = !!order;
  const customerName = order?.customer_name || 'Customer';
  const customerPhone = order?.customer_phone || '';
  const customerAddress = order?.customer_address || '';
  const totalAmount = order?.total_amount || 0;
  const paymentMethod = order?.payment_method || 'COD';
  const status = order?.status || 'pending';
  const createdDate = order ? new Date(order.created_at) : new Date();
  
  const formattedDate = createdDate.toLocaleString('en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });

  // Calculate items subtotal
  const itemsSubtotal = items.reduce((acc, item) => acc + item.price * item.quantity, 0);
  const shippingFee = totalAmount > 0 ? (totalAmount - itemsSubtotal) : 180;

  // Compile WhatsApp Text
  let whatsappText = `🛍️ *NEW ORDER - HR TRADERS* 🛍️\n`;
  whatsappText += `--------------------------------------\n`;
  whatsappText += `*Order Reference:* #HRT-${padRef}\n`;
  whatsappText += `*Date:* ${formattedDate}\n\n`;

  if (hasOrderDetails) {
    whatsappText += `*CUSTOMER DETAILS:*\n`;
    whatsappText += `👤 *Name:* ${customerName}\n`;
    whatsappText += `📞 *Phone:* ${customerPhone}\n`;
    whatsappText += `📍 *Address:* ${customerAddress}\n\n`;

    whatsappText += `*ORDERED ITEMS:*\n`;
    items.forEach((item, index) => {
      whatsappText += `${index + 1}. ${item.product_name} x ${item.quantity} - Rs. ${(item.price * item.quantity).toFixed(0)}\n`;
    });
    whatsappText += `--------------------------------------\n`;
    whatsappText += `*Total Bill Amount:* Rs. ${totalAmount.toFixed(0)}\n`;
    whatsappText += `*Payment Mode:* ${paymentMethod} (Cash on Delivery)\n\n`;
  }
  whatsappText += `Thank you for shopping with HR Traders! 🙏`;

  const whatsappUrl = `https://api.whatsapp.com/send?phone=923033943814&text=${encodeURIComponent(whatsappText)}`;

  return (
    <div className="flex flex-col min-h-screen bg-slate-50/50">
      {/* Confetti Explosion client side */}
      <ConfettiTrigger />

      <Header />

      <main className="flex-1 max-w-3xl mx-auto px-4 py-12 sm:px-6 lg:px-8 w-full space-y-8">
        
        {/* Header confirmed */}
        <div className="text-center space-y-3">
          <div className="w-16 h-16 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-full flex items-center justify-center text-3xl mx-auto mb-2 animate-bounce">
            <CheckCircle className="w-8 h-8" />
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-slate-800">Order Confirmed!</h1>
          <p className="text-xs text-slate-400">Thank you, your Cash on Delivery order has been successfully placed.</p>
          <span className="inline-block bg-slate-100 border border-slate-200 text-xs px-3 py-1 rounded-full text-slate-700 font-mono font-bold">
            Order Reference: #HRT-{padRef}
          </span>
        </div>

        {/* Invoice Panel */}
        <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 text-left">
          
          {hasOrderDetails ? (
            <>
              {/* Metadata */}
              <div className="grid grid-cols-2 gap-4 text-xs pb-6 border-b border-slate-200">
                <div className="space-y-1">
                  <span className="text-slate-400 block uppercase font-extrabold tracking-wider text-[9px]">Delivery Address</span>
                  <strong className="text-slate-800 block font-bold">{customerName}</strong>
                  <span className="text-slate-500 block font-mono font-bold">{customerPhone}</span>
                  <span className="text-slate-500 block mt-1 leading-normal">{customerAddress}</span>
                </div>
                <div className="text-right space-y-1">
                  <span className="text-slate-400 block uppercase font-extrabold tracking-wider text-[9px]">Order Details</span>
                  <span className="text-slate-600 block">Date: {formattedDate}</span>
                  <span className="text-slate-600 block">Payment Method: {paymentMethod}</span>
                  <span className="inline-block bg-emerald-50 text-emerald-700 text-[9px] uppercase font-extrabold border border-emerald-200 px-2 py-0.5 rounded-full mt-1.5">
                    {status}
                  </span>
                </div>
              </div>

              {/* Item details */}
              <div className="space-y-3">
                <h3 className="font-extrabold text-xs text-slate-800 uppercase tracking-wider">Item Details</h3>
                
                <div className="divide-y divide-slate-100">
                  {items.map((item, idx) => (
                    <div key={idx} className="py-3 flex items-center justify-between gap-4 first:pt-0 last:pb-0 text-xs">
                      <div className="min-w-0 text-left">
                        <span className="font-bold text-slate-800 block truncate">{item.product_name}</span>
                        <span className="text-slate-400 text-[10px] font-mono">Rs. {item.price.toFixed(0)}</span>
                      </div>
                      <div className="text-right flex items-center gap-6">
                        <span className="text-slate-400 font-bold">x {item.quantity}</span>
                        <span className="font-mono font-extrabold text-slate-800 w-24 text-right">
                          Rs. {(item.price * item.quantity).toFixed(0)}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Totals */}
              <div className="border-t border-slate-200 pt-6 space-y-3 text-xs">
                <div className="flex items-center justify-between text-slate-500">
                  <span>Shipping / Delivery Fee</span>
                  <span className="font-mono font-bold text-slate-800">
                    {shippingFee > 0 ? `Rs. ${shippingFee.toFixed(0)}` : 'FREE'}
                  </span>
                </div>
                <div className="flex items-center justify-between text-sm font-extrabold text-slate-850 border-t border-slate-200 pt-3">
                  <span>Grand Total Invoice</span>
                  <span className="text-lg text-emerald-600 font-mono">Rs. {totalAmount.toFixed(0)}</span>
                </div>
              </div>
            </>
          ) : (
            <div className="py-8 text-center space-y-3">
              <CheckCircle2 className="w-12 h-12 text-emerald-500 mx-auto" />
              <h3 className="font-black text-slate-800 text-sm">Order Saved Successfully!</h3>
              <p className="text-xs text-slate-400 max-w-sm mx-auto leading-normal">
                Your order has been recorded in our shop registry under Reference <strong className="text-slate-700">#HRT-{padRef}</strong>.
              </p>
              <p className="text-[11px] text-slate-400">
                You can track this order's live preparation status from the homepage header tracking desk.
              </p>
            </div>
          )}

        </div>

        {/* WhatsApp & Navigation CTA */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <a
            href={whatsappUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center justify-center gap-2 py-3 bg-[#25d366] hover:bg-[#1ebd58] active:scale-95 text-slate-900 font-extrabold rounded-2xl text-xs transition-all shadow-md shadow-[#25d366]/20"
          >
            <MessageSquare className="w-4.5 h-4.5" /> Send Invoice Bill to WhatsApp
          </a>
          <Link
            href="/"
            className="flex items-center justify-center gap-2 py-3 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold rounded-2xl text-xs transition-all border border-slate-350"
          >
            <Home className="w-4.5 h-4.5" /> Continue Shopping
          </Link>
        </div>

      </main>

      <Footer />
    </div>
  );
}

export default function CheckoutSuccessPage() {
  return (
    <Suspense fallback={
      <div className="flex flex-col min-h-screen bg-slate-50/50">
        <Header />
        <main className="flex-grow flex flex-col items-center justify-center p-8 gap-3">
          <Loader2 className="w-8 h-8 text-emerald-600 animate-spin" />
          <span className="text-xs text-slate-450 font-semibold">Initializing Invoice Bill Details...</span>
        </main>
        <Footer />
      </div>
    }>
      <SuccessPageContent />
    </Suspense>
  );
}
