'use client';

import React, { useState, useEffect } from 'react';
import { Download, X } from 'lucide-react';

export const PWAInstallPrompt: React.FC = () => {
  const [deferredPrompt, setDeferredPrompt] = useState<any>(null);
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    // 1. Listen for the browser's PWA install prompt event
    const handleBeforeInstallPrompt = (e: Event) => {
      // Prevent default prompt from showing automatically
      e.preventDefault();
      // Store event to trigger it later
      setDeferredPrompt(e);
      
      // Check if user dismissed it in this session
      const isDismissed = sessionStorage.getItem('pwa_prompt_dismissed');
      if (!isDismissed) {
        setIsVisible(true);
      }
    };

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

    // 2. Check if already installed
    window.addEventListener('appinstalled', () => {
      setDeferredPrompt(null);
      setIsVisible(false);
      console.log('PWA successfully installed!');
    });

    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    };
  }, []);

  const handleInstallClick = async () => {
    if (!deferredPrompt) return;

    // Show native browser install prompt dialog
    deferredPrompt.prompt();

    // Wait for user choices
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`PWA install prompt choice: ${outcome}`);

    // Clear prompt state
    setDeferredPrompt(null);
    setIsVisible(false);
  };

  const handleClose = () => {
    setIsVisible(false);
    // Remember dismissal for this session
    sessionStorage.setItem('pwa_prompt_dismissed', 'true');
  };

  if (!isVisible) return null;

  return (
    <div className="fixed top-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-white/95 backdrop-blur-md border border-slate-200 shadow-2xl rounded-2xl p-4 flex items-center justify-between gap-3 z-[99999] animate-in slide-in-from-top duration-300">
      <div className="flex items-center gap-3">
        {/* App Icon Circle */}
        <div className="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white font-extrabold text-sm shadow-md flex-shrink-0">
          HR
        </div>
        <div className="text-left">
          <h4 className="text-xs font-black text-slate-800">Install HR Traders App</h4>
          <p className="text-[10px] text-slate-500 font-semibold leading-normal">
            For faster checkout & instant tracking alerts.
          </p>
        </div>
      </div>

      <div className="flex items-center gap-2">
        <button
          onClick={handleInstallClick}
          className="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition-all shadow-sm hover:shadow active:scale-95 flex items-center gap-1"
        >
          <Download className="w-3 h-3" />
          <span>Install</span>
        </button>
        <button
          onClick={handleClose}
          className="p-1 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-650 transition-colors"
        >
          <X className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
};
