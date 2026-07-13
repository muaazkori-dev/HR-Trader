'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/lib/supabase';
import { X } from 'lucide-react';

export const PromoModalAd: React.FC = () => {
  const router = useRouter();
  const [enabled, setEnabled] = useState(false);
  const [imageUrl, setImageUrl] = useState('');
  const [linkUrl, setLinkUrl] = useState('');
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    const checkPromoAd = async () => {
      try {
        // Fetch keys from settings
        const { data, error } = await supabase
          .from('settings')
          .select('key_name, val_value')
          .in('key_name', ['promo_ad_enabled', 'promo_ad_image', 'promo_ad_link']);

        if (error || !data) return;

        let promoEnabled = false;
        let promoImage = '';
        let promoLink = '';

        data.forEach((row) => {
          if (row.key_name === 'promo_ad_enabled') {
            promoEnabled = row.val_value === 'true';
          } else if (row.key_name === 'promo_ad_image') {
            promoImage = row.val_value || '';
          } else if (row.key_name === 'promo_ad_link') {
            promoLink = row.val_value || '';
          }
        });

        if (promoEnabled && promoImage) {
          setEnabled(true);
          setImageUrl(promoImage);
          setLinkUrl(promoLink);

          // Check session storage so it only pops up ONCE per session visit
          const isClosedThisSession = sessionStorage.getItem('promo_ad_closed');
          if (!isClosedThisSession) {
            // Delay popup slightly for premium transition effect
            setTimeout(() => {
              setIsOpen(true);
            }, 800);
          }
        }
      } catch (err) {
        console.error('Failed to load promo ad details:', err);
      }
    };

    checkPromoAd();
  }, []);

  const handleClose = () => {
    setIsOpen(false);
    sessionStorage.setItem('promo_ad_closed', 'true');
  };

  const handleAdClick = () => {
    setIsOpen(false);
    sessionStorage.setItem('promo_ad_closed', 'true');
    if (linkUrl) {
      router.push(linkUrl);
    }
  };

  if (!isOpen || !imageUrl) return null;

  return (
    <div className="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center p-4 z-[999999] animate-in fade-in duration-300">
      
      {/* Modal Container */}
      <div className="relative max-w-lg w-full bg-transparent rounded-3xl overflow-hidden shadow-2xl flex flex-col items-center animate-in zoom-in-95 duration-350">
        
        {/* Floating Close Button */}
        <button
          onClick={handleClose}
          className="absolute -top-1 -right-1 md:top-3 md:right-3 w-10 h-10 bg-slate-900/80 hover:bg-slate-900 text-white rounded-full flex items-center justify-center transition-all z-10 hover:scale-105 active:scale-95 shadow-md border border-white/10"
          aria-label="Close Ad"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Promo Banner Content */}
        <div 
          onClick={handleAdClick}
          className={`w-full overflow-hidden rounded-2xl border border-white/15 shadow-xl transition-transform duration-300 ${
            linkUrl ? 'cursor-pointer hover:scale-[1.01]' : ''
          }`}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img 
            src={imageUrl} 
            alt="Exclusive Promotion" 
            className="w-full h-auto object-cover max-h-[80vh] md:max-h-[75vh]" 
          />
        </div>

        {/* Optional Action Details */}
        {linkUrl && (
          <button
            onClick={handleAdClick}
            className="mt-4 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs tracking-wider rounded-xl transition-all shadow-lg active:scale-95 uppercase"
          >
            Shop Deals Now / ابھی خریدیں
          </button>
        )}

      </div>
    </div>
  );
};
