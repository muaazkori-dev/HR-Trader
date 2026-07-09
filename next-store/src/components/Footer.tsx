'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { Phone, MapPin, Clock, MessageCircle, X } from 'lucide-react';

export const Footer: React.FC = () => {
  const currentYear = new Date().getFullYear();
  const [isWhatsAppOpen, setIsWhatsAppOpen] = useState(false);

  return (
    <>
      {/* 1. WHATSAPP FLOATING SUPPORT WITH SELECTION POPUP */}
      <div className="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
        {isWhatsAppOpen && (
          <div className="w-80 bg-white border border-slate-100 rounded-[2rem] shadow-2xl p-5 animate-in fade-in slide-in-from-bottom-5 duration-200 text-left space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <MessageCircle className="w-5 h-5 text-[#25d366] fill-[#25d366]" />
                <span className="font-extrabold text-slate-800 text-xs tracking-wider uppercase">WhatsApp Support</span>
              </div>
              <button
                onClick={() => setIsWhatsAppOpen(false)}
                className="text-slate-400 hover:text-slate-650 transition-colors p-1"
                aria-label="Close WhatsApp Menu"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            
            <div className="space-y-3">
              {/* Branch 1 */}
              <a
                href="https://wa.me/923033943814?text=Salam%20HR%20Traders,%20mujhse%20ek%20product%252Forder%20ke%20bary%20me%20inquiry%20krni%20thi."
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/30 transition-all group"
              >
                <div className="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0 group-hover:scale-105 transition-transform">
                  <MessageCircle className="w-5 h-5 fill-emerald-600 text-emerald-50" />
                </div>
                <div className="min-w-0">
                  <h4 className="text-xs font-extrabold text-slate-800 leading-tight">Branch 1 (Toor Colony)</h4>
                  <p className="text-[10px] text-slate-400 font-mono mt-0.5">+92 303 3943814</p>
                </div>
              </a>

              {/* Branch 2 */}
              <a
                href="https://wa.me/923137889859?text=Salam%20HR%20Traders,%20mujhse%20ek%20product%252Forder%20ke%20bary%20me%20inquiry%20krni%20thi."
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/30 transition-all group"
              >
                <div className="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0 group-hover:scale-105 transition-transform">
                  <MessageCircle className="w-5 h-5 fill-emerald-600 text-emerald-50" />
                </div>
                <div className="min-w-0">
                  <h4 className="text-xs font-extrabold text-slate-800 leading-tight">Branch 2 (Gulshan-e-Sardar)</h4>
                  <p className="text-[10px] text-slate-400 font-mono mt-0.5">+92 313 7889859</p>
                </div>
              </a>
            </div>
          </div>
        )}

        <button
          onClick={() => setIsWhatsAppOpen(!isWhatsAppOpen)}
          className="whatsapp-float hover:scale-110 active:scale-95 transition-all duration-200 focus:outline-none flex items-center justify-center"
          title="Chat with HR Traders on WhatsApp"
        >
          <MessageCircle className="w-8 h-8 fill-white text-[#25d366]" />
        </button>
      </div>

      {/* 2. FOOTER CONTENT */}
      <footer className="bg-slate-900 border-t border-slate-800 text-slate-400 text-xs py-12 transition-colors duration-300">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
          
          {/* Logo & About */}
          <div className="space-y-4">
            <Link href="/" className="flex items-center gap-2 group">
              <div className="logo-container w-9 h-9 bg-white rounded-lg flex items-center justify-center text-emerald-600 font-extrabold text-base border border-slate-700 shadow-inner">
                HR
              </div>
              <h2 className="text-sm font-extrabold text-white tracking-wide">HR Traders</h2>
            </Link>
            <p className="leading-relaxed text-[11px] text-slate-400 text-left">
              HR Traders provides premium quality groceries, washing solutions, household items, cold drinks, and cosmetics. Serving Tando Adam locally and delivering top standards online.
            </p>
          </div>

          {/* Timings */}
          <div className="space-y-3 text-left">
            <h3 className="text-[11px] font-extrabold text-white uppercase tracking-wider">Business Timings</h3>
            <ul className="space-y-2 text-[11px] font-mono">
              <li className="flex justify-between border-b border-slate-800/80 pb-1.5">
                <span>Saturday - Thursday:</span>
                <span className="text-emerald-500 font-semibold">6:00 AM - 12:00 PM</span>
              </li>
              <li className="flex justify-between border-b border-slate-800/80 pb-1.5">
                <span>Friday:</span>
                <span className="text-emerald-500 font-semibold">6:00 AM - 12:00 PM</span>
              </li>
              <li className="flex justify-between">
                <span>Friday Evening:</span>
                <span className="text-emerald-500 font-semibold">4:00 PM - 12:00 AM</span>
              </li>
            </ul>
          </div>

          {/* Category links */}
          <div className="space-y-3 text-left">
            <h3 className="text-[11px] font-extrabold text-white uppercase tracking-wider">Top Categories</h3>
            <div className="grid grid-cols-2 gap-2 text-[11px]">
              <Link href="/shop?category=anaj" className="hover:text-white transition-colors">Grains & Rice</Link>
              <Link href="/shop?category=shampoo" className="hover:text-white transition-colors">Hair Care</Link>
              <Link href="/shop?category=soap" className="hover:text-white transition-colors">Soaps & Care</Link>
              <Link href="/shop?category=cold_drinks" className="hover:text-white transition-colors">Beverages</Link>
              <Link href="/shop?category=ice_cream" className="hover:text-white transition-colors">Ice Cream</Link>
              <Link href="/shop?category=milk" className="hover:text-white transition-colors">Dairy Milk</Link>
            </div>
          </div>

          {/* Contact Details / Store Locations */}
          <div className="space-y-4 text-left">
            <h3 className="text-[11px] font-extrabold text-white uppercase tracking-wider">Store Locations</h3>
            
            <div className="space-y-3 text-[11px]">
              <div>
                <strong className="text-[10px] font-extrabold text-slate-300 uppercase tracking-wide block mb-1">Branch 1 (Toor Colony)</strong>
                <ul className="space-y-1.5">
                  <li className="flex gap-2 items-start text-slate-400">
                    <MapPin className="w-3.5 h-3.5 text-emerald-500 flex-shrink-0 mt-0.5" />
                    <span>Toor Colony, Front of Hira Public School, Tando Adam</span>
                  </li>
                  <li className="flex gap-2 items-center text-slate-400">
                    <Phone className="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" />
                    <a href="tel:+923033943814" className="hover:text-white transition-colors font-mono font-bold">+92 303 3943814</a>
                  </li>
                </ul>
              </div>

              <div>
                <strong className="text-[10px] font-extrabold text-slate-300 uppercase tracking-wide block mb-1">Branch 2 (Gulshan-e-Sardar)</strong>
                <ul className="space-y-1.5">
                  <li className="flex gap-2 items-start text-slate-400">
                    <MapPin className="w-3.5 h-3.5 text-emerald-500 flex-shrink-0 mt-0.5" />
                    <span>Gulshan-e-Sardar, near Ayoub Hotel, Tando Adam</span>
                  </li>
                  <li className="flex gap-2 items-center text-slate-400">
                    <Phone className="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" />
                    <a href="tel:+923137889859" className="hover:text-white transition-colors font-mono font-bold">+92 313 7889859</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

        </div>

        {/* Copyright */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-slate-800 mt-10 pt-6 text-center text-[10px] text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p>&copy; {currentYear} HR Traders Storefront. All rights reserved.</p>
          <p className="flex items-center gap-1.5">
            Designed for <strong className="text-slate-400">Premium Grocery POS & E-Com</strong>
          </p>
        </div>
      </footer>
    </>
  );
};
