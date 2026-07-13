'use client';

import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/supabase';
import { 
  Ticket, 
  Plus, 
  Trash2, 
  Check, 
  X, 
  AlertCircle,
  HelpCircle,
  Percent,
  Coins
} from 'lucide-react';

interface Coupon {
  id: number;
  code: string;
  discount_type: 'percentage' | 'fixed';
  discount_value: number;
  min_order_amount: number;
  active: boolean;
  created_at: string;
}

export default function CouponsContent() {
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Form State
  const [code, setCode] = useState('');
  const [discountType, setDiscountType] = useState<'percentage' | 'fixed'>('percentage');
  const [discountValue, setDiscountValue] = useState('');
  const [minOrderAmount, setMinOrderAmount] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    fetchCoupons();
  }, []);

  const fetchCoupons = async () => {
    try {
      setLoading(true);
      const { data, error } = await supabase
        .from('coupons')
        .select('*')
        .order('id', { ascending: false });

      if (error) throw error;
      setCoupons(data || []);
    } catch (err: any) {
      setError(err.message || 'Failed to fetch coupons.');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateCoupon = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!code.trim() || !discountValue.trim()) return;

    setSubmitting(true);
    setError('');
    setSuccess('');

    try {
      const cleanCode = code.trim().toUpperCase();
      const val = parseFloat(discountValue);
      const minVal = minOrderAmount.trim() ? parseFloat(minOrderAmount) : 0;

      if (isNaN(val) || val <= 0) {
        throw new Error('Discount value must be a positive number.');
      }
      if (discountType === 'percentage' && val > 100) {
        throw new Error('Percentage discount cannot exceed 100%.');
      }

      const { error: insErr } = await supabase
        .from('coupons')
        .insert([
          {
            code: cleanCode,
            discount_type: discountType,
            discount_value: val,
            min_order_amount: minVal,
            active: isActive
          }
        ]);

      if (insErr) throw insErr;

      setSuccess(`Coupon '${cleanCode}' created successfully!`);
      setCode('');
      setDiscountValue('');
      setMinOrderAmount('');
      setIsActive(true);
      fetchCoupons();
    } catch (err: any) {
      setError(err.message || 'Failed to create coupon.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleToggleStatus = async (id: number, currentStatus: boolean) => {
    setError('');
    setSuccess('');
    try {
      const { error: updErr } = await supabase
        .from('coupons')
        .update({ active: !currentStatus })
        .eq('id', id);

      if (updErr) throw updErr;
      fetchCoupons();
    } catch (err: any) {
      setError(err.message || 'Failed to toggle coupon status.');
    }
  };

  const handleDeleteCoupon = async (id: number) => {
    if (!confirm('Are you sure you want to delete this coupon? This cannot be undone.')) return;
    setError('');
    setSuccess('');

    try {
      const { error: delErr } = await supabase
        .from('coupons')
        .delete()
        .eq('id', id);

      if (delErr) throw delErr;
      setSuccess('Coupon deleted successfully.');
      fetchCoupons();
    } catch (err: any) {
      setError(err.message || 'Failed to delete coupon.');
    }
  };

  return (
    <div className="space-y-6 text-left w-full flex-grow flex flex-col">
      {/* Page Header */}
      <section className="pb-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
            <Ticket className="w-5 h-5 text-emerald-600" />
            Coupons Desk
          </h1>
          <p className="text-xs text-slate-400 mt-1">Manage promotional discount coupon codes, minimum order value constraints, and active tags.</p>
        </div>
      </section>

      {/* Notifications */}
      {error && (
        <div className="p-4 bg-rose-50 border border-rose-250 rounded-2xl text-rose-700 text-xs font-semibold flex items-center gap-2">
          <AlertCircle className="w-4.5 h-4.5 flex-shrink-0" />
          <span>{error}</span>
        </div>
      )}
      {success && (
        <div className="p-4 bg-emerald-50 border border-emerald-250 rounded-2xl text-emerald-700 text-xs font-semibold flex items-center gap-2">
          <Check className="w-4.5 h-4.5 flex-shrink-0" />
          <span>{success}</span>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        {/* Left side: Creation form */}
        <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4">
          <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center gap-1.5">
            <Plus className="w-4 h-4 text-emerald-650" />
            Create Discount Coupon
          </h3>

          <form onSubmit={handleCreateCoupon} className="space-y-4">
            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Coupon Code (Unique)</label>
              <input
                type="text"
                value={code}
                onChange={(e) => setCode(e.target.value.toUpperCase())}
                placeholder="e.g. SAVE10"
                required
                className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs font-bold font-mono text-slate-800 placeholder-slate-400 uppercase"
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-1">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Discount Type</label>
                <select
                  value={discountType}
                  onChange={(e) => setDiscountType(e.target.value as 'percentage' | 'fixed')}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs font-semibold text-slate-800"
                >
                  <option value="percentage">Percentage (%)</option>
                  <option value="fixed">Fixed Price (Rs.)</option>
                </select>
              </div>

              <div className="space-y-1">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                  {discountType === 'percentage' ? 'Discount %' : 'Discount Rs.'}
                </label>
                <input
                  type="number"
                  value={discountValue}
                  onChange={(e) => setDiscountValue(e.target.value)}
                  placeholder={discountType === 'percentage' ? 'e.g. 10' : 'e.g. 200'}
                  required
                  min="0.01"
                  step="any"
                  className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs font-bold text-slate-800 placeholder-slate-400 font-mono"
                />
              </div>
            </div>

            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Min Order Requirement (Rs.)</label>
              <input
                type="number"
                value={minOrderAmount}
                onChange={(e) => setMinOrderAmount(e.target.value)}
                placeholder="e.g. 1000 (0 for none)"
                min="0"
                step="any"
                className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-xs font-semibold text-slate-800 placeholder-slate-400 font-mono"
              />
            </div>

            <div className="flex items-center justify-between py-2 border-t border-slate-100">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Enable Coupon Instantly</span>
              <button
                type="button"
                onClick={() => setIsActive(!isActive)}
                className={`px-3 py-1 rounded-lg text-[9px] font-black uppercase transition-colors border ${
                  isActive 
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-250' 
                    : 'bg-slate-50 text-slate-400 border-slate-200'
                }`}
              >
                {isActive ? 'Active' : 'Inactive'}
              </button>
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs shadow-md transition-all active:scale-[0.98] disabled:bg-slate-200 disabled:text-slate-400"
            >
              {submitting ? 'Creating...' : 'Save Discount Coupon'}
            </button>
          </form>
        </div>

        {/* Right side: Coupons table/list */}
        <div className="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-4 lg:col-span-2">
          <h3 className="text-xs font-extrabold text-slate-800 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center justify-between">
            <span>Registered Coupon Codes</span>
            <span className="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[10px] font-bold font-mono">
              {coupons.length} Active Promo tags
            </span>
          </h3>

          <div className="overflow-x-auto w-full">
            {loading ? (
              <p className="text-xs text-slate-400 text-center py-16">Loading coupons...</p>
            ) : coupons.length === 0 ? (
              <p className="text-xs text-slate-400 text-center py-16">No coupons created yet.</p>
            ) : (
              <table className="w-full border-collapse">
                <thead>
                  <tr className="border-b border-slate-100 text-[10px] uppercase font-bold text-slate-400 text-left">
                    <th className="pb-3 pr-2">Code</th>
                    <th className="pb-3 px-2">Discount</th>
                    <th className="pb-3 px-2">Min Spend</th>
                    <th className="pb-3 px-2">Status</th>
                    <th className="pb-3 pl-2 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-50 text-xs">
                  {coupons.map((c) => (
                    <tr key={c.id} className="hover:bg-slate-50/50">
                      <td className="py-3.5 pr-2">
                        <span className="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200 text-xs">
                          {c.code}
                        </span>
                      </td>
                      <td className="py-3.5 px-2 font-mono font-semibold text-slate-700">
                        {c.discount_type === 'percentage' ? (
                          <span className="flex items-center gap-1">
                            {c.discount_value}% <Percent className="w-3.5 h-3.5 text-slate-450" />
                          </span>
                        ) : (
                          <span className="flex items-center gap-1">
                            Rs. {c.discount_value} <Coins className="w-3.5 h-3.5 text-slate-450" />
                          </span>
                        )}
                      </td>
                      <td className="py-3.5 px-2 font-mono text-slate-500">
                        Rs. {c.min_order_amount}
                      </td>
                      <td className="py-3.5 px-2">
                        <button
                          type="button"
                          onClick={() => handleToggleStatus(c.id, c.active)}
                          className={`px-2 py-0.5 rounded text-[9px] font-bold uppercase transition-colors ${
                            c.active 
                              ? 'bg-emerald-50 text-emerald-705 border border-emerald-200' 
                              : 'bg-rose-50 text-rose-705 border border-rose-200'
                          }`}
                        >
                          {c.active ? 'ACTIVE' : 'INACTIVE'}
                        </button>
                      </td>
                      <td className="py-3.5 pl-2 text-right">
                        <button
                          onClick={() => handleDeleteCoupon(c.id)}
                          className="p-1.5 text-slate-405 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                          title="Delete Coupon"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
