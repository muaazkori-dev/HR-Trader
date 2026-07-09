'use client';

import React, { useState, useEffect, useRef } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { 
  Barcode, 
  ArrowLeft, 
  Trash2, 
  Plus, 
  Minus, 
  Search, 
  Printer, 
  Info, 
  Settings, 
  AlertTriangle,
  RotateCcw
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';

interface Product {
  id: number;
  name: string;
  price: number;
  stock_quantity: number;
  barcode: string;
  weight?: string;
  unit?: string;
}

interface BillingItem {
  product_id: number;
  name: string;
  price: number;
  stock_qty: number;
  barcode: string;
  quantity: number;
}

export default function POSCounterPage() {
  const router = useRouter();
  const { user, profile, loading } = useAuth();
  
  // State
  const [billingCart, setBillingCart] = useState<BillingItem[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<Product[]>([]);
  const [showResults, setShowResults] = useState(false);
  const [quickProducts, setQuickProducts] = useState<Product[]>([]);
  
  const [discount, setDiscount] = useState<number>(0);
  const [cashPaid, setCashPaid] = useState<string>('0');
  const [paymentMethod, setPaymentMethod] = useState('Cash');
  
  const [statusMessage, setStatusMessage] = useState({ text: 'Scanner ready. Point and scan product barcode directly.', type: 'info' });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [currentTime, setCurrentTime] = useState('');

  const searchRef = useRef<HTMLDivElement>(null);
  const searchInputRef = useRef<HTMLInputElement>(null);

  // Scanner Hook Buffer variables
  const scanBuffer = useRef('');
  const lastScanTime = useRef(Date.now());

  // Access Control: Strict check
  useEffect(() => {
    if (!loading) {
      if (!user || (profile?.role !== 'owner' && profile?.role !== 'manager')) {
        router.replace('/login');
      }
    }
  }, [user, profile, loading, router]);

  // Clock
  useEffect(() => {
    const updateTime = () => {
      const now = new Date();
      setCurrentTime(now.toLocaleTimeString());
    };
    updateTime();
    const interval = setInterval(updateTime, 1000);
    return () => clearInterval(interval);
  }, []);

  // Fetch quick products shortcuts
  useEffect(() => {
    const fetchQuickProducts = async () => {
      try {
        const { data, error } = await supabase
          .from('products')
          .select('id, name, price, stock_quantity, barcode, weight, unit')
          .limit(8);
        if (!error && data) {
          setQuickProducts(data as Product[]);
        }
      } catch (err) {
        console.error('Error fetching quick products:', err);
      }
    };
    fetchQuickProducts();
  }, []);

  // Auto-focus barcode input if click occurs outside interactive elements
  useEffect(() => {
    const handleDocumentClick = (e: MouseEvent) => {
      const activeTag = document.activeElement?.tagName.toLowerCase();
      if (activeTag !== 'input' && activeTag !== 'select' && activeTag !== 'button') {
        searchInputRef.current?.focus();
      }
    };
    document.addEventListener('click', handleDocumentClick);
    return () => document.removeEventListener('click', handleDocumentClick);
  }, []);

  // Global Barcode Keyboard Hook Listener
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const now = Date.now();
      
      // Keystrokes faster than 80ms are assumed to be hardware scanner
      if (now - lastScanTime.current > 80) {
        scanBuffer.current = '';
      }
      lastScanTime.current = now;

      if (e.key === 'Enter') {
        if (scanBuffer.current.length >= 4) {
          e.preventDefault();
          const scannedCode = scanBuffer.current;
          scanBuffer.current = '';
          handleBarcodeScanned(scannedCode);
        }
      } else if (e.key !== 'Shift') {
        scanBuffer.current += e.key;
      }
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [billingCart]);

  // Search autocomplete query fetch
  useEffect(() => {
    if (searchQuery.trim().length < 2) {
      setSearchResults([]);
      setShowResults(false);
      return;
    }

    const delayDebounce = setTimeout(async () => {
      try {
        const cleanQuery = searchQuery.trim().replace(/\s+/, '%');
        const { data, error } = await supabase
          .from('products')
          .select('id, name, price, stock_quantity, barcode, weight, unit')
          .or(`name.ilike.%${cleanQuery}%,barcode.ilike.%${cleanQuery}%`)
          .limit(6);

        if (!error && data) {
          setSearchResults(data as Product[]);
          setShowResults(true);
        }
      } catch (err) {
        console.error(err);
      }
    }, 150);

    return () => clearTimeout(delayDebounce);
  }, [searchQuery]);

  // Click outside search suggestions
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(e.target as Node)) {
        setShowResults(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Logic: Add item to cart
  const addItemToCart = (p: Product) => {
    const existing = billingCart.find(item => item.product_id === p.id);

    if (existing) {
      if (existing.quantity >= p.stock_quantity) {
        setStatusMessage({ text: `Stock limit reached (${p.stock_quantity}) for '${p.name}'`, type: 'error' });
        return;
      }
      setBillingCart(prev => 
        prev.map(item => item.product_id === p.id ? { ...item, quantity: item.quantity + 1 } : item)
      );
    } else {
      if (p.stock_quantity <= 0) {
        setStatusMessage({ text: `'${p.name}' is out of stock.`, type: 'error' });
        return;
      }
      setBillingCart(prev => [
        ...prev,
        {
          product_id: p.id,
          name: p.name,
          price: Number(p.price),
          stock_qty: p.stock_quantity,
          barcode: p.barcode,
          quantity: 1
        }
      ]);
    }
    setStatusMessage({ text: `Added '${p.name}' to invoice list.`, type: 'success' });
    setSearchQuery('');
    setShowResults(false);
    searchInputRef.current?.focus();
  };

  // Logic: Handle scanner lookup
  const handleBarcodeScanned = async (code: string) => {
    setStatusMessage({ text: `Scanning barcode: ${code}...`, type: 'info' });
    try {
      const { data, error } = await supabase
        .from('products')
        .select('id, name, price, stock_quantity, barcode, weight, unit')
        .eq('barcode', code)
        .single();

      if (!error && data) {
        addItemToCart(data as Product);
      } else {
        setStatusMessage({ text: `Barcode '${code}' not found in catalog.`, type: 'error' });
      }
    } catch (err) {
      console.error(err);
      setStatusMessage({ text: 'Scanner lookup database error.', type: 'error' });
    }
  };

  const updateItemQty = (id: number, change: number) => {
    const item = billingCart.find(item => item.product_id === id);
    if (!item) return;

    const newQty = item.quantity + change;
    if (newQty <= 0) {
      setBillingCart(prev => prev.filter(item => item.product_id !== id));
      setStatusMessage({ text: 'Item removed from invoice list.', type: 'info' });
    } else if (newQty > item.stock_qty) {
      setStatusMessage({ text: `Cannot exceed available stock limit (${item.stock_qty})`, type: 'error' });
    } else {
      setBillingCart(prev => 
        prev.map(item => item.product_id === id ? { ...item, quantity: newQty } : item)
      );
    }
  };

  // Math Calculations
  const getSubtotal = () => {
    return billingCart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  };

  const getNetPayable = () => {
    const subtotal = getSubtotal();
    const discAmount = subtotal * (discount / 100);
    return Math.max(0, subtotal - discAmount);
  };

  const getChangeDue = () => {
    const net = getNetPayable();
    const paid = parseFloat(cashPaid) || 0;
    return Math.max(0, paid - net);
  };

  // Checkout submission
  const handlePOSCheckout = async () => {
    if (billingCart.length === 0) {
      setStatusMessage({ text: 'Cannot checkout an empty invoice list.', type: 'error' });
      return;
    }

    const net = getNetPayable();
    const paid = parseFloat(cashPaid) || 0;

    if (paymentMethod === 'Cash' && paid < net) {
      if (!confirm("Received Cash is less than Net Amount Due. Proceed anyway?")) {
        return;
      }
    }

    setIsSubmitting(true);
    setStatusMessage({ text: 'Submitting transaction to cloud database...', type: 'info' });

    try {
      // 1. Calculate purchase costs to compute total profit
      // Fetch cost prices of products in billing cart
      const { data: productsData, error: prodErr } = await supabase
        .from('products')
        .select('id, purchase_price')
        .in('id', billingCart.map(item => item.product_id));

      if (prodErr) throw prodErr;

      let totalProfit = 0;
      billingCart.forEach(item => {
        const prod = productsData?.find(p => p.id === item.product_id);
        const cost = prod ? Number(prod.purchase_price) : 0;
        // profit = revenue (post-discount price share) - purchase cost
        const itemRevenue = item.price * item.quantity * (1 - discount / 100);
        const itemCost = cost * item.quantity;
        totalProfit += (itemRevenue - itemCost);
      });

      // 2. Insert Sale Transaction
      const { data: saleData, error: saleErr } = await supabase
        .from('sales')
        .insert({
          transaction_type: 'POS',
          total_amount: net,
          total_profit: totalProfit,
          payment_method: paymentMethod,
          cashier_id: user?.id
        })
        .select()
        .single();

      if (saleErr) throw saleErr;

      // 3. Insert Sale Items and update inventory quantities
      const saleItemsPayload = billingCart.map(item => {
        const prod = productsData?.find(p => p.id === item.product_id);
        return {
          sale_id: saleData.id,
          product_id: item.product_id,
          quantity: item.quantity,
          price: item.price,
          purchase_price: prod ? Number(prod.purchase_price) : 0
        };
      });

      const { error: itemsErr } = await supabase
        .from('sale_items')
        .insert(saleItemsPayload);

      if (itemsErr) throw itemsErr;

      // 4. Update Inventory Levels
      for (const item of billingCart) {
        await supabase
          .from('products')
          .update({ stock_quantity: item.stock_qty - item.quantity })
          .eq('id', item.product_id);
      }

      setStatusMessage({ text: 'Transaction recorded! Spawning print preview...', type: 'success' });
      
      // Clear Cart
      setBillingCart([]);
      setDiscount(0);
      setCashPaid('0');
      
      // Open Print Receipt Pop-up optimized for Thermal printing
      const printUrl = `/admin/pos/print?sale_id=${saleData.id}`;
      const printWindow = window.open(printUrl, 'Thermal Receipt', 'width=350,height=600,top=100,left=100');
      if (printWindow) {
        printWindow.focus();
      } else {
        alert("Popup blocker blocked thermal invoice window. Visit directly at: " + printUrl);
      }

    } catch (err: any) {
      console.error(err);
      setStatusMessage({ text: err.message || 'Transaction checkout database error.', type: 'error' });
    } finally {
      setIsSubmitting(false);
      searchInputRef.current?.focus();
    }
  };

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center text-slate-400 text-xs font-semibold">Verifying Cashier Desk...</div>;
  }

  return (
    <div className="fixed inset-0 bg-slate-150 flex flex-col z-40 overflow-hidden font-sans text-slate-800">
      
      {/* 1. POS TOP PANEL BAR */}
      <header className="bg-slate-900 text-white px-6 py-3 flex items-center justify-between shadow-md select-none">
        <div className="flex items-center gap-4">
          <Link href="/admin" className="flex items-center gap-2 text-emerald-500 hover:text-emerald-400 transition-colors">
            <div className="w-8 h-8 bg-emerald-600 rounded-xl flex items-center justify-center text-white font-extrabold text-sm shadow-md">
              HR
            </div>
            <span className="text-sm font-black tracking-wider text-white">HR TRADERS <span className="text-[10px] text-slate-400 font-bold uppercase">POS Counter</span></span>
          </Link>
          <span className="hidden sm:inline text-[10px] px-2.5 py-1 bg-slate-800 border border-slate-700/80 text-emerald-500 font-extrabold rounded-lg uppercase tracking-wider">
            Cashier: {profile?.name || 'Staff User'}
          </span>
        </div>

        <div className="flex items-center gap-3">
          <span className="text-xs font-mono text-slate-400 bg-slate-850 px-3 py-1 rounded-lg border border-slate-800">{currentTime}</span>
          <Link href="/admin" className="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-205 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-sm border border-slate-700">
            <ArrowLeft className="w-3.5 h-3.5" /> Dashboard
          </Link>
        </div>
      </header>

      {/* 2. MAIN WORKING DIVISION */}
      <div className="flex-1 flex overflow-hidden">
        
        {/* LEFT COLUMN: ACTIVE BILL ITEMS */}
        <div className="w-full lg:w-3/5 p-4 flex flex-col justify-between border-r border-slate-200 h-full bg-slate-50 overflow-hidden">
          
          <div className="flex-1 overflow-y-auto bg-white border border-slate-200 rounded-2xl shadow-sm relative">
            <table className="w-full text-left border-collapse">
              <thead className="sticky top-0 bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider select-none">
                <tr>
                  <th className="p-3.5 pl-5">Product Details</th>
                  <th className="p-3.5">Price</th>
                  <th className="p-3.5 text-center" style={{ width: '130px' }}>Quantity</th>
                  <th className="p-3.5 text-right pr-5">Total</th>
                  <th className="p-3.5 text-center">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 text-slate-700 text-xs">
                {billingCart.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="py-32 text-center text-slate-400">
                      <div className="flex flex-col items-center gap-3 select-none">
                        <Barcode className="w-12 h-12 opacity-15 stroke-1 animate-pulse" />
                        <p className="text-xs font-medium text-slate-400">Scan product barcode or type name in search lookup.</p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  billingCart.map((item) => (
                    <tr key={item.product_id} className="hover:bg-slate-50 transition-colors border-b border-slate-105">
                      <td className="p-3.5 pl-5 text-left">
                        <span className="font-bold text-slate-800 block text-xs">{item.name}</span>
                        <span className="text-[10px] text-slate-400 font-mono">Barcode: {item.barcode}</span>
                      </td>
                      <td className="p-3.5 text-slate-700 font-semibold font-mono">Rs. {item.price.toFixed(2)}</td>
                      <td className="p-3.5 text-center">
                        <div className="inline-flex items-center bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                          <button 
                            type="button"
                            onClick={() => updateItemQty(item.product_id, -1)} 
                            className="px-2.5 py-1 text-slate-500 hover:bg-slate-50 transition-colors font-bold text-xs"
                          >
                            <Minus className="w-3 h-3" />
                          </button>
                          <span className="px-2 font-bold font-mono text-slate-800 text-xs min-w-[24px] text-center">{item.quantity}</span>
                          <button 
                            type="button"
                            onClick={() => updateItemQty(item.product_id, 1)} 
                            className="px-2.5 py-1 text-slate-500 hover:bg-slate-50 transition-colors font-bold text-xs"
                          >
                            <Plus className="w-3 h-3" />
                          </button>
                        </div>
                      </td>
                      <td className="p-3.5 text-right font-bold font-mono text-emerald-600 pr-5">Rs. {(item.price * item.quantity).toFixed(2)}</td>
                      <td className="p-3.5 text-center">
                        <button 
                          type="button"
                          onClick={() => setBillingCart(prev => prev.filter(p => p.product_id !== item.product_id))} 
                          className="p-1.5 text-slate-400 hover:text-rose-600 transition-colors"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* Status Bar Message */}
          <div className={`mt-4 p-3 border rounded-xl text-xs flex items-center gap-2.5 shadow-sm transition-all select-none ${
            statusMessage.type === 'error' 
              ? 'bg-rose-50 border-rose-200 text-rose-700' 
              : statusMessage.type === 'success' 
              ? 'bg-emerald-50 border-emerald-250 text-emerald-700' 
              : 'bg-white border-slate-200 text-slate-600'
          }`}>
            {statusMessage.type === 'error' ? (
              <AlertTriangle className="w-4.5 h-4.5 text-rose-500 flex-shrink-0 animate-bounce" />
            ) : (
              <Info className="w-4.5 h-4.5 text-emerald-600 flex-shrink-0" />
            )}
            <span className="font-semibold text-left">{statusMessage.text}</span>
          </div>

        </div>

        {/* RIGHT COLUMN: NUMERIC CONTROLS & SEARCH */}
        <div className="w-2/5 p-4 flex flex-col justify-between bg-white border-l border-slate-200 h-full overflow-hidden space-y-4">
          
          {/* Autocomplete Search input */}
          <div ref={searchRef} className="bg-slate-50 border border-slate-200 p-4 rounded-2xl space-y-2.5 relative flex-shrink-0">
            <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Search Catalogue & Scan input</label>
            <div className="relative">
              <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                <Search className="w-4 h-4" />
              </span>
              <input 
                type="text" 
                ref={searchInputRef}
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                autoComplete="off"
                className="w-full pl-9 pr-9 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-xs font-semibold font-mono text-slate-800 shadow-sm"
                placeholder="Scan barcode or type product name..."
              />
              {searchQuery && (
                <button 
                  onClick={() => { setSearchQuery(''); setSearchResults([]); setShowResults(false); }}
                  className="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-700 text-xs"
                >
                  ✕
                </button>
              )}
            </div>

            {/* Suggestions Overlay */}
            {showResults && searchResults.length > 0 && (
              <div className="absolute left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl z-55 max-h-60 overflow-y-auto divide-y divide-slate-100 text-left border-t border-slate-100 animate-in fade-in slide-in-from-top-1">
                {searchResults.map((p) => (
                  <div 
                    key={p.id}
                    onClick={() => addItemToCart(p)}
                    className="p-3 hover:bg-slate-50 cursor-pointer flex items-center justify-between transition-colors"
                  >
                    <div>
                      <span className="font-bold text-slate-800 block text-xs">{p.name}</span>
                      <span className="text-[9px] text-slate-400 font-mono">Barcode: {p.barcode} {p.weight ? ` | ${p.weight}` : ''}</span>
                    </div>
                    <div className="text-right">
                      <span className="text-xs font-bold text-emerald-600 block">Rs. {p.price.toFixed(0)}</span>
                      <span className={`text-[9px] font-bold block ${p.stock_quantity <= 5 ? 'text-rose-500' : 'text-slate-450'}`}>Stock: {p.stock_quantity}</span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Quick Shortcuts */}
          <div className="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex-1 overflow-y-auto space-y-3">
            <h4 className="text-[10px] font-bold text-slate-500 uppercase tracking-wider text-left">Quick Tapping Shortcuts</h4>
            <div className="grid grid-cols-2 gap-2">
              {quickProducts.map((p) => (
                <button
                  key={p.id}
                  type="button"
                  onClick={() => addItemToCart(p)}
                  className="p-2 bg-white border border-slate-200 rounded-xl hover:border-emerald-500 hover:bg-slate-50 transition-all text-left truncate shadow-sm flex flex-col justify-between h-14"
                >
                  <span className="block text-xs font-bold text-slate-800 truncate w-full">{p.name}</span>
                  <span className="text-[10px] text-emerald-600 font-extrabold font-mono mt-0.5">Rs. {p.price.toFixed(0)}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Checkout & Bill Mathematics Summary */}
          <div className="bg-slate-50 border border-slate-200 p-4 rounded-2xl space-y-3.5 flex-shrink-0">
            
            <div className="grid grid-cols-2 gap-3 text-xs pb-3 border-b border-slate-200/80">
              <div className="flex flex-col gap-1 text-left">
                <span className="text-slate-500 font-semibold">Subtotal</span>
                <strong className="text-sm font-bold text-slate-800 font-mono">Rs. {getSubtotal().toFixed(2)}</strong>
              </div>
              <div className="flex flex-col gap-1 text-left">
                <span className="text-slate-500 font-semibold">Discount (%)</span>
                <input 
                  type="number" 
                  min="0"
                  max="100"
                  value={discount === 0 ? '' : discount}
                  onChange={(e) => setDiscount(Math.min(100, Math.max(0, parseInt(e.target.value) || 0)))}
                  className="w-full bg-white border border-slate-250 px-2 py-1 rounded-lg text-xs font-bold text-emerald-600 focus:outline-none focus:border-emerald-500"
                  placeholder="0"
                />
              </div>
            </div>

            <div className="flex items-center justify-between p-2.5 bg-slate-900 text-white rounded-xl shadow-sm border border-slate-800">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Net Due</span>
              <span className="text-xl font-black text-emerald-400 font-mono">Rs. {getNetPayable().toFixed(2)}</span>
            </div>

            <div className="grid grid-cols-2 gap-3 text-xs pt-1">
              <div className="text-left">
                <span className="block text-slate-500 mb-1 font-semibold">Cash Paid</span>
                <input 
                  type="number" 
                  min="0"
                  value={cashPaid === '0' ? '' : cashPaid}
                  onChange={(e) => setCashPaid(e.target.value)}
                  className="w-full bg-white border border-slate-250 px-2.5 py-1.5 rounded-xl font-bold font-mono text-emerald-600 focus:outline-none focus:border-emerald-500 text-xs"
                  placeholder="0.00"
                />
              </div>
              <div className="text-left">
                <span className="block text-slate-500 mb-1 font-semibold">Change Due</span>
                <div className="px-2.5 py-1.5 bg-white border border-slate-250 rounded-xl font-black font-mono text-slate-800 text-xs shadow-inner min-h-[34px] flex items-center">
                  Rs. {getChangeDue().toFixed(2)}
                </div>
              </div>
            </div>

            <div className="space-y-2 pt-1 text-left">
              <select 
                value={paymentMethod}
                onChange={(e) => setPaymentMethod(e.target.value)}
                className="w-full px-3 py-2 bg-white border border-slate-250 rounded-xl focus:outline-none text-xs font-bold text-slate-700 shadow-sm"
              >
                <option value="Cash">Cash Transaction</option>
                <option value="Card">Credit/Debit Card</option>
                <option value="Mobile Wallet">Mobile Wallet (EasyPaisa/JazzCash)</option>
              </select>
            </div>

            <button
              onClick={handlePOSCheckout}
              disabled={isSubmitting}
              className="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-350 text-white font-extrabold rounded-xl text-xs shadow-md transition-all uppercase tracking-widest flex items-center justify-center gap-1.5"
            >
              <Printer className="w-4 h-4" />
              {isSubmitting ? 'Recording...' : 'Fulfill & Print'}
            </button>

          </div>

        </div>

      </div>

    </div>
  );
}
