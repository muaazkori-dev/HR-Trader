'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Phone, MapPin, Clock, MessageCircle, X } from 'lucide-react';
import { supabase } from '@/lib/supabase';

export const Footer: React.FC = () => {
  const currentYear = new Date().getFullYear();
  const [isWhatsAppOpen, setIsWhatsAppOpen] = useState(false);
  
  const [settings, setSettings] = useState<Record<string, string>>({
    branch_1_address: 'Toor Colony, Front of Hira Public School, Tando Adam',
    branch_1_phone: '+92 303 3943814',
    branch_1_maps_url: 'https://maps.app.goo.gl/ux1364EzVohtCkby7',
    branch_2_address: 'Gulshan-e-Sardar, near Ayoub Hotel, Tando Adam',
    branch_2_phone: '+92 313 7889859',
    branch_2_maps_url: 'https://maps.app.goo.gl/PP2a4Uey6twZvHCKA?g_st=aw',
    timings_sat_thu: '6:00 AM - 12:00 PM',
    timings_fri: '6:00 AM - 12:00 PM',
    timings_fri_eve: '4:00 PM - 12:00 AM',
    facebook_url: 'https://www.facebook.com/share/19NUvTTDPS/',
    instagram_url: 'https://www.instagram.com/hrtraderstdm?utm_source=qr&igsh=OHNjb2Vpb241ZGdq',
    tiktok_url: 'https://www.tiktok.com/@hr_traders3?_r=1&_t=ZS-97B8A6PrV3p',
    whatsapp_number: '923033943814'
  });

  // Load dynamic configurations on mount
  useEffect(() => {
    const loadSettings = async () => {
      try {
        const { data, error } = await supabase
          .from('settings')
          .select('key_name, val_value');
        if (!error && data) {
          const dict: Record<string, string> = {};
          data.forEach(item => {
            if (item.val_value) {
              dict[item.key_name] = item.val_value;
            }
          });
          setSettings(prev => ({
            ...prev,
            ...dict
          }));
        }
      } catch (err) {
        console.error('Error fetching settings in footer:', err);
      }
    };
    loadSettings();
  }, []);
  
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
                href={`https://wa.me/${settings.branch_1_phone.replace(/[^0-9]/g, '')}?text=Salam%20HR%20Traders,%20mujhse%20ek%2520product%252Forder%2520ke%2520bary%2520me%2520inquiry%2520krni%2520thi.`}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/30 transition-all group"
              >
                <div className="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0 group-hover:scale-105 transition-transform">
                  <MessageCircle className="w-5 h-5 fill-emerald-600 text-emerald-50" />
                </div>
                <div className="min-w-0">
                  <h4 className="text-xs font-extrabold text-slate-800 leading-tight">Branch 1 (Toor Colony)</h4>
                  <p className="text-[10px] text-slate-400 font-mono mt-0.5">{settings.branch_1_phone}</p>
                </div>
              </a>

              {/* Branch 2 */}
              <a
                href={`https://wa.me/${settings.branch_2_phone.replace(/[^0-9]/g, '')}?text=Salam%20HR%20Traders,%20mujhse%20ek%2520product%252Forder%2520ke%2520bary%2520me%2520inquiry%2520krni%2520thi.`}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/30 transition-all group"
              >
                <div className="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0 group-hover:scale-105 transition-transform">
                  <MessageCircle className="w-5 h-5 fill-emerald-600 text-emerald-50" />
                </div>
                <div className="min-w-0">
                  <h4 className="text-xs font-extrabold text-slate-800 leading-tight">Branch 2 (Gulshan-e-Sardar)</h4>
                  <p className="text-[10px] text-slate-400 font-mono mt-0.5">{settings.branch_2_phone}</p>
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
              <h2 className="text-sm font-extrabold text-white tracking-wide">{settings.store_name}</h2>
            </Link>
            <p className="leading-relaxed text-[11px] text-slate-400 text-left">
              {settings.store_name} provides premium quality groceries, washing solutions, household items, cold drinks, and cosmetics. Serving Tando Adam locally and delivering top standards online.
            </p>
            {/* Social Media Links */}
            <div className="flex items-center gap-3.5 pt-2">
              {/* WhatsApp */}
              <a href={`https://wa.me/${settings.whatsapp_number.replace(/[^0-9]/g, '')}`} target="_blank" rel="noopener noreferrer" className="text-slate-400 hover:text-[#25d366] transition-colors" title="WhatsApp Support">
                <svg viewBox="0 0 448 512" className="w-4 h-4 fill-current">
                  <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                </svg>
              </a>
              {/* Facebook */}
              <a href={settings.facebook_url || '#'} target="_blank" rel="noopener noreferrer" className="text-slate-400 hover:text-[#1877f2] transition-colors" title="Facebook">
                <svg viewBox="0 0 24 24" className="w-4 h-4 fill-current">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </a>
              {/* Instagram */}
              <a href={settings.instagram_url || '#'} target="_blank" rel="noopener noreferrer" className="text-slate-400 hover:text-[#e1306c] transition-colors" title="Instagram">
                <svg viewBox="0 0 24 24" className="w-4 h-4 fill-current">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051C.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                </svg>
              </a>
              {/* TikTok */}
              <a href={settings.tiktok_url || '#'} target="_blank" rel="noopener noreferrer" className="text-slate-400 hover:text-[#ff0050] transition-colors" title="TikTok">
                <svg viewBox="0 0 24 24" className="w-4 h-4 fill-current">
                  <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.17-2.86-.74-3.94-1.74-.22-.2-.43-.43-.62-.67-.02 3.22-.01 6.44-.02 9.65-.07 2.44-.9 4.88-2.61 6.57-2.07 2.02-5.14 2.86-7.9 2.18-3.01-.73-5.35-3.41-5.77-6.48-.52-3.8 1.79-7.72 5.56-8.58 1-.22 2.05-.21 3.07-.02v4.09c-1.12-.34-2.38-.2-3.37.52-.99.72-1.52 1.95-1.42 3.19.1 1.25.9 2.4 2.02 2.91 1.11.51 2.47.39 3.47-.33.91-.65 1.39-1.75 1.34-2.87.01-5.56.01-11.12.02-16.68z"/>
                </svg>
              </a>
            </div>
          </div>

          {/* Timings */}
          <div className="space-y-3 text-left">
            <h3 className="text-[11px] font-extrabold text-white uppercase tracking-wider">Business Timings</h3>
            <ul className="space-y-2 text-[11px] font-mono">
              <li className="flex justify-between border-b border-slate-800/80 pb-1.5">
                <span>Saturday - Thursday:</span>
                <span className="text-emerald-500 font-semibold">{settings.timings_sat_thu}</span>
              </li>
              <li className="flex justify-between border-b border-slate-800/80 pb-1.5">
                <span>Friday:</span>
                <span className="text-emerald-500 font-semibold">{settings.timings_fri}</span>
              </li>
              <li className="flex justify-between">
                <span>Friday Evening:</span>
                <span className="text-emerald-500 font-semibold">{settings.timings_fri_eve}</span>
              </li>
            </ul>
          </div>

          {/* Category links */}
          <div className="space-y-3 text-left">
            <h3 className="text-[11px] font-extrabold text-white uppercase tracking-wider">Quick Categories</h3>
            <div className="grid grid-cols-2 gap-2 text-[11px]">
              <Link href="/shop?category=grocery" className="hover:text-white transition-colors">Grocery</Link>
              <Link href="/shop?category=anaj" className="hover:text-white transition-colors">Anaj</Link>
              <Link href="/shop?category=ice_cream" className="hover:text-white transition-colors">Ice Cream</Link>
              <Link href="/shop?category=beverages" className="hover:text-white transition-colors">Beverages</Link>
              <Link href="/shop?category=milk" className="hover:text-white transition-colors">Milk</Link>
              <Link href="/shop?category=cosmetics" className="hover:text-white transition-colors">Cosmetics</Link>
              <Link href="/shop?category=confectionary" className="hover:text-white transition-colors">Snacks</Link>
              <Link href="/shop?category=bakery" className="hover:text-white transition-colors">Bakery</Link>
              <Link href="/shop?category=sauce" className="hover:text-white transition-colors">Sauces</Link>
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
                    <a 
                      href={settings.branch_1_maps_url} 
                      target="_blank" 
                      rel="noopener noreferrer" 
                      className="hover:text-white transition-colors"
                    >
                      {settings.branch_1_address}
                    </a>
                  </li>
                  <li className="flex gap-2 items-center text-slate-400">
                    <Phone className="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" />
                    <a href={`tel:${settings.branch_1_phone.replace(/\s+/g, '')}`} className="hover:text-white transition-colors font-mono font-bold">{settings.branch_1_phone}</a>
                  </li>
                </ul>
              </div>

              <div>
                <strong className="text-[10px] font-extrabold text-slate-300 uppercase tracking-wide block mb-1">Branch 2 (Gulshan-e-Sardar)</strong>
                <ul className="space-y-1.5">
                  <li className="flex gap-2 items-start text-slate-400">
                    <MapPin className="w-3.5 h-3.5 text-emerald-500 flex-shrink-0 mt-0.5" />
                    <a 
                      href={settings.branch_2_maps_url} 
                      target="_blank" 
                      rel="noopener noreferrer" 
                      className="hover:text-white transition-colors"
                    >
                      {settings.branch_2_address}
                    </a>
                  </li>
                  <li className="flex gap-2 items-center text-slate-400">
                    <Phone className="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" />
                    <a href={`tel:${settings.branch_2_phone.replace(/\s+/g, '')}`} className="hover:text-white transition-colors font-mono font-bold">{settings.branch_2_phone}</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

        </div>

        {/* Copyright */}
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-slate-800 mt-10 pt-6 text-center text-[10px] text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p>&copy; {currentYear} {settings.store_name} Storefront. All rights reserved.</p>
          <p className="flex items-center gap-1.5">
            Designed for <strong className="text-slate-400">Premium Grocery POS & E-Com</strong>
          </p>
        </div>
      </footer>
    </>
  );
};
