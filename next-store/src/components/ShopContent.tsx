'use client';

import React, { useState, useEffect } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { Search, SlidersHorizontal, Tag, Eye } from 'lucide-react';
import { AddToCartButton } from './AddToCartButton';
import Link from 'next/link';
import Image from 'next/image';

interface Product {
  id: number;
  barcode: string;
  name: string;
  description: string;
  price: number;
  old_price?: number;
  discount_percentage?: number;
  weight?: string;
  unit?: string;
  category: string;
  image: string;
}

interface ShopContentProps {
  initialProducts: Product[];
  categories?: any[];
}

const CATEGORIES = [
  { id: '', name: 'All Categories', icon: '🛍️' },
  { id: 'anaj', name: 'Grains & Rice', icon: '🌾' },
  { id: 'grocery', name: 'Grocery & Oils', icon: '🛒' },
  { id: 'ice_cream', name: 'Ice Creams', icon: '🍦' },
  { id: 'beverages', name: 'Beverages', icon: '🥤' },
  { id: 'milk', name: 'Dairy & Milk', icon: '🥛' },
  { id: 'cosmetics', name: 'Cosmetics', icon: '🧴' },
  { id: 'confectionary', name: 'Confectionary & Snacks', icon: '🍟' },
  { id: 'bakery', name: 'Bakery', icon: '🍞' },
  { id: 'sauce', name: 'Sauces & Pickles', icon: '🥫' },
  { id: 'soap', name: 'Soaps & Hygiene', icon: '🧼' },
  { id: 'toothpaste', name: 'Dental Care', icon: '🪥' },
];

