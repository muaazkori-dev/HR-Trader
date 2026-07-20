'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';
import { 
  Home, 
  ShoppingBag, 
  ClipboardList, 
  Settings, 
  LogOut, 
  ShieldCheck, 
  Menu,
  ChevronRight,
  TrendingUp,
  Barcode,
  UserCheck,
  Layers,
  Sparkles,
  Ticket,
  X
} from 'lucide-react';

export default function AdminLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const router = useRouter();
  const pathname = usePathname();
  const { user, profile, loading, signOut } = useAuth();

  const [newOrderNotification, setNewOrderNotification] = useState<{
    id: number;
    customer_name: string;
    total_amount: number;
  } | null>(null);

  const playChime = () => {
    try {
      const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext;
      if (!AudioContextClass) return;
      const ctx = new AudioContextClass();
      
      const osc1 = ctx.createOscillator();
      const gain1 = ctx.createGain();
      osc1.type = 'sine';
      osc1.frequency.setValueAtTime(587.33, ctx.currentTime);
      gain1.gain.setValueAtTime(0.08, ctx.currentTime);
      gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
      osc1.connect(gain1);
      gain1.connect(ctx.destination);
      osc1.start();
      osc1.stop(ctx.currentTime + 0.4);

      const osc2 = ctx.createOscillator();
      const gain2 = ctx.createGain();
      osc2.type = 'sine';
      osc2.frequency.setValueAtTime(880, ctx.currentTime + 0.12);
      gain2.gain.setValueAtTime(0.08, ctx.currentTime + 0.12);
      gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.62);
      osc2.connect(gain2);
      gain2.connect(ctx.destination);
      osc2.start(ctx.currentTime + 0.12);
      osc2.stop(ctx.currentTime + 0.62);
    } catch (err) {
      console.error(err);
    }
  };

  const [lastOrderId, setLastOrderId] = useState<number | null>(null);

  useEffect(() => {
    if (!user || (profile?.role !== 'owner' && profile?.role !== 'manager')) return;

    // 1. Initialize last seen order ID
    const initLatestOrder = async () => {
      try {
        const { data, error } = await supabase
          .from('orders')
          .select('id')
          .order('id', { ascending: false })
          .limit(1);
        if (!error && data && data.length > 0) {
          setLastOrderId(data[0].id);
        }
      } catch (e) {
        console.error(e);
      }
    };
    initLatestOrder();

    // 2. Real-time instant websocket listener
    const channel = supabase
      .channel('schema-insert-order')
      .on(
        'postgres_changes',
        {
          event: 'INSERT',
          schema: 'public',
          table: 'orders',
        },
        (payload) => {
          if (payload.new) {
            setLastOrderId(payload.new.id);
            setNewOrderNotification({
              id: payload.new.id,
              customer_name: payload.new.customer_name || 'Guest',
              total_amount: payload.new.total_amount || 0,
            });
            playChime();
          }
        }
      )
      .subscribe();

    return () => {
      supabase.removeChannel(channel);
    };
  }, [user, profile]);

  // 3. Fallback polling loop (Runs if real-time websockets are disabled or fail)
  useEffect(() => {
    if (!user || (profile?.role !== 'owner' && profile?.role !== 'manager') || !lastOrderId) return;

    const interval = setInterval(async () => {
      try {
        const { data, error } = await supabase
          .from('orders')
          .select('id, customer_name, total_amount')
          .order('id', { ascending: false })
          .limit(1);

        if (!error && data && data.length > 0) {
          const latestId = data[0].id;
          if (latestId > lastOrderId) {
            setLastOrderId(latestId);
            setNewOrderNotification({
              id: latestId,
              customer_name: data[0].customer_name || 'Guest',
              total_amount: data[0].total_amount || 0,
            });
            playChime();
          }
        }
      } catch (e) {
        console.error(e);
      }
    }, 15000); // 15s fallback poll

    return () => clearInterval(interval);
  }, [user, profile, lastOrderId]);

  // Guard: Redirect unauthorized users
  useEffect(() => {
    if (!loading) {
      if (!user) {
        router.replace('/login');
      } else if (profile?.role !== 'owner' && profile?.role !== 'manager') {
        // Not authorized
        router.replace('/');
      }
    }
  }, [user, profile, loading, router]);

  if (loading || (!user && profile?.role !== 'owner' && profile?.role !== 'manager')) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 text-slate-400 font-semibold text-xs">
        Verifying Admin Access...
      </div>
    );
  }

  const navLinks = [
    { href: '/admin', label: 'Dashboard Overview', icon: TrendingUp },
    { href: '/admin/pos', label: 'POS Billing Desk', icon: Barcode },
    { href: '/admin/products', label: 'Inventory Catalog', icon: ShoppingBag },
    { href: '/admin/categories', label: 'Categories Desk', icon: Layers },
    { href: '/admin/banners', label: 'Hero Banners Desk', icon: Sparkles },
    { href: '/admin/coupons', label: 'Coupons Desk', icon: Ticket },
    { href: '/admin/orders', label: 'Order Register', icon: ClipboardList },
    { href: '/admin/demands', label: 'Customer Demands', icon: ClipboardList },
    ...(profile?.role === 'owner' ? [{ href: '/admin/staff', label: 'Staff Management', icon: UserCheck }] : []),
    { href: '/admin/settings', label: 'System Settings', icon: Settings },
  ];

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col md:flex-row">
      
      {/* 1. SIDEBAR NAVIGATION PANEL */}
      <aside className="w-full md:w-64 bg-slate-900 border-r border-slate-800 text-slate-400 flex flex-col justify-between flex-shrink-0">
        <div className="p-5 space-y-6">
          {/* Header */}
          <div className="flex items-center gap-2 pb-5 border-b border-slate-800">
            <div className="w-9 h-9 bg-emerald-600 rounded-xl flex items-center justify-center text-white font-extrabold text-base shadow-md">
              HR
            </div>
            <div className="text-left">
              <h2 className="text-xs font-black text-white uppercase tracking-wider">HR Traders</h2>
              <span className="text-[9px] text-emerald-500 font-mono tracking-widest uppercase flex items-center gap-1 font-bold">
                <ShieldCheck className="w-3 h-3" />
                Admin Panel
              </span>
            </div>
          </div>

          {/* Links */}
          <nav className="space-y-1.5 text-left">
            {navLinks.map((link) => {
              const Icon = link.icon;
              const isActive = pathname === link.href;
              return (
                <Link
                  key={link.href}
                  href={link.href}
                  className={`w-full px-4 py-3 rounded-xl text-xs font-bold flex items-center justify-between group transition-all ${
                    isActive
                      ? 'bg-emerald-600 text-white shadow-md'
                      : 'hover:bg-slate-800 hover:text-white'
                  }`}
                >
                  <div className="flex items-center gap-2.5">
                    <Icon className="w-4.5 h-4.5" />
                    <span>{link.label}</span>
                  </div>
                  <ChevronRight className={`w-3.5 h-3.5 transition-transform ${
                    isActive ? 'translate-x-0.5' : 'opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5'
                  }`} />
                </Link>
              );
            })}
          </nav>
        </div>

        {/* Footer actions */}
        <div className="p-4 border-t border-slate-800 space-y-2">
          <div className="px-4 py-2 text-left">
            <span className="text-[9px] text-slate-500 uppercase font-extrabold block">Active User</span>
            <span className="text-xs font-bold text-slate-350 block truncate">{profile?.name}</span>
            <span className="text-[10px] text-slate-500 block capitalize font-semibold">{profile?.role} mode</span>
          </div>

          <Link
            href="/"
            className="w-full px-4 py-2.5 rounded-xl text-xs font-semibold hover:bg-slate-800 hover:text-white flex items-center gap-2.5 transition-colors text-left"
          >
            <Home className="w-4.5 h-4.5" />
            <span>Storefront View</span>
          </Link>
          
          <button
            onClick={signOut}
            className="w-full px-4 py-2.5 rounded-xl text-xs font-semibold hover:bg-rose-950/20 hover:text-rose-455 text-rose-500 flex items-center gap-2.5 transition-colors text-left"
          >
            <LogOut className="w-4.5 h-4.5" />
            <span>Sign Out</span>
          </button>
        </div>
      </aside>

      {/* 2. MAIN WORKING WORKSPACE */}
      <main className="flex-1 overflow-y-auto p-6 md:p-8 flex flex-col">
        {children}
      </main>

      {/* Real-time Order Alert Toast */}
      {newOrderNotification && (
        <div className="fixed bottom-6 right-6 z-50 bg-slate-900 border border-slate-800 text-white p-4 rounded-2xl shadow-2xl flex flex-col gap-3 animate-in slide-in-from-bottom-5 max-w-sm w-full select-none text-left">
          <div className="flex items-start justify-between gap-4">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 bg-emerald-600 rounded-xl flex items-center justify-center text-white text-base animate-bounce">
                🔔
              </div>
              <div>
                <h4 className="text-xs font-black uppercase text-emerald-500 tracking-wider">New Order Placed!</h4>
                <p className="text-xs font-bold mt-0.5">Order #HRT-{String(newOrderNotification.id).padStart(5, '0')}</p>
              </div>
            </div>
            <button 
              onClick={() => setNewOrderNotification(null)}
              className="p-1 text-slate-400 hover:text-white transition-colors animate-in"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
          <div className="text-xs text-slate-350">
            Received a new COD order from <strong className="text-white font-bold">{newOrderNotification.customer_name}</strong> for <strong className="text-emerald-550 font-mono font-bold">Rs. {newOrderNotification.total_amount.toFixed(0)}</strong>.
          </div>
          <div className="flex items-center gap-2 mt-1">
            <Link
              href="/admin/orders"
              onClick={() => setNewOrderNotification(null)}
              className="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-center text-[10px] uppercase rounded-xl transition-all shadow-md"
            >
              Open Orders Register
            </Link>
            <button
              onClick={() => setNewOrderNotification(null)}
              className="px-3 py-2 bg-slate-850 hover:bg-slate-800 active:scale-95 text-slate-350 font-semibold text-center text-[10px] uppercase rounded-xl transition-all"
            >
              Dismiss
            </button>
          </div>
        </div>
      )}

    </div>
  );
}
