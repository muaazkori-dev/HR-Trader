'use client';

import React, { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

const SLIDES = [
  {
    id: 1,
    title: 'Premium Basmati Grains & Rice',
    desc: 'High-quality washed split gram pulses, premium Basmati rice, and everyday organic essentials.',
    bg: 'from-emerald-800 to-teal-900',
    image: '/assets/images/hero_grocery_banner.png',
  },
  {
    id: 2,
    title: 'Daily Care & Cosmetics',
    desc: 'Explore softening brand shampoos, germ-protection soaps, and premium skincare products.',
    bg: 'from-rose-700 to-pink-900',
    image: null,
  },
  {
    id: 3,
    title: 'Refreshing Beverages & Cold Drinks',
    desc: 'UHT milk packs, premium chocolate ice cream family packs, and carbonated cold soft drinks.',
    bg: 'from-blue-700 to-indigo-900',
    image: null,
  },
];

export const HomeSlider: React.FC = () => {
  const [current, setCurrent] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrent((prev) => (prev + 1) % SLIDES.length);
    }, 5000);
    return () => clearInterval(timer);
  }, []);

  const handlePrev = () => {
    setCurrent((prev) => (prev - 1 + SLIDES.length) % SLIDES.length);
  };

  const handleNext = () => {
    setCurrent((prev) => (prev + 1) % SLIDES.length);
  };

  return (
    <div className="relative w-full h-[320px] md:h-[400px] overflow-hidden bg-slate-900">
      {SLIDES.map((slide, idx) => {
        const isActive = idx === current;
        return (
          <div
            key={slide.id}
            className={`absolute inset-0 w-full h-full flex items-center transition-opacity duration-1000 ease-in-out ${
              isActive ? 'opacity-100 z-10' : 'opacity-0 z-0'
            }`}
          >
            {/* Background image or gradient */}
            {slide.image ? (
              <div className="absolute inset-0">
                <img
                  src={slide.image}
                  alt={slide.title}
                  className="w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-slate-950/70" />
              </div>
            ) : (
              <div className={`absolute inset-0 bg-gradient-to-r ${slide.bg}`} />
            )}

            {/* Overlay details */}
            <div className="relative z-10 max-w-xl mx-auto px-6 md:px-12 text-center text-white space-y-4">
              <h2 className="text-2xl md:text-4xl font-extrabold tracking-tight leading-tight animate-in slide-in-from-bottom duration-500">
                {slide.title}
              </h2>
              <p className="text-xs md:text-sm text-slate-200/90 leading-relaxed font-normal max-w-md mx-auto">
                {slide.desc}
              </p>
              <div className="pt-2">
                <a
                  href="/shop"
                  className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-all inline-block"
                >
                  Order Now (COD)
                </a>
              </div>
            </div>
          </div>
        );
      })}

      {/* Control Chevron Buttons */}
      <button
        onClick={handlePrev}
        className="absolute left-4 top-1/2 -translate-y-1/2 z-20 p-2 bg-black/30 hover:bg-black/50 text-white rounded-full transition-all focus:outline-none"
      >
        <ChevronLeft className="w-5 h-5" />
      </button>
      <button
        onClick={handleNext}
        className="absolute right-4 top-1/2 -translate-y-1/2 z-20 p-2 bg-black/30 hover:bg-black/50 text-white rounded-full transition-all focus:outline-none"
      >
        <ChevronRight className="w-5 h-5" />
      </button>

      {/* Dot Indicators */}
      <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2">
        {SLIDES.map((_, idx) => (
          <button
            key={idx}
            onClick={() => setCurrent(idx)}
            className={`w-2.5 h-2.5 rounded-full transition-all ${
              idx === current ? 'bg-emerald-500 w-6' : 'bg-white/50'
            }`}
          />
        ))}
      </div>
    </div>
  );
};
