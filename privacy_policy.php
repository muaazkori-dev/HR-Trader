<?php
// HR Traders Official Privacy Policy Page (Google Play Store Compliant)
$page_title = "Privacy Policy - HR Traders";
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy Policy - HR Traders</title>
  <link rel="icon" href="assets/images/logo.png" type="image/png">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans">

  <!-- Header -->
  <header class="bg-emerald-700 text-white shadow-md">
    <div class="max-w-4xl mx-auto px-4 py-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black tracking-wide">HR TRADERS</h1>
        <p class="text-xs text-emerald-100 uppercase tracking-widest">Online Grocery & Grain Delivery</p>
      </div>
      <a href="index.php" class="text-xs font-bold bg-white text-emerald-800 px-3.5 py-1.5 rounded-full hover:bg-emerald-50 transition shadow">
        &larr; Back to Store
      </a>
    </div>
  </header>

  <!-- Main Content Container -->
  <main class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-10 space-y-8">
      
      <div>
        <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full mb-3">Google Play Compliant</span>
        <h2 class="text-3xl font-extrabold text-slate-900">Privacy Policy</h2>
        <p class="text-xs text-slate-500 mt-1">Last Updated: October 2026 | Effective Date: October 2026</p>
      </div>

      <p class="text-slate-600 leading-relaxed">
        Welcome to <strong>HR Traders</strong> (&ldquo;we&rdquo;, &ldquo;our&rdquo;, or &ldquo;us&rdquo;). We operate the <strong>HR Traders</strong> e-commerce grocery storefront (thehrtraders.com) and associated mobile applications (Customer App, Rider App, and Store Manager App). This Privacy Policy explains how we collect, use, disclose, and protect your information when you access or use our applications and services.
      </p>

      <!-- Section 1 -->
      <section class="space-y-3">
        <h3 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-2">1. Information We Collect</h3>
        <p class="text-slate-600 text-sm leading-relaxed">
          We collect information that you provide directly to us when creating an account, browsing products, or placing an order:
        </p>
        <ul class="list-disc list-inside space-y-1 text-sm text-slate-600 ml-2">
          <li><strong>Personal Contact Information:</strong> Name, phone number, and physical delivery address.</li>
          <li><strong>Order Details:</strong> Items purchased, order amounts, payment preference (Cash on Delivery / COD), and special delivery instructions.</li>
          <li><strong>Account Credentials:</strong> Username and securely hashed passwords.</li>
        </ul>
      </section>

      <!-- Section 2: Location Data -->
      <section class="space-y-3 bg-emerald-50/50 p-5 rounded-xl border border-emerald-100">
        <h3 class="text-xl font-bold text-emerald-900 border-b border-emerald-200 pb-2">2. Geolocation Information & Location Permissions</h3>
        <p class="text-slate-700 text-sm leading-relaxed">
          Our mobile applications request access to your device's location to deliver orders accurately:
        </p>
        <ul class="list-disc list-inside space-y-1.5 text-sm text-slate-700 ml-2">
          <li><strong>Customer App (Precise GPS Pin):</strong> When checking out, you may choose to pin your exact location on Google Maps. This ensures the delivery rider can reach your doorstep in rural or dense colony areas without getting lost.</li>
          <li><strong>Rider App (Background / Foreground Navigation):</strong> For delivery staff, we access location while the app is active to route orders efficiently from our store in Tando Adam to the customer's delivery destination.</li>
          <li><strong>No Tracking:</strong> We do NOT sell, lease, or monetize your location data. Location data is only used during the active delivery cycle.</li>
        </ul>
      </section>

      <!-- Section 3 -->
      <section class="space-y-3">
        <h3 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-2">3. How We Use Your Information</h3>
        <ul class="list-disc list-inside space-y-1 text-sm text-slate-600 ml-2">
          <li>To process, fulfill, and deliver your grocery and household orders.</li>
          <li>To send live order status notifications (SMS, WhatsApp, or Push Notifications).</li>
          <li>To print delivery invoices and thermal receipts for order dispatch.</li>
          <li>To provide customer support and handle order queries.</li>
          <li>To prevent fraudulent orders and maintain platform security.</li>
        </ul>
      </section>

      <!-- Section 4 -->
      <section class="space-y-3">
        <h3 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-2">4. Data Sharing & Security</h3>
        <p class="text-slate-600 text-sm leading-relaxed">
          We respect your privacy. We <strong>never sell</strong> your personal information to third-party advertisers. Information is shared only with:
        </p>
        <ul class="list-disc list-inside space-y-1 text-sm text-slate-600 ml-2">
          <li><strong>Assigned Delivery Riders:</strong> Only your name, delivery address, phone number, and pinned GPS location are provided to the designated rider fulfilling your order.</li>
          <li><strong>Cloud Infrastructure Providers:</strong> Hostinger secure databases and Google Cloud / Firebase for notifications.</li>
        </ul>
      </section>

      <!-- Section 5 -->
      <section class="space-y-3">
        <h3 class="text-xl font-bold text-slate-900 border-b border-slate-100 pb-2">5. Data Retention & Account Deletion</h3>
        <p class="text-slate-600 text-sm leading-relaxed">
          You retain the right to access, update, or permanently delete your account and personal data. To request account deletion or data removal, please contact our support team at <strong>info@hrtraders.com</strong> or call <strong>+92 303 3943814</strong>. Requests are processed within 48 business hours.
        </p>
      </section>

      <!-- Section 6: Contact Us -->
      <section class="space-y-3 border-t border-slate-100 pt-6">
        <h3 class="text-xl font-bold text-slate-900">6. Contact Information</h3>
        <p class="text-slate-600 text-sm">
          If you have any questions or feedback regarding this Privacy Policy, please contact us:
        </p>
        <div class="text-sm text-slate-700 bg-slate-100 p-4 rounded-xl space-y-1">
          <p><strong>Store:</strong> HR Traders Grocery & Retail</p>
          <p><strong>Address:</strong> Toor Colony, Front of Hira Public School, Tando Adam, Sindh, Pakistan</p>
          <p><strong>Phone:</strong> +92 303 3943814</p>
          <p><strong>Email:</strong> info@hrtraders.com</p>
          <p><strong>Website:</strong> <a href="https://thehrtraders.com" class="text-emerald-700 underline font-semibold">https://thehrtraders.com</a></p>
        </div>
      </section>

    </div>
  </main>

  <footer class="text-center text-xs text-slate-400 py-6">
    &copy; <?php echo date('Y'); ?> HR Traders. All rights reserved.
  </footer>

</body>
</html>
