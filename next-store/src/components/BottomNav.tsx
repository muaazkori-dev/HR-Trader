'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { Home, ShoppingBag, ClipboardList, ShoppingCart, User } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';

export const BottomNav: React.FC = () => {
  const pathname = usePathname();
  const { user } = useAuth();
  const { getCartCount } = useCart();
  const [cartCount, setCartCount] = useState(0);

  // Avoid hydration issues by running cart count on client
  useEffect(() => {
    setCartCount(getCartCount());
  }, [getCartCount()]);

  // Exclude bottom nav on admin routes
  if (!pathname || pathname.startsWith('/admin')) {
    return null;
  }

  const triggerDemandModal = (e: React.MouseEvent) => {
    e.preventDefault();
    window.dispatchEvent(new CustomEvent('open-demand-modal'));
  };

  const triggerCartDrawer = (e: React.MouseEvent) => {
    e.preventDefault();
    window.dispatchEvent(new CustomEvent('open-cart-drawer'));
  };

  const isHomeActive = pathname === '/';
  const isShopActive = pathname === '/shop';
  const isAccountActive = pathname === '/login' || pathname === '/my-account';

  return (
    <div className="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/80 shadow-[0_-4px_12px_rgba(0,0,0,0.05)] md:hidden flex justify-around items-center h-16 pb-safe select-none">
      
      {/* Home Tab */}
      <Link 
        href="/" 
        className={`flex flex-col items-center justify-center flex-1 h-full gap-0.5 transition-all duration-200 ${
          isHomeActive 
            ? 'text-emerald-600 font-extrabold scale-105' 
            : 'text-slate-400 hover:text-slate-600'
        }`}
      >
        <Home className={`w-5 h-5 ${isHomeActive ? 'stroke-[2.5px]' : 'stroke-2'}`} />
        <span className="text-[10px] tracking-wide">Home</span>
      </Link>

      {/* Shop Tab */}
      <Link 
        href="/shop" 
        className={`flex flex-col items-center justify-center flex-1 h-full gap-0.5 transition-all duration-205 ${
          isShopActive 
            ? 'text-emerald-600 font-extrabold scale-105' 
            : 'text-slate-400 hover:text-slate-600'
        }`}
      >
        <ShoppingBag className={`w-5 h-5 ${isShopActive ? 'stroke-[2.5px]' : 'stroke-2'}`} />
        <span className="text-[10px] tracking-wide">Shop</span>
      </Link>

      {/* Demand Tab */}
      <button 
        onClick={triggerDemandModal}
        className="flex flex-col items-center justify-center flex-1 h-full gap-0.5 transition-all duration-200 text-slate-400 hover:text-slate-600 focus:outline-none"
      >
        <ClipboardList className="w-5 h-5 stroke-2" />
        <span className="text-[10px] tracking-wide">Demand</span>
      </button>

      {/* Cart Tab */}
      <button 
        onClick={triggerCartDrawer}
        className="flex flex-col items-center justify-center flex-1 h-full gap-0.5 transition-all duration-200 text-slate-400 hover:text-slate-600 focus:outline-none relative"
      >
        <div className="relative">
          <ShoppingCart className="w-5 h-5 stroke-2" />
          {cartCount > 0 && (
            <span className="absolute -top-1.5 -right-1.5 bg-rose-500 text-white text-[9px] font-extrabold w-4 h-4 rounded-full flex items-center justify-center border border-white shadow-sm">
              {cartCount}
            </span>
          )}
        </div>
        <span className="text-[10px] tracking-wide">Cart</span>
      </button>

      {/* Login / Profile Tab */}
      <Link 
        href={user ? "/my-account" : "/login"} 
        className={`flex flex-col items-center justify-center flex-1 h-full gap-0.5 transition-all duration-200 ${
          isAccountActive 
            ? 'text-emerald-600 font-extrabold scale-105' 
            : 'text-slate-400 hover:text-slate-600'
        }`}
      >
        <User className={`w-5 h-5 ${isAccountActive ? 'stroke-[2.5px]' : 'stroke-2'}`} />
        <span className="text-[10px] tracking-wide">{user ? 'Account' : 'Login'}</span>
      </Link>

    </div>
  );
};
