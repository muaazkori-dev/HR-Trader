'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { 
  ClipboardList, 
  Trash2, 
  Check, 
  Calendar, 
  Phone, 
  User, 
  AlertCircle,
  FileText
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';

interface ProductDemand {
  id: number;
  customer_name: string;
  customer_phone: string;
  demand_details: string;
  status: 'pending' | 'confirmed';
  created_at: string;
}

export default function AdminDemandsDesk() {
  const router = useRouter();
  const { user, profile, loading } = useAuth();

  // State
  const [demands, setDemands] = useState<ProductDemand[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState<string>('all');

  // Guard: restrict to staff
  useEffect(() => {
    if (!loading) {
      if (!user || (profile?.role !== 'owner' && profile?.role !== 'manager')) {
        router.replace('/login');
      }
    }
  }, [user, profile, loading, router]);

  const fetchDemands = async () => {
    try {
      setIsLoading(true);
      let query = supabase.from('product_demands').select('*');

      if (statusFilter !== 'all') {
        query = query.eq('status', statusFilter);
      }

      const { data, error } = await query.order('id', { ascending: false });

      if (!error && data) {
        setDemands(data as ProductDemand[]);
      }
    } catch (err) {
      console.error('Error fetching demands:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (user && profile) {
      fetchDemands();
    }
  }, [user, profile, statusFilter]);

  // Handle Confirm Demand (update status to confirmed)
  const handleConfirmDemand = async (id: number) => {
    try {
      const { error } = await supabase
        .from('product_demands')
        .update({ status: 'confirmed' })
        .eq('id', id);

      if (error) throw error;
      
      setDemands(prev => 
        prev.map(d => d.id === id ? { ...d, status: 'confirmed' } : d)
      );
    } catch (err: any) {
      console.error(err);
      alert(err.message || 'Failed to confirm demand.');
    }
  };

  // Handle Delete Demand
  const handleDeleteDemand = async (id: number) => {
    if (!confirm('Are you sure you want to delete this demand request?')) {
      return;
    }

    try {
      const { error } = await supabase
        .from('product_demands')
        .delete()
        .eq('id', id);

      if (error) throw error;

      setDemands(prev => prev.filter(d => d.id !== id));
    } catch (err: any) {
      console.error(err);
      alert(err.message || 'Failed to delete demand.');
    }
  };

  if (loading || isLoading) {
    return <div className="text-slate-400 text-xs font-semibold text-center py-20">Loading Demand Box Requests...</div>;
  }

  return (
    <div className="space-y-6 text-slate-800">
      
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-5 border-b border-slate-200 select-none">
        <div className="text-left">
          <h1 className="text-lg font-black text-slate-800 uppercase tracking-wider">Customer Demand Register</h1>
          <p className="text-xs text-slate-500 mt-1">Review products requested by customers that are not currently in catalog stock.</p>
        </div>

        {/* Filter */}
        <div className="flex items-center gap-2">
          <span className="text-[10px] font-bold text-slate-400 uppercase">Filter Status:</span>
          <select 
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 focus:outline-none"
          >
            <option value="all">All Demands</option>
            <option value="pending">Pending Requests</option>
            <option value="confirmed">Confirmed Requests</option>
          </select>
        </div>
      </div>

      {/* Grid or Table list */}
      <div className="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden p-6 space-y-4">
        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 select-none">
          <ClipboardList className="w-5 h-5 text-amber-600 animate-pulse" />
          <h3 className="font-black text-slate-800 text-xs uppercase tracking-wider">Demand Log</h3>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 select-none">
                <th className="p-3.5 pl-4">Customer Details</th>
                <th className="p-3.5">Requested Product / Details</th>
                <th className="p-3.5">Date Requested</th>
                <th className="p-3.5 text-center">Status</th>
                <th className="p-3.5 text-center">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-xs text-slate-700">
              {demands.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-16 text-center text-slate-405">
                    <div className="flex flex-col items-center gap-2.5 select-none">
                      <AlertCircle className="w-8 h-8 opacity-20 text-slate-400" />
                      <span className="font-semibold text-slate-400">No product demand entries logged.</span>
                    </div>
                  </td>
                </tr>
              ) : (
                demands.map((item) => (
                  <tr key={item.id} className="hover:bg-slate-50/50 transition-colors">
                    <td className="p-3.5 pl-4 text-left space-y-1">
                      <div className="font-bold text-slate-800 flex items-center gap-1.5">
                        <User className="w-3.5 h-3.5 text-slate-400" />
                        {item.customer_name}
                      </div>
                      <div className="flex items-center gap-1 text-[10px] text-slate-500">
                        <Phone className="w-3.5 h-3.5 text-slate-400" />
                        <span>{item.customer_phone}</span>
                      </div>
                    </td>
                    <td className="p-3.5 text-left max-w-sm">
                      <div className="flex items-start gap-1.5">
                        <FileText className="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                        <span className="font-semibold text-slate-800 whitespace-pre-wrap leading-relaxed">{item.demand_details}</span>
                      </div>
                    </td>
                    <td className="p-3.5 text-slate-500 font-medium">
                      <div className="flex items-center gap-1 text-[10px]">
                        <Calendar className="w-3.5 h-3.5 text-slate-400" />
                        <span>{new Date(item.created_at).toLocaleString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}</span>
                      </div>
                    </td>
                    <td className="p-3.5 text-center">
                      <span className={`px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border ${
                        item.status === 'confirmed'
                          ? 'bg-emerald-50 border-emerald-250 text-emerald-700'
                          : 'bg-amber-50 border-amber-200 text-amber-700 animate-pulse'
                      }`}>
                        {item.status}
                      </span>
                    </td>
                    <td className="p-3.5 text-center">
                      <div className="flex items-center justify-center gap-2">
                        {item.status === 'pending' && (
                          <button
                            onClick={() => handleConfirmDemand(item.id)}
                            className="p-1 text-emerald-600 hover:text-white hover:bg-emerald-600 border border-emerald-300 hover:border-transparent rounded-lg transition-colors"
                            title="Confirm request"
                          >
                            <Check className="w-3.5 h-3.5" />
                          </button>
                        )}
                        <button
                          onClick={() => handleDeleteDemand(item.id)}
                          className="p-1 text-slate-400 hover:text-white hover:bg-rose-600 border border-slate-200 hover:border-transparent rounded-lg transition-colors"
                          title="Delete request"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

    </div>
  );
}
