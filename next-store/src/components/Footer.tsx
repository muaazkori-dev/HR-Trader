'use client';

import React from 'react';
import Link from 'next/link';
import { Phone, MapPin, Clock, MessageCircle } from 'lucide-react';

export const Footer: React.FC = () => {
  const currentYear = new Date().getFullYear();

  return (
    <>
      {/* 1. WHATSAPP FLOATING BUTTON */}
      <a
        href="https://wa.me/923033943814?text=Salam%20HR%20Traders,%20mujhse%20ek%20product%252Forder%20ke%20bary%20me%20inquiry%20krni%20thi."
        target="_blank"
        rel="noopener noreferrer"
        className="whatsapp-float hover:scale-110 active:scale-95 transition-all duration-200"
        title="Chat with HR Traders on WhatsApp"
      >
        <MessageCircle className="w-8 h-8 fill-white text-[#25d366]" />
      </a>

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

          {/* Contact Details */}
          <div className="space-y-3 text-left">
            <h3 className="text-[11px] font-extrabold text-white uppercase tracking-wider">Store Location</h3>
            <ul className="space-y-3 text-[11px]">
              <li className="flex gap-2 items-start">
                <MapPin className="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" />
                <span className="leading-relaxed">Toor Colony, Front of Hira Public School, Tando Adam, Sindh</span>
              </li>
              <li className="flex gap-2 items-center">
                <Phone className="w-4 h-4 text-emerald-500 flex-shrink-0" />
                <a href="tel:+923033943814" className="hover:text-white transition-colors">+92 303 3943814</a>
              </li>
            </ul>
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
