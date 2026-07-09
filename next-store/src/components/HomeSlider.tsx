'use client';

import React, { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight, GlassWater, IceCream, ShoppingBag } from 'lucide-react';

const SLIDES = [
  {
    id: 1,
    tag: 'Premium Choice',
    title: 'Your Premium Grocery Partner',
    desc: 'Fresh organic crops, groceries, and premium household brands delivered straight to your home.',
    link: '/shop',
    image: '/assets/images/hero_grocery_banner.png',
    theme: 'emerald'
  },
  {
    id: 2,
    tag: 'Beat The Heat',
    title: 'Quench Your Thirst',
    desc: 'Soft drinks, juices, mineral water bottles, and energy drinks delivered straight to your doorstep ice cold.',
    link: '/shop?category=cold_drinks',
    image: null,
    theme: 'teal'
  },
  {
    id: 3,
    tag: 'Frozen Delights',
    title: 'Frozen Ice Creams',
    desc: 'Family pack ice creams and chicken frozen snacks. *Available for nearby locations to maintain cold chain.',
    link: '/shop?category=ice_cream',
    image: null,
    theme: 'cyan'
  }
];

export const HomeSlider: React.FC = () => {
  const [current, setCurrent] = useState(0);
  const [windowWidth, setWindowWidth] = useState(typeof window !== 'undefined' ? window.innerWidth : 1200);

  useEffect(() => {
    const handleResize = () => setWindowWidth(window.innerWidth);
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

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

  const getTranslateX = () => {
    let slideWidth = windowWidth * 0.55; // Desktop width (55vw)
    let gap = 24; // md:gap-6
    if (windowWidth < 640) {
      slideWidth = windowWidth * 0.82; // Mobile width (82vw)
      gap = 16; // gap-4
    } else if (windowWidth < 1024) {
      slideWidth = windowWidth * 0.65; // Tablet width (65vw)
      gap = 24; // md:gap-6
    }
    // Calculate translation to perfectly center the current slide
    const offset = (windowWidth / 2) - (current * (slideWidth + gap) + slideWidth / 2);
    return offset;
  };

  return (
    <section className="relative bg-slate-50/50 py-8 overflow-hidden border-b border-slate-200/50 w-full text-left">
      <div className="slider-container max-w-7xl mx-auto relative px-4 overflow-visible">
        {/* Track holding the slides */}
        <div 
          className="slider-track flex gap-4 md:gap-6 transition-transform duration-500 ease-out items-center" 
          style={{ 
            width: 'max-content',
            transform: `translateX(${getTranslateX()}px)` 
          }}
        >
          {SLIDES.map((slide, idx) => {
            const isActive = idx === current;
            const hasImage = !!slide.image;
            
            // Build theme dynamic styling
            let cardClass = "";
            let tagClass = "";
            let btnClass = "";
            let IconComponent = ShoppingBag;
            let iconClass = "text-emerald-500/10";
            
            if (hasImage) {
              cardClass = "bg-cover bg-center relative text-white";
              tagClass = "bg-emerald-600 text-white";
              btnClass = "bg-emerald-600 hover:bg-emerald-500 text-white";
            } else {
              cardClass = "flex items-center justify-between px-6 sm:px-12 text-slate-800 bg-gradient-to-r";
              if (slide.theme === 'teal') {
                cardClass += " from-slate-100 via-slate-50 to-teal-50/80";
                tagClass = "bg-teal-100 text-teal-700";
                btnClass = "bg-teal-600 hover:bg-teal-500 text-white";
                IconComponent = GlassWater;
                iconClass = "text-teal-600/10";
              } else if (slide.theme === 'cyan') {
                cardClass += " from-slate-100 via-slate-50 to-cyan-50/80";
                tagClass = "bg-cyan-100 text-cyan-700";
                btnClass = "bg-cyan-600 hover:bg-cyan-500 text-white";
                IconComponent = IceCream;
                iconClass = "text-cyan-600/10";
              } else {
                cardClass += " from-slate-100 via-slate-50 to-emerald-50/80";
                tagClass = "bg-emerald-100 text-emerald-700";
                btnClass = "bg-emerald-600 hover:bg-emerald-500 text-white";
                IconComponent = ShoppingBag;
                iconClass = "text-emerald-600/10";
              }
            }

            return (
              <div 
                key={slide.id}
                onClick={() => setCurrent(idx)}
                className={`slide-card flex-shrink-0 w-[82vw] sm:w-[65vw] lg:w-[55vw] h-[240px] sm:h-[350px] rounded-[2rem] overflow-hidden border border-slate-200/60 dark:border-slate-800 transition-all duration-500 cursor-pointer ${cardClass} ${
                  isActive ? 'scale-100 opacity-100 shadow-xl border-emerald-500/20' : 'scale-90 opacity-50 border-slate-200'
                }`}
                style={hasImage ? { backgroundImage: `url(${slide.image})` } : undefined}
              >
                {hasImage ? (
                  <div className="absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/40 to-transparent flex items-center px-6 sm:px-12">
                    <div className="max-w-md space-y-2 sm:space-y-4">
                      <span className={`inline-block ${tagClass} text-[9px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider`}>
                        {slide.tag}
                      </span>
                      <h2 className="text-xl sm:text-3xl lg:text-4xl font-black leading-tight text-white">
                        {slide.title}
                      </h2>
                      <p className="text-[10px] sm:text-xs text-slate-200 leading-relaxed font-normal">
                        {slide.desc}
                      </p>
                      <div>
                        <a 
                          href={slide.link}
                          className={`inline-flex items-center gap-1.5 px-5 py-2.5 ${btnClass} font-bold rounded-xl text-xs transition-all shadow-md`}
                        >
                          Shop Now &rarr;
                        </a>
                      </div>
                    </div>
                  </div>
                ) : (
                  <>
                    <div className="max-w-md space-y-2 sm:space-y-4">
                      <span className={`inline-block ${tagClass} text-[9px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider`}>
                        {slide.tag}
                      </span>
                      <h2 className="text-xl sm:text-3xl lg:text-4xl font-black leading-tight text-slate-900">
                        {slide.title}
                      </h2>
                      <p className="text-[10px] sm:text-xs text-slate-500 leading-relaxed font-normal">
                        {slide.desc}
                      </p>
                      <div>
                        <a 
                          href={slide.link}
                          className={`inline-flex items-center gap-1.5 px-5 py-2.5 ${btnClass} font-bold rounded-xl text-xs transition-all shadow-md`}
                        >
                          Shop Now &rarr;
                        </a>
                      </div>
                    </div>
                    <div className="hidden sm:block pr-4">
                      <IconComponent className={`w-32 h-32 ${iconClass}`} strokeWidth={1} />
                    </div>
                  </>
                )}
              </div>
            );
          })}
        </div>

        {/* Chevron buttons */}
        <button
          onClick={(e) => { e.stopPropagation(); handlePrev(); }}
          className="absolute left-6 top-1/2 -translate-y-1/2 z-20 p-2.5 bg-white border border-slate-200/80 shadow-md text-slate-700 hover:text-emerald-600 rounded-full transition-all hover:scale-105 active:scale-95 focus:outline-none hidden sm:block"
          aria-label="Previous Slide"
        >
          <ChevronLeft className="w-5 h-5" />
        </button>
        <button
          onClick={(e) => { e.stopPropagation(); handleNext(); }}
          className="absolute right-6 top-1/2 -translate-y-1/2 z-20 p-2.5 bg-white border border-slate-200/80 shadow-md text-slate-700 hover:text-emerald-600 rounded-full transition-all hover:scale-105 active:scale-95 focus:outline-none hidden sm:block"
          aria-label="Next Slide"
        >
          <ChevronRight className="w-5 h-5" />
        </button>

        {/* Dot Indicators */}
        <div className="absolute -bottom-6 left-1/2 -translate-x-1/2 z-10 flex gap-2">
          {SLIDES.map((_, idx) => (
            <button
              key={idx}
              onClick={() => setSlide(idx)}
              className={`w-2.5 h-2.5 rounded-full transition-all ${
                idx === current ? 'bg-emerald-600 w-6' : 'bg-slate-300'
              }`}
              aria-label={`Go to slide ${idx + 1}`}
            />
          ))}
        </div>
      </div>
    </section>
  );

  function setSlide(index: number) {
    setCurrent(index);
  }
};
