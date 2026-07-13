'use client';

import React, { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { 
  ShoppingBag, 
  Search, 
  User as UserIcon, 
  LogOut, 
  Clock, 
  Menu, 
  X, 
  Plus, 
  Minus, 
  Trash2, 
  ChevronRight,
  ClipboardList,
  Megaphone
} from 'lucide-react';
import { useCart } from '@/context/CartContext';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';

const PLACEHOLDERS = [
  'Search for "basmati grains"...',
  'Search for "chocolate ice cream"...',
  'Search for "cold soft drinks"...',
  'Search for "washing powders"...',
  'Search for "hair shampoo"...',
  'Search for "body soaps"...',
  'Search for "daily cosmetics"...',
  'Search for "dal and grains"...'
];

interface ProductSuggestion {
  id: number;
  name: string;
  price: number;
  image: string;
  category: string;
  barcode: string;
}

export const Header: React.FC = () => {
  const router = useRouter();
  const { cart, addToCart, removeFromCart, updateQuantity, getCartTotal, getCartCount, clearCart } = useCart();
  const { user, profile, signOut } = useAuth();
  
  // Header state
  const [announcement, setAnnouncement] = useState('Welcome to HR Traders!');
  const [timings, setTimings] = useState('6:00 AM - 12:00 PM');
  const [searchQuery, setSearchQuery] = useState('');
  const [suggestions, setSuggestions] = useState<ProductSuggestion[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isProfileDropdownOpen, setIsProfileDropdownOpen] = useState(false);
  const [isDemandModalOpen, setIsDemandModalOpen] = useState(false);
  const [demandName, setDemandName] = useState('');
  const [demandPhone, setDemandPhone] = useState('');
  const [demandDetails, setDemandDetails] = useState('');
  const [isSubmittingDemand, setIsSubmittingDemand] = useState(false);
  const [demandSuccess, setDemandSuccess] = useState(false);
  const [currentPlaceholder, setCurrentPlaceholder] = useState('');

  // Typewriter placeholder animation
  useEffect(() => {
    let isMounted = true;
    let wordIdx = 0;
    let charIdx = 0;
    let isDeleting = false;
    let delay = 100;

    const tick = () => {
      if (!isMounted) return;
      
      const fullText = PLACEHOLDERS[wordIdx];
      
      if (isDeleting) {
        setCurrentPlaceholder(fullText.substring(0, charIdx - 1));
        charIdx--;
        delay = 40;
      } else {
        setCurrentPlaceholder(fullText.substring(0, charIdx + 1));
        charIdx++;
        delay = 100;
      }

      if (!isDeleting && charIdx === fullText.length) {
        isDeleting = true;
        delay = 2000;
      } else if (isDeleting && charIdx === 0) {
        isDeleting = false;
        wordIdx = (wordIdx + 1) % PLACEHOLDERS.length;
        delay = 300;
      }

      setTimeout(tick, delay);
    };

    const timer = setTimeout(tick, 100);
    return () => {
      isMounted = false;
      clearTimeout(timer);
    };
  }, []);

  const searchRef = useRef<HTMLDivElement>(null);
  const profileRef = useRef<HTMLDivElement>(null);

  // Fetch header announcement & timings from Supabase
  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const { data: annData } = await supabase
          .from('settings')
          .select('val_value')
          .eq('key_name', 'homepage_announcement')
          .single();
        if (annData?.val_value) setAnnouncement(annData.val_value);

        const { data: timeData } = await supabase
          .from('settings')
          .select('val_value')
          .eq('key_name', 'shop_timings')
          .single();
        if (timeData?.val_value) {
          try {
            const parsed = JSON.parse(timeData.val_value);
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const today = days[new Date().getDay()];
            setTimings(parsed[today] || '6:00 AM - 12:00 PM');
          } catch {
            setTimings(timeData.val_value);
          }
        }
      } catch (err) {
        console.error('Error fetching settings:', err);
      }
    };
    fetchSettings();
  }, []);

  // Debounced search query suggestion fetch
  useEffect(() => {
    if (searchQuery.trim().length < 1) {
      setSuggestions([]);
      return;
    }

    const delayDebounce = setTimeout(async () => {
      try {
        const cleanQuery = searchQuery.trim().replace(/\s+/, '%');
        const { data, error } = await supabase
          .from('products')
          .select('id, name, price, image, category, barcode')
          .or(`name.ilike.%${cleanQuery}%,category.ilike.%${cleanQuery}%,barcode.ilike.%${cleanQuery}%`)
          .limit(6);

        if (!error && data) {
          setSuggestions(data as ProductSuggestion[]);
        }
      } catch (err) {
        console.error('Search query error:', err);
      }
    }, 200);

    return () => clearTimeout(delayDebounce);
  }, [searchQuery]);

  // Click outside handlers
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(e.target as Node)) {
        setShowSuggestions(false);
      }
      if (profileRef.current && !profileRef.current.contains(e.target as Node)) {
        setIsProfileDropdownOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Listen for bottom navigation custom events
  useEffect(() => {
    const handleOpenDemand = () => setIsDemandModalOpen(true);
    const handleOpenCart = () => setIsCartOpen(true);
    window.addEventListener('open-demand-modal', handleOpenDemand);
    window.addEventListener('open-cart-drawer', handleOpenCart);
    return () => {
      window.removeEventListener('open-demand-modal', handleOpenDemand);
      window.removeEventListener('open-cart-drawer', handleOpenCart);
    };
  }, []);

  const handleDemandSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!demandName.trim() || !demandPhone.trim() || !demandDetails.trim()) return;

    setIsSubmittingDemand(true);
    try {
      const { error } = await supabase
        .from('product_demands')
        .insert({
          customer_name: demandName.trim(),
          customer_phone: demandPhone.trim(),
          demand_details: demandDetails.trim(),
          status: 'pending'
        });

      if (error) throw error;

      setDemandSuccess(true);
      setDemandName('');
      setDemandPhone('');
      setDemandDetails('');
      setTimeout(() => {
        setDemandSuccess(false);
        setIsDemandModalOpen(false);
      }, 2000);
    } catch (err) {
      console.error('Error submitting demand:', err);
      alert('Failed to submit demand. Please try again.');
    } finally {
      setIsSubmittingDemand(false);
    }
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      setShowSuggestions(false);
      router.push(`/shop?search=${encodeURIComponent(searchQuery.trim())}`);
    }
  };

  const handleSuggestionClick = (productId: number) => {
    setSearchQuery('');
    setSuggestions([]);
    setShowSuggestions(false);
    router.push(`/product/${productId}`);
  };

  return (
    <>
      {/* 1. MAIN HEADER NAVBAR */}
      <header className="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-sm transition-all duration-300">
        <div className="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2 sm:gap-4">
          
          {/* Logo */}
          <Link href="/" className="flex items-center gap-1.5 sm:gap-2 group flex-shrink-0">
            <img 
              src="/assets/images/favicon.png" 
              alt="HR Traders Logo" 
              className="w-10 h-10 object-contain rounded-full shadow-md group-hover:scale-105 transition-transform duration-200"
            />
            <div className="hidden sm:block text-left">
              <h1 className="text-base font-extrabold text-slate-800 leading-none">HR Traders</h1>
              <span className="text-[10px] text-slate-450 font-mono tracking-widest uppercase">Grocery & Care</span>
            </div>
          </Link>

          {/* Autocomplete Search input */}
          <div ref={searchRef} className="relative flex-1 max-w-md">
            <form onSubmit={handleSearchSubmit} className="relative w-full">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => {
                  setSearchQuery(e.target.value);
                  setShowSuggestions(true);
                }}
                onFocus={() => setShowSuggestions(true)}
                placeholder={currentPlaceholder}
                className="w-full bg-slate-50 border border-slate-200 rounded-full py-2 pl-4 pr-10 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
              />
              <button type="submit" className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition-colors">
                <Search className="w-4 h-4" />
              </button>
            </form>

            {/* Suggestions list */}
            {showSuggestions && suggestions.length > 0 && (
              <div className="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-2xl shadow-2xl z-50 max-h-96 overflow-y-auto divide-y divide-slate-100 animate-in fade-in slide-in-from-top-2 duration-200">
                {suggestions.map((p) => (
                  <div
                    key={p.id}
                    onClick={() => handleSuggestionClick(p.id)}
                    className="p-3 hover:bg-slate-50 flex items-center justify-between gap-3 cursor-pointer transition-colors"
                  >
                    <div className="flex items-center gap-3 min-w-0">
                      <img
                        src={p.image ? (p.image.startsWith('http') || p.image.startsWith('/') ? p.image : `/${p.image}`) : '/assets/images/placeholder.svg'}
                        alt={p.name}
                        className="w-9 h-9 object-cover rounded-lg border border-slate-200/80 bg-slate-50 flex-shrink-0"
                      />
                      <div className="min-w-0">
                        <h4 className="text-xs font-semibold text-slate-800 leading-tight truncate">{p.name}</h4>
                        <p className="text-[10px] text-slate-400 font-mono mt-0.5">{p.barcode}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2 flex-shrink-0">
                      <span className="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded-full">
                        Rs. {p.price.toFixed(0)}
                      </span>
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation(); // prevent suggestion redirect click
                           addToCart({
                             id: p.id,
                             name: p.name,
                             price: p.price,
                             image: p.image || ''
                           });
                        }}
                        className="p-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all hover:scale-105 active:scale-95 shadow-sm"
                        title="Add to Cart"
                      >
                        <Plus className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* User actions / Cart */}
          <div className="flex items-center gap-2 sm:gap-4 flex-shrink-0">
            <Link href="/shop" className="hidden lg:inline text-xs font-bold text-slate-600 hover:text-emerald-600 transition-colors">
              Browse Shop
            </Link>

            <button
              onClick={() => setIsDemandModalOpen(true)}
              className="hidden sm:flex px-3 py-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 text-xs font-bold rounded-xl transition-all shadow-sm items-center gap-1.5"
            >
              <ClipboardList className="w-3.5 h-3.5 text-amber-600" />
              Demand Box
            </button>

            {/* User Login/Account */}
            <div ref={profileRef} className="relative hidden sm:block">
              {user ? (
                <>
                  <button
                    onClick={() => setIsProfileDropdownOpen(!isProfileDropdownOpen)}
                    className="flex items-center gap-2 p-1.5 hover:bg-slate-100 rounded-xl transition-colors text-slate-700 text-xs font-semibold"
                  >
                    <div className="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-700 border border-slate-300 font-bold uppercase shadow-sm">
                      {profile?.name?.charAt(0) || user.email?.charAt(0) || 'U'}
                    </div>
                    <span className="hidden sm:inline truncate max-w-[100px]">
                      {profile?.name || 'Customer'}
                    </span>
                  </button>

                  {isProfileDropdownOpen && (
                    <div className="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 py-2 divide-y divide-slate-100 animate-in fade-in slide-in-from-top-1">
                      <div className="px-4 py-2 text-left">
                        <p className="text-xs font-bold text-slate-800 truncate">{profile?.name || 'Customer'}</p>
                        <p className="text-[10px] text-slate-450 truncate mt-0.5">{user.email}</p>
                      </div>
                      <div className="py-1">
                        <Link
                          href="/my-account"
                          onClick={() => setIsProfileDropdownOpen(false)}
                          className="flex items-center gap-2 px-4 py-2 text-xs text-slate-600 hover:bg-slate-50 transition-colors text-left"
                        >
                          <ClipboardList className="w-4 h-4" />
                          My Orders
                        </Link>
                        {profile?.role && ['owner', 'manager'].includes(profile.role) && (
                          <Link
                            href="/admin"
                            onClick={() => setIsProfileDropdownOpen(false)}
                            className="flex items-center gap-2 px-4 py-2 text-xs font-bold text-emerald-600 hover:bg-emerald-50 transition-colors text-left"
                          >
                            <UserIcon className="w-4 h-4" />
                            Admin Panel
                          </Link>
                        )}
                      </div>
                      <div className="py-1">
                        <button
                          onClick={signOut}
                          className="flex items-center gap-2 w-full px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 transition-colors text-left"
                        >
                          <LogOut className="w-4 h-4" />
                          Sign Out
                        </button>
                      </div>
                    </div>
                  )}
                </>
              ) : (
                <Link
                  href="/login"
                  className="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all text-xs font-bold text-slate-700 shadow-sm"
                >
                  <UserIcon className="w-4 h-4" />
                  Sign In
                </Link>
              )}
            </div>

            {/* Shopping Cart button */}
            <button
              onClick={() => setIsCartOpen(true)}
              className="relative p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all shadow-sm group"
            >
              <ShoppingBag className="w-5 h-5 group-hover:scale-105 transition-transform" />
              {getCartCount() > 0 && (
                <span className="absolute -top-1 -right-1 bg-emerald-600 text-white text-[9px] font-extrabold w-5 h-5 rounded-full flex items-center justify-center animate-bounce shadow-md">
                  {getCartCount()}
                </span>
              )}
            </button>
          </div>
        </div>
      </header>

      {/* 2. SUB-HEADER ANNOUNCEMENT BAR (alert under header navbar) */}
      <div className="bg-emerald-600 text-white py-2.5 px-4 text-center text-xs font-semibold tracking-wider relative overflow-hidden flex items-center justify-center gap-2 shadow-sm z-20 print-hidden select-none">
        <Megaphone className="w-3.5 h-3.5 animate-bounce flex-shrink-0" />
        <span>{announcement}</span>
      </div>

      {/* 3. SLIDING MINI-CART DRAWER PANEL */}
      {isCartOpen && (
        <div className="fixed inset-0 z-50 flex justify-end">
          <div
            onClick={() => setIsCartOpen(false)}
            className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
          />

          <div className="relative w-full max-w-md h-full bg-white shadow-2xl flex flex-col z-10 transition-transform">
            {/* Header */}
            <div className="p-4 border-b border-slate-200 flex items-center justify-between">
              <div className="flex items-center gap-2">
                <ShoppingBag className="w-5 h-5 text-emerald-600" />
                <h3 className="font-extrabold text-slate-800 text-sm">Shopping Cart Drawer</h3>
                <span className="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-full">
                  {getCartCount()} items
                </span>
              </div>
              <button
                onClick={() => setIsCartOpen(false)}
                className="p-1.5 hover:bg-slate-100 rounded-lg transition-colors text-slate-500 hover:text-slate-800"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Item list */}
            <div className="flex-1 overflow-y-auto p-4 space-y-4">
              {cart.length === 0 ? (
                <div className="h-full flex flex-col items-center justify-center text-center p-8">
                  <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4 border border-dashed border-slate-300">
                    <ShoppingBag className="w-8 h-8" />
                  </div>
                  <h4 className="font-bold text-slate-800 text-sm">Your Cart is Empty</h4>
                  <p className="text-xs text-slate-400 mt-1 max-w-[200px]">
                    Looks like you haven't added any premium grocery to your basket yet.
                  </p>
                  <button
                    onClick={() => {
                      setIsCartOpen(false);
                      router.push('/shop');
                    }}
                    className="mt-6 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-colors"
                  >
                    Start Shopping
                  </button>
                </div>
              ) : (
                cart.map((item) => (
                  <div
                    key={item.id}
                    className="flex gap-3 p-3 bg-slate-50 border border-slate-200/65 rounded-2xl transition-all"
                  >
                    <img
                      src={item.image ? (item.image.startsWith('http') || item.image.startsWith('/') ? item.image : `/${item.image}`) : '/assets/images/placeholder.svg'}
                      alt={item.name}
                      className="w-16 h-16 object-cover rounded-xl border border-slate-200 bg-white flex-shrink-0"
                    />
                    <div className="flex-1 min-w-0 flex flex-col justify-between">
                      <div className="text-left">
                        <h4 className="text-xs font-bold text-slate-800 truncate">{item.name}</h4>
                        {item.weight && (
                          <span className="text-[10px] text-slate-400 mt-0.5 block">
                            {item.weight} ({item.unit})
                          </span>
                        )}
                      </div>
                      
                      <div className="flex items-center justify-between mt-2">
                        <div className="flex items-center border border-slate-200 bg-white rounded-lg">
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity - 1)}
                            className="p-1 hover:bg-slate-55 text-slate-500 transition-colors"
                          >
                            <Minus className="w-3.5 h-3.5" />
                          </button>
                          <span className="px-2 text-xs font-mono font-bold text-slate-700 min-w-[20px] text-center">
                            {item.quantity}
                          </span>
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity + 1)}
                            className="p-1 hover:bg-slate-55 text-slate-500 transition-colors"
                          >
                            <Plus className="w-3.5 h-3.5" />
                          </button>
                        </div>

                        <div className="flex items-center gap-3">
                          <span className="text-xs font-mono font-extrabold text-slate-800">
                            Rs. {(item.price * item.quantity).toFixed(0)}
                          </span>
                          <button
                            onClick={() => removeFromCart(item.id)}
                            className="text-slate-450 hover:text-rose-605 p-1 rounded-md transition-colors"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>

            {/* Footer calculations */}
            {cart.length > 0 && (
              <div className="p-4 border-t border-slate-200 bg-slate-50/80 space-y-4">
                <div className="space-y-1.5 text-xs text-slate-600 text-left">
                  <div className="flex justify-between">
                    <span>Subtotal</span>
                    <span className="font-mono font-semibold text-slate-800">Rs. {getCartTotal().toFixed(0)}</span>
                  </div>
                  <div className="flex justify-between text-[11px]">
                    <span>Shipping</span>
                    <span className="text-emerald-600 font-bold">Free Shipping</span>
                  </div>
                  <div className="border-t border-slate-200 pt-2 flex justify-between font-extrabold text-sm text-slate-800">
                    <span>Total Amount</span>
                    <span className="font-mono text-emerald-600">Rs. {getCartTotal().toFixed(0)}</span>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <button
                    onClick={clearCart}
                    className="py-2.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl transition-all shadow-sm"
                  >
                    Clear Cart
                  </button>
                  <button
                    onClick={() => {
                      setIsCartOpen(false);
                      router.push('/checkout');
                    }}
                    className="py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-1 shadow-md transition-all group"
                  >
                    Proceed Checkout
                    <ChevronRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
      {/* 4. CUSTOMER DEMAND BOX MODAL */}
      {isDemandModalOpen && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-in fade-in">
          <div className="bg-white border border-slate-100 rounded-3xl shadow-2xl w-full max-w-md p-6 relative overflow-hidden animate-in zoom-in-95 duration-200">
            {/* Header */}
            <div className="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <ClipboardList className="w-5 h-5 text-amber-600 animate-pulse" />
                <h3 className="font-black text-slate-800 text-sm uppercase tracking-wider text-left">Demand Box Register</h3>
              </div>
              <button
                onClick={() => setIsDemandModalOpen(false)}
                className="p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {demandSuccess ? (
              <div className="py-8 text-center space-y-3">
                <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-xl font-bold shadow-md">
                  ✓
                </div>
                <h4 className="text-sm font-bold text-slate-800">Demand Registered!</h4>
                <p className="text-xs text-slate-500 max-w-xs mx-auto">
                  Thank you! We will update our stock status or contact you shortly about this product.
                </p>
              </div>
            ) : (
              <form onSubmit={handleDemandSubmit} className="space-y-4 text-left">
                <p className="text-xs text-slate-500 leading-relaxed">
                  Is there a product you need that we do not have in stock? Drop your name, contact, and product details below!
                </p>

                {/* Name */}
                <div className="space-y-1">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Your Full Name</label>
                  <input
                    type="text"
                    required
                    value={demandName}
                    onChange={(e) => setDemandName(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-amber-500 text-xs font-semibold text-slate-805"
                    placeholder="e.g. Haroon Kori"
                  />
                </div>

                {/* Phone */}
                <div className="space-y-1">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Phone / WhatsApp Number</label>
                  <input
                    type="text"
                    required
                    value={demandPhone}
                    onChange={(e) => setDemandPhone(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-amber-500 text-xs font-semibold text-slate-805"
                    placeholder="e.g. 03337155323"
                  />
                </div>

                {/* Details */}
                <div className="space-y-1">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Product Details (Name, Qty, Brand)</label>
                  <textarea
                    required
                    rows={3}
                    value={demandDetails}
                    onChange={(e) => setDemandDetails(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-amber-500 text-xs font-semibold text-slate-805 resize-none"
                    placeholder="e.g. National Chili Sauce 500ml - 2 bottles"
                  />
                </div>

                {/* Submit button */}
                <button
                  type="submit"
                  disabled={isSubmittingDemand}
                  className="w-full py-2.5 bg-amber-600 hover:bg-amber-700 disabled:bg-slate-300 text-white text-xs font-bold rounded-xl shadow-md transition-all uppercase tracking-wider flex items-center justify-center gap-1.5"
                >
                  {isSubmittingDemand ? 'Registering...' : 'Submit Request'}
                </button>
              </form>
            )}
          </div>
        </div>
      )}
    </>
  );
};