export const ShopContent: React.FC<ShopContentProps> = ({ initialProducts, categories }) => {
  const router = useRouter();
  const searchParams = useSearchParams();

  // Derive categories list from props or fallback to static list
  const categoriesList = categories && categories.length > 0
    ? [
        { id: '', name: 'All Categories', icon: '🛍️' },
        ...categories.map(c => ({
          id: c.id,
          name: c.name,
          icon: c.id === 'anaj' ? '🌾' :
                c.id === 'grocery' ? '🛒' :
                c.id === 'ice_cream' ? '🍦' :
                c.id === 'beverages' ? '🥤' :
                c.id === 'milk' ? '🥛' :
                c.id === 'cosmetics' ? '🧴' :
                c.id === 'confectionary' ? '🍟' :
                c.id === 'bakery' ? '🍞' :
                c.id === 'sauce' ? '🥫' : '🏷️'
        }))
      ]
    : CATEGORIES;

  // Get initial values from URL query parameters
  const initialCategory = searchParams.get('category') || '';
  const initialSearch = searchParams.get('search') || '';

  const [selectedCategory, setSelectedCategory] = useState(initialCategory);
  const [searchQuery, setSearchQuery] = useState(initialSearch);

  // Sync state if URL changes
  useEffect(() => {
    setSelectedCategory(searchParams.get('category') || '');
    setSearchQuery(searchParams.get('search') || '');
  }, [searchParams]);

  // Update URL parameters helper
  const updateUrl = (category: string, search: string) => {
    const params = new URLSearchParams();
    if (category) params.set('category', category);
    if (search) params.set('search', search);

    const queryString = params.toString();
    router.replace(`/shop${queryString ? `?${queryString}` : ''}`, { scroll: false });
  };

  const handleCategorySelect = (categoryId: string) => {
    setSelectedCategory(categoryId);
    updateUrl(categoryId, searchQuery);
  };

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const query = e.target.value;
    setSearchQuery(query);
    updateUrl(selectedCategory, query);
  };

  // Perform instant client-side filtering
  const filteredProducts = initialProducts.filter((p) => {
    const matchesCategory =
      selectedCategory === '' || p.category === selectedCategory;
    const matchesSearch =
      searchQuery.trim() === '' ||
      p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.barcode.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.category.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  return (
    <div className="flex flex-col lg:flex-row gap-8">
      
      {/* 1. SIDEBAR FILTER MODULE */}
      <aside className="w-full lg:w-64 flex-shrink-0 space-y-6 text-left">
        <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-5">
          <div className="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 className="font-extrabold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-2">
              <SlidersHorizontal className="w-4 h-4 text-emerald-600" />
              Filter Catalog
            </h3>
            {(selectedCategory || searchQuery) && (
              <button
                onClick={() => {
                  setSelectedCategory('');
                  setSearchQuery('');
                  updateUrl('', '');
                }}
                className="text-[10px] font-bold text-rose-500 hover:underline"
              >
                Clear All
              </button>
            )}
          </div>

          {/* Search filter input */}
          <div className="space-y-2">
            <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Search Keyword</label>
            <div className="relative">
              <input
                type="text"
                value={searchQuery}
                onChange={handleSearchChange}
                placeholder="Search name, barcode..."
                className="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-3 pr-8 text-xs text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition-all shadow-inner"
              />
              <Search className="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            </div>
          </div>

          {/* Category List */}
          <div className="space-y-2">
            <label className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block mb-1">Categories</label>
            <div className="flex flex-wrap lg:flex-col gap-1.5">
              {categoriesList.map((cat) => {
                const isActive = selectedCategory === cat.id;
                return (
                  <button
                    key={cat.id}
                    onClick={() => handleCategorySelect(cat.id)}
                    className={`w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2.5 transition-all ${
                      isActive
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm font-bold'
                        : 'border border-transparent hover:bg-slate-50 text-slate-650'
                    }`}
                  >
                    <span>{cat.icon}</span>
                    <span className="truncate">{cat.name}</span>
                  </button>
                );
              })}
            </div>
          </div>
        </div>
      </aside>

      {/* 2. PRODUCTS GRID LIST */}
      <section className="flex-1 space-y-6">
        {/* Results Header */}
        <div className="flex items-center justify-between bg-white border border-slate-200 rounded-2xl p-4 shadow-sm text-xs font-semibold text-slate-600 text-left">
          <div>
            Showing <strong className="text-slate-800">{filteredProducts.length}</strong> items of{' '}
            <strong className="text-slate-800">{initialProducts.length}</strong> total products
          </div>
          {selectedCategory && (
            <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full font-bold border border-emerald-150 uppercase text-[9px] tracking-wider">
              {CATEGORIES.find((c) => c.id === selectedCategory)?.name}
            </span>
          )}
        </div>

        {/* Catalog Grid */}
        {filteredProducts.length === 0 ? (
          <div className="bg-white border border-slate-200 rounded-3xl p-16 text-center shadow-sm">
            <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-350 mx-auto mb-4 border border-dashed border-slate-300">
              <Search className="w-8 h-8" />
            </div>
            <h4 className="font-extrabold text-slate-800 text-sm">No Matching Products</h4>
            <p className="text-xs text-slate-400 mt-1">
              We couldn't find any products matching your active filter criteria. Try adjusting your search query!
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
            {filteredProducts.map((p) => {
              const discount = p.discount_percentage || 0;
              const oldPrice = p.old_price || 0;
              
              return (
                <div
                  key={p.id}
                  className="bg-white border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md hover:border-emerald-250 transition-all group relative"
                >
                  {/* Discount badge */}
                  {discount > 0 && (
                    <span className="absolute top-3 left-3 bg-rose-500 text-white text-[9px] font-extrabold px-2 py-0.5 rounded-full z-10 shadow-sm">
                      {discount}% OFF
                    </span>
                  )}

                  {/* Image wrapper */}
                  <Link
                    href={`/product/${p.id}`}
                    className="block relative aspect-square rounded-2xl overflow-hidden bg-slate-50 border border-slate-100 mb-4 flex-shrink-0"
                  >
                    <Image
                      src={p.image ? (p.image.startsWith('http') || p.image.startsWith('/') ? p.image : `/${p.image}`) : '/assets/images/placeholder.svg'}
                      alt={p.name}
                      fill
                      className="object-cover group-hover:scale-105 transition-transform duration-300"
                      sizes="(max-width: 768px) 50vw, (max-width: 1200px) 33vw, 25vw"
                    />
                  </Link>

                  {/* Info details */}
                  <div className="flex-1 flex flex-col justify-between text-left">
                    <div className="space-y-1">
                      <span className="text-[9px] text-emerald-600 font-extrabold uppercase tracking-wider">
                        {p.category.replace('_', ' ')}
                      </span>
                      <Link
                        href={`/product/${p.id}`}
                        className="block font-bold text-slate-800 text-xs sm:text-sm hover:text-emerald-605 transition-colors line-clamp-1"
                      >
                        {p.name}
                      </Link>
                      {p.weight && (
                        <span className="text-[10px] text-slate-400 block font-normal">
                          {p.weight} ({p.unit})
                        </span>
                      )}
                    </div>

                    {/* Pricing */}
                    <div className="mt-4 flex items-baseline gap-2">
                      <span className="text-sm sm:text-base font-mono font-black text-emerald-600">
                        Rs. {p.price.toFixed(0)}
                      </span>
                      {oldPrice > p.price && (
                        <span className="text-[10px] text-slate-400 line-through font-normal font-mono">
                          Rs. {oldPrice.toFixed(0)}
                        </span>
                      )}
                    </div>

                    {/* Add/Buy Buttons */}
                    <div className="mt-3 w-full">
                      <AddToCartButton product={{
                        id: p.id,
                        name: p.name,
                        price: p.price,
                        image: p.image,
                        weight: p.weight,
                        unit: p.unit
                      }} />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </section>

    </div>
  );
};
