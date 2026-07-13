'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useCart } from '@/context/CartContext';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { 
  ShoppingBag, 
  MapPin, 
  Phone, 
  User, 
  FileText, 
  AlertCircle,
  Truck,
  ArrowLeft,
  CheckCircle2
} from 'lucide-react';

export default function Checkout() {
  const router = useRouter();
  const { cart, getCartTotal, getCartCount, clearCart } = useCart();
  const { user, profile } = useAuth();

  // State management
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [notes, setNotes] = useState('');

  const [minOrder, setMinOrder] = useState(0);
  const [defaultShipping, setDefaultShipping] = useState(180);
  const [shippingFee, setShippingFee] = useState(180);
  const [shopStatus, setShopStatus] = useState('open');
  const [loadingSettings, setLoadingSettings] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  // PWA/WhatsApp & Coupons State
  const [whatsappNumber, setWhatsappNumber] = useState('923337155323');
  const [couponCode, setCouponCode] = useState('');
  const [appliedCoupon, setAppliedCoupon] = useState<any>(null);
  const [couponError, setCouponError] = useState('');
  const [couponDiscount, setCouponDiscount] = useState(0);

  // 1. Redirect if cart is empty on mount
  useEffect(() => {
    if (cart.length === 0) {
      router.replace('/shop');
    }
  }, [cart, router]);

  // 2. Pre-populate user details if logged in
  useEffect(() => {
    if (profile) {
      setName(profile.name || '');
      setPhone(profile.phone || '');
      setAddress(profile.address || '');
    }
  }, [profile]);

  // 3. Fetch configurations and calculate shipping fee
  useEffect(() => {
    const fetchSettingsAndCheckFirstOrder = async () => {
      try {
        setLoadingSettings(true);

        // Fetch settings values
        const { data: settingsData } = await supabase
          .from('settings')
          .select('key_name, val_value');

        if (settingsData) {
          const min = settingsData.find((s) => s.key_name === 'min_order_value');
          if (min) setMinOrder(parseFloat(min.val_value));

          const ship = settingsData.find((s) => s.key_name === 'shipping_fee');
          const defaultShipVal = ship ? parseFloat(ship.val_value) : 180;
          setDefaultShipping(defaultShipVal);
          setShippingFee(defaultShipVal); // Default initially

          const status = settingsData.find((s) => s.key_name === 'shop_status');
          if (status) setShopStatus(status.val_value);

          const wa = settingsData.find((s) => s.key_name === 'whatsapp_number');
          if (wa) setWhatsappNumber(wa.val_value.replace(/[^0-9]/g, ''));
        }

        // Determine if first order for free shipping
        if (user) {
          const { count, error } = await supabase
            .from('orders')
            .select('*', { count: 'exact', head: true })
            .eq('customer_id', user.id)
            .neq('status', 'cancelled');

          if (!error && count !== null && count === 0) {
            // First order has free shipping!
            setShippingFee(0);
          }
        }
      } catch (err) {
        console.error('Error loading checkout parameters:', err);
      } finally {
        setLoadingSettings(false);
      }
    };

    fetchSettingsAndCheckFirstOrder();
  }, [user]);

  const handleApplyCoupon = async () => {
    setCouponError('');
    if (!couponCode.trim()) return;
    
    try {
      const { data, error } = await supabase
        .from('coupons')
        .select('*')
        .eq('code', couponCode.trim().toUpperCase())
        .eq('active', true)
        .maybeSingle();
        
      if (error || !data) {
        setCouponError('Invalid or inactive coupon code.');
        setAppliedCoupon(null);
        setCouponDiscount(0);
        return;
      }
      
      const subtotal = getCartTotal();
      if (subtotal < parseFloat(data.min_order_amount)) {
        setCouponError(`Min order amount to use this coupon is Rs. ${data.min_order_amount}`);
        setAppliedCoupon(null);
        setCouponDiscount(0);
        return;
      }
      
      let discount = 0;
      if (data.discount_type === 'percentage') {
        discount = (subtotal * parseFloat(data.discount_value)) / 100;
      } else {
        discount = parseFloat(data.discount_value);
      }
      
      discount = Math.min(discount, subtotal);
      
      setAppliedCoupon(data);
      setCouponDiscount(discount);
    } catch (err) {
      setCouponError('Failed to apply coupon.');
    }
  };

  const processCheckout = async (method: 'COD' | 'WhatsApp') => {
    if (cart.length === 0) return;

    if (shopStatus === 'closed') {
      setErrorMsg('Store is temporarily CLOSED. We cannot accept orders at this time.');
      return;
    }

    const subtotal = getCartTotal();
    if (subtotal < minOrder) {
      setErrorMsg(`Order subtotal must be at least Rs. ${minOrder} to place an order.`);
      return;
    }

    if (!name.trim() || !phone.trim() || !address.trim()) {
      setErrorMsg('Please complete all shipping address fields.');
      return;
    }

    setSubmitting(true);
    setErrorMsg('');

    try {
      // 1. Lock and decrement stock for all items
      for (const item of cart) {
        const { data: prodData, error: prodErr } = await supabase
          .from('products')
          .select('stock_quantity, name')
          .eq('id', item.id)
          .single();

        if (prodErr || !prodData) {
          throw new Error(`Failed to verify stock level for ${item.name}`);
        }

        if (prodData.stock_quantity < item.quantity) {
          throw new Error(
            `Stock level changed for '${prodData.name}'. Only ${prodData.stock_quantity} left in register. Please update your cart quantity.`
          );
        }
      }

      // 2. Create the order header
      const orderTotal = subtotal - couponDiscount + shippingFee;
      const { data: order, error: orderErr } = await supabase
        .from('orders')
        .insert([
          {
            customer_id: user?.id || null,
            customer_name: name.trim(),
            customer_phone: phone.trim(),
            customer_address: address.trim(),
            total_amount: orderTotal,
            payment_method: method,
            status: 'pending',
            notes: notes.trim() || null,
            coupon_code: appliedCoupon ? appliedCoupon.code : null,
            discount_amount: couponDiscount,
          },
        ])
        .select()
        .single();

      if (orderErr || !order) {
        throw new Error('Failed to record order: ' + orderErr?.message);
      }

      // 3. Create order details & decrement stock levels
      const orderItemsToInsert = cart.map((item) => ({
        order_id: order.id,
        product_id: item.id,
        price: item.price,
        quantity: item.quantity,
        product_name: item.name,
      }));

      const { error: itemsErr } = await supabase
        .from('order_items')
        .insert(orderItemsToInsert);

      if (itemsErr) {
        throw new Error('Failed to record order items: ' + itemsErr.message);
      }

      // Decrement product inventory stock
      for (const item of cart) {
        const { data: currentProd } = await supabase
          .from('products')
          .select('stock_quantity')
          .eq('id', item.id)
          .single();
          
        const newStock = Math.max(0, (currentProd?.stock_quantity || 0) - item.quantity);
        await supabase
          .from('products')
          .update({ stock_quantity: newStock })
          .eq('id', item.id);
      }

      // Link push subscription endpoint with customer phone number if subscribed
      if (typeof window !== 'undefined') {
        const endpoint = localStorage.getItem('push_subscription_endpoint');
        if (endpoint) {
          const cleanPhone = phone.trim().replace(/[^0-9]/g, '');
          await supabase
            .from('push_subscriptions')
            .update({ customer_phone: cleanPhone })
            .filter('subscription->>endpoint', 'eq', endpoint);
        }
      }

      // 4. Success! If WhatsApp, redirect to WhatsApp API
      if (method === 'WhatsApp') {
        let cartText = '';
        cart.forEach((item, index) => {
          cartText += `${index + 1}. *${item.name}* x${item.quantity} - Rs. ${(item.price * item.quantity).toFixed(0)}\n`;
        });
        
        const messageText = `*NEW ORDER - HR TRADERS*\n\n` +
          `*Order ID:* #HRT-${String(order.id).padStart(5, '0')}\n` +
          `*Customer Name:* ${name.trim()}\n` +
          `*Phone:* ${phone.trim()}\n` +
          `*Delivery Address:* ${address.trim()}\n` +
          `*Notes:* ${notes.trim() || 'None'}\n\n` +
          `*Order Items:*\n${cartText}\n` +
          `*Subtotal:* Rs. ${subtotal.toFixed(0)}\n` +
          (couponDiscount > 0 ? `*Discount Coupon:* -Rs. ${couponDiscount.toFixed(0)} (${appliedCoupon?.code})\n` : '') +
          `*Shipping Fee:* Rs. ${shippingFee.toFixed(0)}\n` +
          `*Total Payable:* *Rs. ${orderTotal.toFixed(0)}*\n\n` +
          `Please confirm my order!`;
          
        const encodedText = encodeURIComponent(messageText);
        const waUrl = `https://wa.me/${whatsappNumber}?text=${encodedText}`;
        
        clearCart();
        window.open(waUrl, '_blank');
        router.replace(`/checkout/success?id=${order.id}`);
      } else {
        clearCart();
        router.replace(`/checkout/success?id=${order.id}`);
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to place order. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  const subtotal = getCartTotal();
  const grandTotal = Math.max(0, subtotal - couponDiscount + shippingFee);

  return (
    <div className="flex flex-col min-h-screen bg-slate-50/50">
      <Header />

      <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-8">
        
        {/* Title */}
        <section className="flex items-center justify-between border-b border-slate-200 pb-4">
          <div className="text-left">
            <h1 className="text-xl sm:text-2xl font-black text-slate-800 leading-none">Checkout Desk</h1>
            <p className="text-[11px] text-slate-400 mt-1">Review your basket and complete shipping address details</p>
          </div>
          <Link
            href="/shop"
            className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 hover:text-emerald-600 transition-colors border border-slate-200 bg-white px-3 py-1.5 rounded-xl shadow-sm"
          >
            <ArrowLeft className="w-4.5 h-4.5" /> Back to Shop
          </Link>
        </section>

        {/* Errors */}
        {errorMsg && (
          <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-xs font-semibold flex items-center gap-2 text-left">
            <AlertCircle className="w-4 h-4 flex-shrink-0" />
            <span>{errorMsg}</span>
          </div>
        )}

        {/* Minimum Order Warning */}
        {subtotal < minOrder && (
          <div className="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-amber-800 text-xs font-semibold flex items-center gap-2 text-left">
            <AlertCircle className="w-4 h-4 flex-shrink-0" />
            <span>
              Minimum order limit is <strong>Rs. {minOrder}</strong>. Your current subtotal is <strong>Rs. {subtotal}</strong>. Please add more items.
            </span>
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Form */}
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 text-left">
              <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2 pb-3 border-b border-slate-100">
                <MapPin className="w-4 h-4 text-emerald-600" />
                Shipping & Delivery Address
              </h3>

              <form onSubmit={(e) => e.preventDefault()} className="space-y-4">
                {/* Name */}
                <div className="space-y-1">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    Full Name
                  </label>
                  <div className="relative">
                    <input
                      type="text"
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      required
                      placeholder="Enter customer name"
                      className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 font-semibold"
                    />
                    <User className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-slate-400" />
                  </div>
                </div>

                {/* Phone */}
                <div className="space-y-1">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    Mobile Phone Number
                  </label>
                  <div className="relative">
                    <input
                      type="tel"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      required
                      placeholder="e.g. 03033943814"
                      className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 font-mono font-semibold"
                    />
                    <Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-slate-400" />
                  </div>
                </div>

                {/* Address */}
                <div className="space-y-1">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    Delivery Address
                  </label>
                  <div className="relative">
                    <textarea
                      value={address}
                      onChange={(e) => setAddress(e.target.value)}
                      required
                      rows={3}
                      placeholder="Enter complete house address, street details, colony name, and city (Tando Adam)"
                      className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 resize-none font-medium"
                    />
                    <MapPin className="absolute left-3.5 top-4 w-4.5 h-4.5 text-slate-400" />
                  </div>
                </div>

                {/* Notes */}
                <div className="space-y-1">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    Special Delivery Instructions (Optional)
                  </label>
                  <div className="relative">
                    <textarea
                      value={notes}
                      onChange={(e) => setNotes(e.target.value)}
                      rows={2}
                      placeholder="e.g. Call before delivery, deliver in the evening, etc."
                      className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 resize-none font-medium"
                    />
                    <FileText className="absolute left-3.5 top-4 w-4.5 h-4.5 text-slate-400" />
                  </div>
                </div>

                {/* Place Order CTA Buttons */}
                <div className="flex flex-col sm:flex-row gap-3 pt-2">
                  <button
                    type="button"
                    disabled={submitting || subtotal < minOrder || loadingSettings}
                    onClick={() => processCheckout('COD')}
                    className="flex-1 py-3.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold rounded-xl text-xs shadow-md active:scale-[0.99] transition-all flex items-center justify-center gap-1.5 disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none"
                  >
                    <CheckCircle2 className="w-4 h-4" />
                    {submitting ? 'Processing...' : `Place COD Order (Rs. ${grandTotal.toFixed(0)})`}
                  </button>
                  <button
                    type="button"
                    disabled={submitting || subtotal < minOrder || loadingSettings}
                    onClick={() => processCheckout('WhatsApp')}
                    className="flex-1 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs shadow-md shadow-emerald-600/10 active:scale-[0.99] transition-all flex items-center justify-center gap-2.5 disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none"
                  >
                    <svg className="w-4 h-4 fill-current flex-shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.454 5.709 1.455h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Order via WhatsApp
                  </button>
                </div>
              </form>
            </div>
          </div>

          {/* Right Summary */}
          <div className="space-y-6">
            <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4 text-left">
              <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2 pb-3 border-b border-slate-100">
                <ShoppingBag className="w-4 h-4 text-emerald-600" />
                Cart Basket Summary
              </h3>

              <div className="divide-y divide-slate-100 max-h-64 overflow-y-auto pr-1">
                {cart.map((item) => (
                  <div key={item.id} className="py-2.5 flex items-center justify-between gap-3 text-xs">
                    <div className="min-w-0">
                      <strong className="text-slate-800 font-bold block truncate">{item.name}</strong>
                      <span className="text-[10px] text-slate-400 font-mono">Rs. {item.price.toFixed(0)} x {item.quantity}</span>
                    </div>
                    <span className="font-mono font-extrabold text-slate-700">
                      Rs. {(item.price * item.quantity).toFixed(0)}
                    </span>
                  </div>
                ))}
              </div>

              {/* Promo Coupon Applier */}
              <div className="border-t border-slate-100 pt-4 space-y-2 text-left">
                <label className="block text-[9px] font-bold text-slate-500 uppercase tracking-wider">Promo Coupon / ڈسکاؤنٹ کوپن</label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={couponCode}
                    onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                    placeholder="ENTER CODE"
                    className="flex-1 px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 uppercase font-mono font-bold text-slate-800"
                  />
                  <button
                    type="button"
                    onClick={handleApplyCoupon}
                    className="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all active:scale-[0.98]"
                  >
                    Apply
                  </button>
                </div>
                {couponError && <p className="text-[10px] font-semibold text-rose-600">{couponError}</p>}
                {appliedCoupon && (
                  <p className="text-[10px] font-semibold text-emerald-600">
                    🎉 Coupon '{appliedCoupon.code}' applied successfully!
                  </p>
                )}
              </div>

              {/* Pricing Math */}
              <div className="border-t border-slate-100 pt-4 space-y-2 text-xs">
                <div className="flex justify-between text-slate-500">
                  <span>Cart Items Subtotal</span>
                  <span className="font-mono font-bold text-slate-700">Rs. {subtotal.toFixed(0)}</span>
                </div>
                {couponDiscount > 0 && (
                  <div className="flex justify-between text-emerald-600 font-bold">
                    <span>Coupon Discount</span>
                    <span className="font-mono">-Rs. {couponDiscount.toFixed(0)}</span>
                  </div>
                )}
                <div className="flex justify-between text-slate-500">
                  <span>Shipping Fee</span>
                  <span className="font-mono font-bold text-slate-705 flex items-center gap-1">
                    {shippingFee === 0 ? (
                      <span className="text-emerald-600 font-extrabold uppercase">FREE DELIVERY</span>
                    ) : (
                      `Rs. ${shippingFee}`
                    )}
                  </span>
                </div>
                {shippingFee === 0 && (
                  <div className="p-2 bg-emerald-50 text-emerald-700 text-[10px] rounded-lg font-semibold flex items-center gap-1 border border-emerald-100">
                    <Truck className="w-3.5 h-3.5" />
                    <span>Mubarak! Apki pehli order par delivery bilkul Muft (FREE) hai!</span>
                  </div>
                )}
                <div className="border-t border-slate-200 pt-3 flex justify-between text-sm font-extrabold text-slate-800">
                  <span>Total Bill Amount</span>
                  <span className="font-mono text-emerald-600 text-base">Rs. {grandTotal.toFixed(0)}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </main>

      <Footer />
    </div>
  );
}
