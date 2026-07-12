'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Phone, MapPin, Clock, MessageCircle, X } from 'lucide-react';

export const Footer: React.FC = () => {
  const currentYear = new Date().getFullYear();
  const [isWhatsAppOpen, setIsWhatsAppOpen] = useState(false);
  
  // Draggable position state
  const [position, setPosition] = useState({ x: 0, y: 0 });
  const [isDragging, setIsDragging] = useState(false);
  const [hasMoved, setHasMoved] = useState(false);
  const [windowWidth, setWindowWidth] = useState(0);

  // Initialize position and window width on client
  useEffect(() => {
    if (typeof window !== 'undefined') {
      setWindowWidth(window.innerWidth);
      // Start in the bottom right corner (approx 24px from bottom, 24px from right)
      setPosition({
        x: window.innerWidth - 80,
        y: window.innerHeight - 100
      });

      const handleResize = () => {
        setWindowWidth(window.innerWidth);
        setPosition((prev) => {
          // Adjust position on resize to keep it on screen
          const isLeft = prev.x < window.innerWidth / 2;
          return {
            x: isLeft ? 24 : window.innerWidth - 80,
            y: Math.max(24, Math.min(prev.y, window.innerHeight - 100))
          };
        });
      };
      
      window.addEventListener('resize', handleResize);
      return () => window.removeEventListener('resize', handleResize);
    }
  }, []);

  // Snap to nearest side edge when dragging stops
  useEffect(() => {
    if (!isDragging && position.x !== 0 && typeof window !== 'undefined') {
      const halfWidth = window.innerWidth / 2;
      const snapX = position.x < halfWidth ? 24 : window.innerWidth - 80;
      const snapY = Math.max(24, Math.min(position.y, window.innerHeight - 100));
      setPosition({ x: snapX, y: snapY });
    }
  }, [isDragging]);

  const onMouseDown = (e: React.MouseEvent) => {
    if (e.button !== 0) return; // Only left click
    setIsDragging(true);
    setHasMoved(false);
    
    const offset = {
      x: e.clientX - position.x,
      y: e.clientY - position.y
    };

    const onMouseMove = (moveEvent: MouseEvent) => {
      const x = moveEvent.clientX - offset.x;
      const y = moveEvent.clientY - offset.y;
      
      const clampedX = Math.max(16, Math.min(x, window.innerWidth - 80));
      const clampedY = Math.max(16, Math.min(y, window.innerHeight - 80));
      
      setPosition({ x: clampedX, y: clampedY });
      setHasMoved(true);
    };

    const onMouseUp = () => {
      window.removeEventListener('mousemove', onMouseMove);
      window.removeEventListener('mouseup', onMouseUp);
      setIsDragging(false);
    };

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
  };

  const onTouchStart = (e: React.TouchEvent) => {
    const touch = e.touches[0];
    setIsDragging(true);
    setHasMoved(false);
    
    const offset = {
      x: touch.clientX - position.x,
      y: touch.clientY - position.y
    };

    const onTouchMove = (moveEvent: TouchEvent) => {
      const touchMove = moveEvent.touches[0];
      const x = touchMove.clientX - offset.x;
      const y = touchMove.clientY - offset.y;
      
      const clampedX = Math.max(16, Math.min(x, window.innerWidth - 80));
      const clampedY = Math.max(16, Math.min(y, window.innerHeight - 80));
      
      setPosition({ x: clampedX, y: clampedY });
      setHasMoved(true);
    };

    const onTouchEnd = () => {
      window.removeEventListener('touchmove', onTouchMove);
      window.removeEventListener('touchend', onTouchEnd);
      setIsDragging(false);
    };

    window.addEventListener('touchmove', onTouchMove);
    window.addEventListener('touchend', onTouchEnd);
  };

  const handleButtonClick = () => {
    if (!hasMoved) {
      setIsWhatsAppOpen(!isWhatsAppOpen);
    }
  };

  // Determine if widget is on the left side of the screen
  const isLeft = position.x < (windowWidth / 2 || 600);

  return (
    <>
      {/* 1. WHATSAPP FLOATING SUPPORT WITH SELECTION POPUP */}
      <div 
        className="fixed z-50 flex flex-col gap-3 select-none"
        style={{
          left: position.x || 'auto',
          top: position.y || 'auto',
          bottom: position.x ? 'auto' : '24px',
          right: position.x ? 'auto' : '24px',
          // Smooth spring animation when not dragging (for snapping effect)
          transition: isDragging ? 'none' : 'left 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28), top 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28)',
          alignItems: isLeft ? 'flex-start' : 'flex-end'
        }}
      >
        {isWhatsAppOpen && (
          <div 
            className={`w-80 bg-white border border-slate-100 rounded-[2rem] shadow-2xl p-5 animate-in fade-in slide-in-from-bottom-5 duration-200 text-left space-y-4 absolute bottom-16 ${
              isLeft ? 'left-0 origin-bottom-left' : 'right-0 origin-bottom-right'
            }`}
          >
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2">
                {/* Official WhatsApp Green Logo SVG */}
                <svg viewBox="0 0 448 512" className="w-5 h-5 text-[#25d366] fill-current">
                  <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                </svg>
                <span className="font-extrabold text-slate-800 text-xs tracking-wider uppercase">WhatsApp Support</span>
              </div>
              <button
                onClick={() => setIsWhatsAppOpen(false)}
                className="text-slate-400 hover:text-slate-600 transition-colors p-1"
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

        {/* Floating Trigger Button */}
        <button
          onMouseDown={onMouseDown}
          onTouchStart={onTouchStart}
          onClick={handleButtonClick}
          style={{ touchAction: 'none' }}
          className="w-14 h-14 bg-[#25d366] hover:bg-[#20ba59] active:scale-95 transition-all duration-200 focus:outline-none flex items-center justify-center rounded-full shadow-2xl cursor-grab active:cursor-grabbing border border-white/20 select-none group"
          title="Chat with HR Traders on WhatsApp"
        >
          {/* Official WhatsApp Logo SVG */}
          <svg viewBox="0 0 448 512" className="w-7 h-7 fill-white transform group-hover:scale-110 transition-transform duration-250">
            <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
          </svg>
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
