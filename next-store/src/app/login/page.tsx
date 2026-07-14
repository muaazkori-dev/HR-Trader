'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';
import { Header } from '@/components/Header';
import { Footer } from '@/components/Footer';
import { 
  Mail, 
  Lock, 
  User, 
  Phone, 
  MapPin, 
  AlertCircle, 
  CheckCircle2, 
  ArrowRight,
  LogIn
} from 'lucide-react';

function LoginContent() {
  const router = useRouter();
  const { user, profile, loading } = useAuth();

  // Auth Modes: 'login' | 'signup'
  const [mode, setMode] = useState<'login' | 'signup'>('login');

  // Input fields
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');

  // UI state
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  const searchParams = useSearchParams();
  const redirectParam = searchParams.get('redirect') || '/';

  // Redirect if already logged in
  useEffect(() => {
    if (!loading && user) {
      if (profile?.role === 'owner' || profile?.role === 'manager') {
        router.replace('/admin');
      } else {
        router.replace(redirectParam);
      }
    }
  }, [user, profile, loading, router, redirectParam]);

  const handleAuthSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg('');
    setSuccessMsg('');

    if (!email.trim() || !password.trim()) {
      setErrorMsg('Please enter email and password.');
      return;
    }

    if (mode === 'signup' && (!name.trim() || !phone.trim() || !address.trim())) {
      setErrorMsg('All registration fields are required.');
      return;
    }

    setSubmitting(true);

    try {
      if (mode === 'login') {
        // Sign In
        const { error } = await supabase.auth.signInWithPassword({
          email: email.trim(),
          password: password.trim(),
        });

        if (error) {
          throw new Error(error.message);
        }

        setSuccessMsg('Successfully signed in!');
      } else {
        // Sign Up
        const { data, error } = await supabase.auth.signUp({
          email: email.trim(),
          password: password.trim(),
        });

        if (error) {
          throw new Error(error.message);
        }

        if (data.user) {
          // Determine starting role
          let startingRole = 'customer';
          const lowerEmail = email.trim().toLowerCase();
          if (lowerEmail === 'owner@hrtraders.com' || lowerEmail.includes('owner') || lowerEmail.includes('admin')) {
            startingRole = 'owner';
          }

          // Insert into custom profiles table
          const { error: profileErr } = await supabase.from('profiles').insert([
            {
              id: data.user.id,
              username: email.trim().split('@')[0],
              role: startingRole,
              name: name.trim(),
              phone: phone.trim(),
              address: address.trim(),
            },
          ]);

          if (profileErr) {
            throw new Error('Failed to create profile: ' + profileErr.message);
          }

          setSuccessMsg('Sign up successful! You can now log in.');
          setMode('login');
          // Clear password
          setPassword('');
        }
      }
    } catch (err: any) {
      setErrorMsg(err.message || 'Authentication failed. Please verify credentials.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleGoogleLogin = async () => {
    setErrorMsg('');
    try {
      const { error } = await supabase.auth.signInWithOAuth({
        provider: 'google',
        options: {
          redirectTo: `${window.location.origin}/login`,
        },
      });
      if (error) throw error;
    } catch (err: any) {
      setErrorMsg(err.message || 'Google authentication failed.');
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col min-h-screen bg-slate-50/50">
        <Header />
        <div className="flex-grow flex items-center justify-center p-24 text-slate-400 font-semibold text-xs">
          Loading Auth Portal...
        </div>
        <Footer />
      </div>
    );
  }

  return (
    <div className="flex flex-col min-h-screen bg-slate-50/50">
      <Header />

      <main className="flex-grow max-w-md mx-auto px-4 py-12 w-full flex flex-col justify-center">
        
        {/* Card wrapper */}
        <div className="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 text-left">
          
          <div className="text-center space-y-2">
            <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto shadow-sm">
              <LogIn className="w-6 h-6" />
            </div>
            <h2 className="text-lg font-black text-slate-800 uppercase tracking-wider">
              {mode === 'login' ? 'Customer Sign In' : 'Create Account'}
            </h2>
            <p className="text-[11px] text-slate-450">
              {mode === 'login' ? 'Enter credentials to track orders & checkout fast' : 'Register details for first-order Free Delivery'}
            </p>
          </div>

          {/* Feedback Messages */}
          {errorMsg && (
            <div className="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-semibold flex items-center gap-2">
              <AlertCircle className="w-4.5 h-4.5 flex-shrink-0" />
              <span>{errorMsg}</span>
            </div>
          )}

          {successMsg && (
            <div className="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-xs font-semibold flex items-center gap-2">
              <CheckCircle2 className="w-4.5 h-4.5 flex-shrink-0" />
              <span>{successMsg}</span>
            </div>
          )}

          {/* Form */}
          <form onSubmit={handleAuthSubmit} className="space-y-4">
            
            {/* Name (Sign Up only) */}
            {mode === 'signup' && (
              <div className="space-y-1">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Full Name</label>
                <div className="relative">
                  <input
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    required
                    placeholder="Enter your name"
                    className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 font-semibold"
                  />
                  <User className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-slate-400" />
                </div>
              </div>
            )}

            {/* Email */}
            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Email Address</label>
              <div className="relative">
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  placeholder="name@example.com"
                  className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 font-semibold"
                />
                <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-slate-400" />
              </div>
            </div>

            {/* Password */}
            <div className="space-y-1">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Password</label>
              <div className="relative">
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  placeholder="••••••••"
                  className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 font-semibold"
                />
                <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-slate-400" />
              </div>
            </div>

            {/* Phone (Sign Up only) */}
            {mode === 'signup' && (
              <div className="space-y-1">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Phone Number</label>
                <div className="relative">
                  <input
                    type="tel"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    required
                    placeholder="e.g. 03033943814"
                    className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 font-mono font-semibold"
                  />
                  <Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-slate-400" />
                </div>
              </div>
            )}

            {/* Address (Sign Up only) */}
            {mode === 'signup' && (
              <div className="space-y-1">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Delivery Address</label>
                <div className="relative">
                  <textarea
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    required
                    rows={2}
                    placeholder="Enter complete shipping address"
                    className="w-full px-4 py-2.5 pl-10 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-slate-800 placeholder-slate-400 resize-none font-medium"
                  />
                  <MapPin className="absolute left-3.5 top-3 w-4.5 h-4.5 text-slate-400" />
                </div>
              </div>
            )}

            {/* Submit Button */}
            <button
              type="submit"
              disabled={submitting}
              className="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs shadow-lg shadow-emerald-600/10 transition-all flex items-center justify-center gap-1.5 disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none"
            >
              {submitting ? 'Authenticating...' : mode === 'login' ? 'Sign In Now' : 'Register Account'}
              <ArrowRight className="w-4 h-4" />
            </button>

          </form>

          {/* Divider */}
          <div className="flex items-center my-2">
            <div className="flex-1 border-t border-slate-200"></div>
            <span className="px-3 text-[9px] font-bold text-slate-400 uppercase tracking-widest">OR</span>
            <div className="flex-1 border-t border-slate-200"></div>
          </div>

          {/* Google OAuth Button */}
          <button
            type="button"
            onClick={handleGoogleLogin}
            className="w-full py-2.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2 shadow-sm active:scale-[0.98]"
          >
            {/* Google Colored Icon */}
            <svg className="w-4 h-4" viewBox="0 0 24 24">
              <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.85z"
              />
              <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.85c.87-2.6 3.3-4.53 6.16-4.53z"
              />
            </svg>
            Continue with Google
          </button>

          {/* Toggle mode */}
          <div className="text-center pt-2 text-xs">
            <span className="text-slate-400">
              {mode === 'login' ? "Don't have an account? " : "Already have an account? "}
            </span>
            <button
              onClick={() => {
                setMode(mode === 'login' ? 'signup' : 'login');
                setErrorMsg('');
                setSuccessMsg('');
              }}
              className="text-emerald-600 font-bold hover:underline"
            >
              {mode === 'login' ? 'Sign Up' : 'Log In'}
            </button>
          </div>

        </div>

      </main>

      <Footer />
    </div>
  );
}

export default function Login() {
  return (
    <Suspense fallback={
      <div className="flex flex-col min-h-screen bg-slate-50/50">
        <Header />
        <div className="flex-grow flex items-center justify-center p-24 text-slate-400 font-semibold text-xs">
          Loading Auth Portal...
        </div>
        <Footer />
      </div>
    }>
      <LoginContent />
    </Suspense>
  );
}
