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

  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault();
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
      const orderTotal = subtotal + shippingFee;
      const { data: order, error: orderErr } = await supabase
        .from('orders')
        .insert([
          {
            customer_id: user?.id || null,
            customer_name: name.trim(),
            customer_phone: phone.trim(),
            customer_address: address.trim(),
            total_amount: orderTotal,
            payment_method: 'COD',
            status: 'pending',
            notes: notes.trim() || null,
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

      // 4. Success! Clear cart and redirect
      clearCart();
      router.replace(`/checkout/success?id=${order.id}`);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to place order. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  const subtotal = getCartTotal();
  const grandTotal = subtotal + shippingFee;

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

              <form onSubmit={handlePlaceOrder} className="space-y-4">
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

                {/* Place Order CTA */}
                <button
                  type="submit"
                  disabled={submitting || subtotal < minOrder || loadingSettings}
                  className="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs shadow-lg shadow-emerald-600/10 active:scale-[0.99] transition-all flex items-center justify-center gap-1.5 disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none"
                >
                  <CheckCircle2 className="w-4 h-4" />
                  {submitting ? 'Processing Order...' : `Place Cash on Delivery Order (Rs. ${grandTotal.toFixed(0)})`}
                </button>
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

              {/* Pricing Math */}
              <div className="border-t border-slate-100 pt-4 space-y-2 text-xs">
                <div className="flex justify-between text-slate-500">
                  <span>Cart Items Subtotal</span>
                  <span className="font-mono font-bold text-slate-700">Rs. {subtotal.toFixed(0)}</span>
                </div>
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
