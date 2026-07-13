'use client';

import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/supabase';
import { X, ShieldCheck, BellRing, Settings } from 'lucide-react';

export const CookieConsentBanner: React.FC = () => {
  const [isVisible, setIsVisible] = useState(false);
  const [isClient, setIsClient] = useState(false);

  useEffect(() => {
    setIsClient(true);
    // Check if consent has already been given
    const consent = localStorage.getItem('cookie_consent');
    if (!consent) {
      // Show banner after 1.5 seconds delay for premium experience
      const timer = setTimeout(() => {
        setIsVisible(true);
      }, 1500);
      return () => clearTimeout(timer);
    }
  }, []);

  const handleAcceptAll = async () => {
    setIsVisible(false);
    localStorage.setItem('cookie_consent', 'accepted');

    // 1. Log visitor analytics details
    try {
      await fetch('/api/log-visitor', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });
    } catch (err) {
      console.error('Failed to log visitor metrics:', err);
    }

    // 2. Request Push Notifications permission
    if ('Notification' in window) {
      try {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
          await registerPushNotification();
        }
      } catch (err) {
        console.error('Notification permission request failed:', err);
      }
    }
  };

  const registerPushNotification = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

    try {
      const registration = await navigator.serviceWorker.register('/sw.js');
      
      // Load public VAPID key
      const { data: keyData } = await supabase
        .from('settings')
        .select('val_value')
        .eq('key_name', 'vapid_public_key')
        .single();

      const publicKey = keyData?.val_value;
      if (!publicKey) {
        console.warn('VAPID public key not configured yet.');
        return;
      }

      // Subscribe to Push Service
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: publicKey
      });

      // Insert subscription into database
      const { error } = await supabase
        .from('push_subscriptions')
        .insert({
          subscription: subscription
        });

      if (error) throw error;

      // Save endpoint locally to link with customer phone number on order placement
      localStorage.setItem('push_subscription_endpoint', subscription.endpoint);
    } catch (err) {
      console.error('Failed to subscribe device to Web Push notifications:', err);
    }
  };

  const handleClose = () => {
    setIsVisible(false);
    // Suppress showing again for this browser session only
    sessionStorage.setItem('cookie_banner_dismissed', 'true');
  };

  // Only render on client side to avoid SSR hydration mismatches
  if (!isClient || !isVisible) return null;

  return (
    <div className="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:max-w-md z-50 animate-in fade-in slide-in-from-bottom-10 duration-500">
      <div className="bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-3xl p-5 shadow-2xl space-y-4">
        
        {/* Header Icon & Title */}
        <div className="flex items-start justify-between gap-3 text-left">
          <div className="flex gap-3">
            <div className="w-10 h-10 rounded-full bg-emerald-50 border border-emerald-150 flex items-center justify-center text-emerald-600 flex-shrink-0">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <div>
              <h3 className="font-extrabold text-slate-800 text-xs uppercase tracking-wider">Privacy & Alerts Cookie Consent</h3>
              <p className="urdu-text font-bold text-[13px] text-emerald-700 mt-0.5 tracking-wide leading-none">پرائیویسی اور نوٹیفیکیشن کی اجازت</p>
            </div>
          </div>
          <button 
            onClick={handleClose} 
            className="text-slate-400 hover:text-slate-650 transition-colors p-1"
            aria-label="Dismiss banner"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Informational details text */}
        <div className="text-[11px] text-slate-500 leading-normal text-left space-y-1.5 font-normal">
          <p>
            We use cookie records to log visitor traffic analytics. If you accept cookies, your general device metadata will be recorded, and we will request permission for browser push notifications.
          </p>
          <p className="urdu-text text-[12px] text-slate-600 font-semibold leading-relaxed tracking-wide">
            ہم ویب سائٹ ٹریفک کو مانیٹر کرنے کے لئے کوکیز استعمال کرتے ہیں۔ جب آپ قبول کریں گے، آپ کا ڈیٹا محفوظ ہو جائے گا اور پارسل اسٹیٹس (ڈسپیچ اور ڈیلیوری) کے نوٹیفیکیشنز آپ کے براؤزر پر بھیجے جائیں گے۔
          </p>
        </div>

        {/* Call to Actions buttons */}
        <div className="flex flex-col sm:flex-row gap-2 pt-1.5">
          <button
            onClick={handleAcceptAll}
            className="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-[10px] uppercase tracking-wider rounded-xl shadow-md flex items-center justify-center gap-1.5 transition-all"
          >
            <BellRing className="w-3.5 h-3.5" />
            Accept & Receive Alerts
          </button>
          <button
            onClick={handleClose}
            className="py-2.5 px-4 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 font-extrabold text-[10px] uppercase tracking-wider rounded-xl flex items-center justify-center gap-1.5 transition-all"
          >
            <Settings className="w-3.5 h-3.5" />
            Reject
          </button>
        </div>

      </div>
    </div>
  );
};
