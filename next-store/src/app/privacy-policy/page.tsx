import React from 'react';
import Link from 'next/link';

export const metadata = {
  title: 'Privacy Policy | HR Traders',
  description: 'Official Privacy Policy for HR Traders e-commerce storefront and mobile applications.',
};

export default function PrivacyPolicyPage() {
  return (
    <div className="min-h-screen bg-slate-50 text-slate-800 antialiased font-sans">
      <header className="bg-emerald-700 text-white shadow-md">
        <div className="max-w-4xl mx-auto px-4 py-6 flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-black tracking-wide">HR TRADERS</h1>
            <p className="text-xs text-emerald-100 uppercase tracking-widest">Online Grocery & Grain Delivery</p>
          </div>
          <Link href="/" className="text-xs font-bold bg-white text-emerald-800 px-3.5 py-1.5 rounded-full hover:bg-emerald-50 transition shadow">
            &larr; Back to Store
          </Link>
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-4 py-10">
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-10 space-y-8">
          <div>
            <span className="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full mb-3">Google Play Compliant</span>
            <h2 className="text-3xl font-extrabold text-slate-900">Privacy Policy</h2>
            <p className="text-xs text-slate-500 mt-1">Last Updated: October 2026 | Effective Date: October 2026</p>
          </div>

          <p className="text-slate-600 leading-relaxed">
            Welcome to <strong>HR Traders</strong> (&ldquo;we&rdquo;, &ldquo;our&rdquo;, or &ldquo;us&rdquo;). We operate the <strong>HR Traders</strong> e-commerce grocery storefront (thehrtraders.com) and associated mobile applications (Customer App, Rider App, and Store Manager App). This Privacy Policy explains how we collect, use, disclose, and protect your information when you access or use our applications and services.
          </p>

          <section className="space-y-3">
            <h3 className="text-xl font-bold text-slate-900 border-b border-slate-100 pb-2">1. Information We Collect</h3>
            <ul className="list-disc list-inside space-y-1 text-sm text-slate-600 ml-2">
              <li><strong>Personal Contact Information:</strong> Name, phone number, and physical delivery address.</li>
              <li><strong>Order Details:</strong> Items purchased, order amounts, payment preference (Cash on Delivery / COD), and delivery instructions.</li>
              <li><strong>Account Credentials:</strong> Username and securely hashed passwords.</li>
            </ul>
          </section>

          <section className="space-y-3 bg-emerald-50/50 p-5 rounded-xl border border-emerald-100">
            <h3 className="text-xl font-bold text-emerald-900 border-b border-emerald-200 pb-2">2. Geolocation Information & Location Permissions</h3>
            <p className="text-slate-700 text-sm leading-relaxed">
              Our mobile applications request access to your device's location to deliver orders accurately:
            </p>
            <ul className="list-disc list-inside space-y-1.5 text-sm text-slate-700 ml-2">
              <li><strong>Customer App (Precise GPS Pin):</strong> When checking out, you may pin your location on Google Maps so the delivery rider can reach your exact address without delay.</li>
              <li><strong>Rider App (Navigation):</strong> Used to route delivery personnel directly to customer homes.</li>
              <li><strong>No Data Brokering:</strong> We do NOT sell, lease, or monetize your location data.</li>
            </ul>
          </section>

          <section className="space-y-3">
            <h3 className="text-xl font-bold text-slate-900 border-b border-slate-100 pb-2">3. How We Use Your Information</h3>
            <ul className="list-disc list-inside space-y-1 text-sm text-slate-600 ml-2">
              <li>To fulfill and deliver your grocery orders promptly.</li>
              <li>To provide order tracking updates via push notifications and WhatsApp.</li>
              <li>To print thermal receipt slips for delivery packaging.</li>
              <li>To maintain account security and fraud prevention.</li>
            </ul>
          </section>

          <section className="space-y-3 border-t border-slate-100 pt-6">
            <h3 className="text-xl font-bold text-slate-900">4. Contact & Account Deletion</h3>
            <div className="text-sm text-slate-700 bg-slate-100 p-4 rounded-xl space-y-1">
              <p><strong>Store:</strong> HR Traders Grocery & Retail</p>
              <p><strong>Address:</strong> Toor Colony, Front of Hira Public School, Tando Adam, Sindh, Pakistan</p>
              <p><strong>Phone:</strong> +92 303 3943814</p>
              <p><strong>Email:</strong> info@hrtraders.com</p>
              <p><strong>Website:</strong> https://thehrtraders.com</p>
            </div>
          </section>
        </div>
      </main>

      <footer className="text-center text-xs text-slate-400 py-6">
        &copy; {new Date().getFullYear()} HR Traders. All rights reserved.
      </footer>
    </div>
  );
}
