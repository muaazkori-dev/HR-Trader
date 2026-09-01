'use client';

import React, { useState, useEffect } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { Search, SlidersHorizontal, Tag, Eye, ClipboardList, CheckCircle2, Send } from 'lucide-react';
import { AddToCartButton } from './AddToCartButton';
import Link from 'next/link';
import Image from 'next/image';
import { supabase } from '@/lib/supabase';
import { getProductImageUrl } from '@/lib/utils';
import { useAuth } from '@/context/AuthContext';

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

  const [products, setProducts] = useState<Product[]>(initialProducts);
  const [selectedCategory, setSelectedCategory] = useState(initialCategory);
  const [searchQuery, setSearchQuery] = useState(initialSearch);

  // Demand Box Form state
  const { profile } = useAuth();
  const [demandName, setDemandName] = useState('');
  const [demandPhone, setDemandPhone] = useState('');
  const [demandDetails, setDemandDetails] = useState(initialSearch);
  const [isSubmittingDemand, setIsSubmittingDemand] = useState(false);
  const [demandSuccess, setDemandSuccess] = useState(false);

  // Sync demandDetails with searchQuery when searchQuery changes
  useEffect(() => {
    if (searchQuery) {
      setDemandDetails(searchQuery);
    }
  }, [searchQuery]);

  // Auto-fill user profile if logged in
  useEffect(() => {
    if (profile) {
      if (profile.name) setDemandName(profile.name);
      if (profile.phone) setDemandPhone(profile.phone);
    }
  }, [profile]);

  const handleDemandSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!demandName.trim() || !demandPhone.trim() || !demandDetails.trim()) return;

    setIsSubmittingDemand(true);
    try {
      const { error } = await supabase
        .from('product_demands')
        .insert({
          customer_name: demandName.trim(),
          customer_phone: demandPhone.trim(),
          demand_details: demandDetails.trim(),
          status: 'pending'
        });

      if (error) throw error;

      setDemandSuccess(true);
      setTimeout(() => {
        setDemandSuccess(false);
      }, 6000);
    } catch (err) {
      console.error('Error submitting product demand:', err);
      alert('Failed to submit demand request. Please try again.');
    } finally {
      setIsSubmittingDemand(false);
    }
  };

  useEffect(() => {
    const fetchFreshProducts = async () => {
      try {
        const { data, error } = await supabase
          .from('products')
          .select('*')
          .order('id', { ascending: false });
        if (!error && data) {
          setProducts(data as Product[]);
        }
      } catch (err) {
        console.error('Error fetching fresh shop products:', err);
      }
    };
    fetchFreshProducts();
  }, []);

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
  const filteredProducts = products.filter((p) => {
    const activeCatIdx = categoriesList.findIndex(c => c.id === selectedCategory);
    const matchesCategory =
      selectedCategory === '' ||
      p.category === selectedCategory ||
      (activeCatIdx > 0 && p.category === String(activeCatIdx - 1)); // index fallback (excluding 'All Categories' at 0)
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
            <strong className="text-slate-800">{products.length}</strong> total products
          </div>
          {selectedCategory && (
            <span className="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full font-bold border border-emerald-150 uppercase text-[9px] tracking-wider">
              {categoriesList.find((c) => c.id === selectedCategory)?.name || selectedCategory.replace(/_/g, ' ')}
            </span>
          )}
        </div>

        {/* Catalog Grid */}
        {filteredProducts.length === 0 ? (
          <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 text-center shadow-sm space-y-6">
            <div className="w-14 h-14 bg-amber-50 rounded-full flex items-center justify-center text-amber-600 mx-auto border border-amber-200 shadow-xs">
              <ClipboardList className="w-7 h-7" />
            </div>
            <div>
              <h4 className="font-extrabold text-slate-850 text-base">No Matching Products Found</h4>
              <p className="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                We couldn't find any products matching {searchQuery ? <strong className="text-slate-800">"{searchQuery}"</strong> : 'your active filter criteria'}.
              </p>
            </div>

            {/* Inline Demand Form Card */}
            <div className="max-w-md mx-auto bg-slate-50 border border-slate-200/90 rounded-3xl p-5 text-left shadow-sm space-y-4">
              <div className="flex items-center gap-3 pb-3 border-b border-slate-200/80">
                <div className="p-2 bg-emerald-100/80 rounded-xl text-emerald-700">
                  <ClipboardList className="w-5 h-5" />
                </div>
                <div>
                  <h5 className="text-xs font-extrabold text-slate-850 uppercase tracking-wider">Product Demand Box</h5>
                  <p className="text-[10px] text-slate-500 font-semibold mt-0.5">Apni demand yahan darj karein! Hum is product ko jaldi ready kar denge.</p>
                </div>
              </div>

              {demandSuccess ? (
                <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-center text-emerald-800 text-xs font-bold space-y-1.5 animate-in fade-in zoom-in-95 duration-200">
                  <p className="flex items-center justify-center gap-1.5 text-sm font-extrabold">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600" /> Demand Request Submitted!
                  </p>
                  <p className="text-[11px] font-medium text-emerald-700">
                    Aap ki demand "<strong>{demandDetails}</strong>" record ho gayi hai. Product available hote hi hum aap se rabta karein ge!
                  </p>
                </div>
              ) : (
                <form onSubmit={handleDemandSubmit} className="space-y-3">
                  <div className="space-y-1">
                    <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Product Name / Details</label>
                    <input
                      type="text"
                      value={demandDetails}
                      onChange={(e) => setDemandDetails(e.target.value)}
                      placeholder="e.g. Shan Chana Masala, MilkPak 1L..."
                      required
                      className="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 font-semibold shadow-xs"
                    />
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div className="space-y-1">
                      <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Your Name</label>
                      <input
                        type="text"
                        value={demandName}
                        onChange={(e) => setDemandName(e.target.value)}
                        placeholder="Aap ka naam"
                        required
                        className="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 font-semibold shadow-xs"
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="block text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Phone Number</label>
                      <input
                        type="tel"
                        value={demandPhone}
                        onChange={(e) => setDemandPhone(e.target.value)}
                        placeholder="03xxxxxxxxx"
                        required
                        className="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 font-mono font-bold shadow-xs"
                      />
                    </div>
                  </div>
                  <button
                    type="submit"
                    disabled={isSubmittingDemand}
                    className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-extrabold rounded-xl text-xs shadow-md shadow-emerald-600/10 active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer"
                  >
                    <Send className="w-3.5 h-3.5" />
                    {isSubmittingDemand ? 'Submitting Request...' : 'Submit Demand Request'}
                  </button>
                </form>
              )}
            </div>

            <div className="pt-2">
              <button
                type="button"
                onClick={() => {
                  setSelectedCategory('');
                  setSearchQuery('');
                  updateUrl('', '');
                }}
                className="text-xs font-bold text-slate-500 hover:text-emerald-600 underline transition-colors"
              >
                Clear all search & filter criteria
              </button>
            </div>
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
                      src={getProductImageUrl(p.image)}
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
