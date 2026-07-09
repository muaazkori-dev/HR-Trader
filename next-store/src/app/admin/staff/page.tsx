'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { 
  UserPlus, 
  Trash2, 
  ShieldCheck, 
  Lock, 
  AlertOctagon,
  UserCheck,
  Phone,
  MapPin,
  Mail
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';

interface StaffProfile {
  id: string;
  name: string;
  phone?: string;
  address?: string;
  role: string;
  created_at: string;
  username?: string;
}

export default function AdminStaffDesk() {
  const router = useRouter();
  const { user, profile, loading } = useAuth();

  // State
  const [staffList, setStaffList] = useState<StaffProfile[]>([]);
  const [isFetchLoading, setIsFetchLoading] = useState(true);

  // Form states for registering a new manager
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');

  const [formError, setFormError] = useState('');
  const [formSuccess, setFormSuccess] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  // 1. Guard: Restrict strictly to Owner
  useEffect(() => {
    if (!loading) {
      if (!user) {
        router.replace('/login');
      } else if (profile?.role !== 'owner') {
        // Managers are not allowed to view/edit staff list
        setStatusMessage("Access Denied: Managers cannot access Staff Panel.");
      }
    }
  }, [user, profile, loading, router]);

  const [statusMessage, setStatusMessage] = useState('');

  // 2. Fetch all staff members (role = manager or owner)
  const fetchStaffMembers = async () => {
    try {
      setIsFetchLoading(true);
      const { data, error } = await supabase
        .from('profiles')
        .select('*')
        .in('role', ['owner', 'manager'])
        .order('name', { ascending: true });

      if (!error && data) {
        setStaffList(data as StaffProfile[]);
      }
    } catch (err) {
      console.error('Error fetching staff list:', err);
    } finally {
      setIsFetchLoading(false);
    }
  };

  useEffect(() => {
    if (profile?.role === 'owner') {
      fetchStaffMembers();
    }
  }, [profile]);

  // 3. Register manager
  const handleRegisterManager = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError('');
    setFormSuccess('');

    if (!email.trim() || !password.trim() || !fullName.trim()) {
      setFormError('Email, Password, and Full Name fields are required.');
      return;
    }

    if (password.length < 6) {
      setFormError('Password must be at least 6 characters long.');
      return;
    }

    setIsSubmitting(true);

    try {
      // Sign up the new user
      const { data: authData, error: authErr } = await supabase.auth.signUp({
        email: email.trim(),
        password: password.trim()
      });

      if (authErr) throw authErr;

      if (!authData.user) {
        throw new Error('Registration failed. Please check credentials.');
      }

      // Create profile row for manager role
      const { error: profileErr } = await supabase
        .from('profiles')
        .insert({
          id: authData.user.id,
          username: email.split('@')[0],
          name: fullName.trim(),
          phone: phone.trim(),
          address: address.trim(),
          role: 'manager'
        });

      if (profileErr) throw profileErr;

      setFormSuccess(`Staff manager '${fullName}' registered successfully!`);
      setEmail('');
      setPassword('');
      setFullName('');
      setPhone('');
      setAddress('');
      fetchStaffMembers(); // Refresh

    } catch (err: any) {
      console.error(err);
      setFormError(err.message || 'Failed to create manager account.');
    } finally {
      setIsSubmitting(false);
    }
  };

  // 4. Remove manager privileges
  const handleRemoveStaff = async (id: string, name: string) => {
    if (id === user?.id) {
      alert("Error: You cannot delete your own logged-in account.");
      return;
    }

    if (!confirm(`Are you sure you want to remove manager privileges for '${name}'?`)) {
      return;
    }

    try {
      // By changing their role to 'customer', they instantly lose dashboard access
      const { error } = await supabase
        .from('profiles')
        .update({ role: 'customer' })
        .eq('id', id);

      if (error) throw error;

      alert(`Privileges for '${name}' revoked successfully.`);
      fetchStaffMembers(); // Refresh
    } catch (err: any) {
      console.error(err);
      alert(err.message || 'Failed to revoke permissions.');
    }
  };

  if (loading || isFetchLoading) {
    return <div className="text-slate-400 text-xs font-semibold text-center py-20">Loading Staff Panel...</div>;
  }

  if (profile?.role !== 'owner') {
    return (
      <div className="bg-white border border-slate-200 rounded-3xl p-10 max-w-lg mx-auto text-center space-y-4 shadow-sm mt-10">
        <AlertOctagon className="w-12 h-12 text-rose-500 mx-auto animate-bounce" />
        <h2 className="text-sm font-black text-slate-800 uppercase tracking-widest">Access Denied</h2>
        <p className="text-xs text-slate-500 leading-relaxed">
          Staff management is restricted exclusively to the **Store Owner**. Managers do not have permissions to register, edit, or delete staff credentials.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6 text-slate-800">
      
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-5 border-b border-slate-200 select-none">
        <div className="text-left">
          <h1 className="text-lg font-black text-slate-800 uppercase tracking-wider">Staff Management Desk</h1>
          <p className="text-xs text-slate-500 mt-1">Register management accounts and control cashier permissions.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* LEFT CARD: REGISTER FORM */}
        <div className="lg:col-span-1 bg-white border border-slate-200 rounded-3xl shadow-sm p-6 space-y-4 self-start">
          <div className="flex items-center gap-2 pb-3 border-b border-slate-100 select-none">
            <UserPlus className="w-5 h-5 text-emerald-600" />
            <h3 className="font-black text-slate-800 text-xs uppercase tracking-wider">Add Staff Manager</h3>
          </div>

          {formError && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-semibold text-left">
              {formError}
            </div>
          )}

          {formSuccess && (
            <div className="p-3 bg-emerald-50 border border-emerald-250 text-emerald-700 text-xs font-semibold text-left">
              {formSuccess}
            </div>
          )}

          <form onSubmit={handleRegisterManager} className="space-y-4 text-left">
            
            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Full Name</label>
              <input
                type="text"
                required
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 text-xs font-semibold text-slate-800"
                placeholder="e.g. Muaaz Kori"
              />
            </div>

            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Email Address</label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 text-xs font-semibold text-slate-800"
                placeholder="e.g. manager.name@gmail.com"
              />
            </div>

            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Password</label>
              <div className="relative">
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full pl-3 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 text-xs font-semibold font-mono text-slate-800"
                  placeholder="••••••••"
                />
                <span className="absolute inset-y-0 right-3 flex items-center text-slate-400">
                  <Lock className="w-3.5 h-3.5" />
                </span>
              </div>
            </div>

            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Contact Number</label>
              <input
                type="text"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 text-xs font-semibold text-slate-800"
                placeholder="e.g. 03337155323"
              />
            </div>

            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Home Address</label>
              <input
                type="text"
                value={address}
                onChange={(e) => setAddress(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 text-xs font-semibold text-slate-800"
                placeholder="e.g. Toor Colony, Lahore"
              />
            </div>

            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white text-xs font-extrabold rounded-xl shadow-md transition-all uppercase tracking-wider flex items-center justify-center gap-1.5"
            >
              <UserPlus className="w-4 h-4" />
              {isSubmitting ? 'Registering...' : 'Register Manager'}
            </button>

          </form>
        </div>

        {/* RIGHT CARD: STAFF DIRECTORY */}
        <div className="lg:col-span-2 bg-white border border-slate-200 rounded-3xl shadow-sm p-6 space-y-4">
          <div className="flex items-center gap-2 pb-3 border-b border-slate-100 select-none">
            <ShieldCheck className="w-5 h-5 text-emerald-600" />
            <h3 className="font-black text-slate-800 text-xs uppercase tracking-wider">Staff Directory</h3>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 select-none">
                  <th className="p-3 pl-4">Staff Name</th>
                  <th className="p-3">Role</th>
                  <th className="p-3">Contact</th>
                  <th className="p-3 text-center">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-xs text-slate-700">
                {staffList.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="p-8 text-center text-slate-400 font-semibold">No active staff members found.</td>
                  </tr>
                ) : (
                  staffList.map((item) => (
                    <tr key={item.id} className="hover:bg-slate-50/50 transition-colors">
                      <td className="p-3 pl-4 text-left">
                        <div className="font-bold text-slate-850 flex items-center gap-1.5">
                          {item.name}
                          {item.id === user?.id && (
                            <span className="px-1.5 py-0.5 bg-slate-100 text-[8px] font-bold rounded text-slate-500 uppercase">You</span>
                          )}
                        </div>
                        <span className="text-[10px] text-slate-400 font-mono mt-0.5 block">{item.username}@hrtraders.com</span>
                      </td>
                      <td className="p-3">
                        <span className={`px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border ${
                          item.role === 'owner' 
                            ? 'bg-rose-50 border-rose-200 text-rose-700' 
                            : 'bg-emerald-50 border-emerald-250 text-emerald-700'
                        }`}>
                          {item.role}
                        </span>
                      </td>
                      <td className="p-3 text-left space-y-0.5">
                        {item.phone && (
                          <div className="flex items-center gap-1 text-[10px] text-slate-500">
                            <Phone className="w-3 h-3 text-slate-400" />
                            <span>{item.phone}</span>
                          </div>
                        )}
                        {item.address && (
                          <div className="flex items-center gap-1 text-[10px] text-slate-500">
                            <MapPin className="w-3 h-3 text-slate-400" />
                            <span className="truncate max-w-[150px]">{item.address}</span>
                          </div>
                        )}
                      </td>
                      <td className="p-3 text-center">
                        {item.id !== user?.id && item.role !== 'owner' ? (
                          <button
                            onClick={() => handleRemoveStaff(item.id, item.name)}
                            className="p-1.5 text-slate-400 hover:text-rose-600 transition-colors rounded-lg hover:bg-rose-50 border border-transparent hover:border-rose-200"
                            title="Revoke manager role"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        ) : (
                          <span className="text-[10px] text-slate-400 font-semibold select-none">—</span>
                        )}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </div>
  );
}
