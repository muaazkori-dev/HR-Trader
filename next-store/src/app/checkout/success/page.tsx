import React, { Suspense } from 'react';
import Link from 'next/link';
import { notFound } from 'next/navigation';
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
  CheckCircle2
} from 'lucide-react';

interface SuccessPageProps {
  searchParams: Promise<{
    id?: string;
  }>;
}

export const revalidate = 0;

export default async function CheckoutSuccess({ searchParams }: SuccessPageProps) {
  const params = await searchParams;
  const orderIdStr = params.id || '';
  const orderId = parseInt(orderIdStr, 10);

  if (isNaN(orderId)) {
    return notFound();
  }

  // 1. Fetch Order details
  const { data: order, error: orderErr } = await supabase
    .from('orders')
    .select('*')
    .eq('id', orderId)
    .single();

  if (orderErr || !order) {
    return notFound();
  }

  // 2. Fetch order items
  const { data: orderItems, error: itemsErr } = await supabase
    .from('order_items')
    .select('*')
    .eq('order_id', orderId);

  const items = orderItems || [];

  // 3. Compile WhatsApp Text
  const padRef = String(order.id).padStart(5, '0');
  const formattedDate = new Date(order.created_at).toLocaleString('en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });

  let whatsappText = `🛍️ *NEW ORDER - HR TRADERS* 🛍️\n`;
  whatsappText += `--------------------------------------\n`;
  whatsappText += `*Order Reference:* #HRT-${padRef}\n`;
  whatsappText += `*Date:* ${formattedDate}\n\n`;

  whatsappText += `*CUSTOMER DETAILS:*\n`;
  whatsappText += `👤 *Name:* ${order.customer_name}\n`;
  whatsappText += `📞 *Phone:* ${order.customer_phone}\n`;
  whatsappText += `📍 *Address:* ${order.customer_address}\n\n`;

  whatsappText += `*ORDERED ITEMS:*\n`;
  items.forEach((item, index) => {
    whatsappText += `${index + 1}. ${item.product_name} x ${item.quantity} - Rs. ${(item.price * item.quantity).toFixed(0)}\n`;
  });
  whatsappText += `--------------------------------------\n`;
  whatsappText += `*Total Bill Amount:* Rs. ${order.total_amount.toFixed(0)}\n`;
  whatsappText += `*Payment Mode:* ${order.payment_method} (Cash on Delivery)\n\n`;
  whatsappText += `Thank you for shopping with HR Traders! 🙏`;

  const whatsappUrl = `https://api.whatsapp.com/send?phone=923033943814&text=${encodeURIComponent(whatsappText)}`;

  // Calculate items subtotal to show shipping breakdown
  const itemsSubtotal = items.reduce((acc, item) => acc + item.price * item.quantity, 0);
  const shippingFee = order.total_amount - itemsSubtotal;

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
          
          {/* Metadata */}
          <div className="grid grid-cols-2 gap-4 text-xs pb-6 border-b border-slate-200">
            <div className="space-y-1">
              <span className="text-slate-400 block uppercase font-extrabold tracking-wider text-[9px]">Delivery Address</span>
              <strong className="text-slate-800 block font-bold">{order.customer_name}</strong>
              <span className="text-slate-500 block font-mono font-bold">{order.customer_phone}</span>
              <span className="text-slate-500 block mt-1 leading-normal">{order.customer_address}</span>
            </div>
            <div className="text-right space-y-1">
              <span className="text-slate-400 block uppercase font-extrabold tracking-wider text-[9px]">Order Details</span>
              <span className="text-slate-600 block">Date: {formattedDate}</span>
              <span className="text-slate-600 block">Payment Method: {order.payment_method}</span>
              <span className="inline-block bg-emerald-50 text-emerald-700 text-[9px] uppercase font-extrabold border border-emerald-200 px-2 py-0.5 rounded-full mt-1.5">
                {order.status}
              </span>
            </div>
          </div>

          {/* Item details */}
          <div className="space-y-3">
            <h3 className="font-extrabold text-xs text-slate-800 uppercase tracking-wider">Item Details</h3>
            
            <div className="divide-y divide-slate-100">
              {items.map((item) => (
                <div key={item.id} className="py-3 flex items-center justify-between gap-4 first:pt-0 last:pb-0 text-xs">
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
              <span className="text-lg text-emerald-600 font-mono">Rs. {order.total_amount.toFixed(0)}</span>
            </div>
          </div>

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
