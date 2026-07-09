'use client';

import React, { useEffect } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
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
  UserCheck
} from 'lucide-react';

export default function AdminLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const router = useRouter();
  const pathname = usePathname();
  const { user, profile, loading, signOut } = useAuth();

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

    </div>
  );
}
