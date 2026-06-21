<?php
// HR Traders Asset & QR Code Generator Tool
require_once __DIR__ . '/config/db.php';

$qr_data = 'https://thehrtraders.com/';
$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=' . urlencode($qr_data);
$dest_path = __DIR__ . '/assets/images/qr_code.png';

// Try to download and save the QR code locally
$img_data = @file_get_contents($qr_url);
$download_status = false;
if ($img_data) {
    // Ensure directory exists
    $dir = dirname($dest_path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (@file_put_contents($dest_path, $img_data)) {
        $download_status = true;
    }
}

// Set HTML class based on theme type (light or dark)
$current_theme = get_setting('active_theme', 'emerald_green');
$dark_themes = ['midnight_indigo', 'cyberpunk_neon', 'deep_purple', 'forest_dark', 'forest_green', 'crimson_dark', 'crimson_rose'];
$html_class = in_array($current_theme, $dark_themes) ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $html_class; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Traders - Assets Download Portal</title>
    <!-- Locally saved Tailwind CSS compiler script (offline-ready) -->
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--theme-primary, #10b981)',
                        darkbg: '#0b0f19',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-darkbg text-slate-800 dark:text-slate-100 min-h-screen flex flex-col justify-between">

    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shadow-sm py-4">
        <div class="max-w-4xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="HR Traders Logo" class="h-10 w-10 object-contain">
                <div>
                    <h1 class="text-lg font-bold leading-tight">HR Traders</h1>
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Brand Assets & QR Scanner Portal</p>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>" class="text-xs bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 px-3 py-1.5 rounded-xl font-bold hover:bg-emerald-100 transition-all">&larr; Back to Shop</a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-12 flex-1 w-full">
        <div class="text-center space-y-3 mb-12">
            <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Customer Brand Identity Assets</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">Download the high-quality official store logo for social media profiles and print-ready QR codes for offline scanning.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Card 1: Official Logo -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col items-center justify-between text-center gap-6">
                <div class="space-y-2">
                    <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Social Media & Profile Logo</span>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Official Brand Logo</h3>
                    <p class="text-xs text-slate-500 max-w-[280px]">High-resolution transparent circular logo to use on WhatsApp, Facebook, Instagram, and TikTok accounts.</p>
                </div>

                <div class="w-48 h-48 rounded-full border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 flex items-center justify-center p-6 shadow-inner">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="HR Traders Logo" class="max-w-full max-h-full object-contain">
                </div>

                <a href="<?php echo BASE_URL; ?>assets/images/logo.png" download="hr_traders_logo.png" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm rounded-2xl transition-all shadow-lg shadow-emerald-600/10 text-center">
                    Download Logo Image
                </a>
            </div>

            <!-- Card 2: QR Scanner Code -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col items-center justify-between text-center gap-6">
                <div class="space-y-2">
                    <span class="bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Print & Scan QR Code</span>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Website QR Scanner</h3>
                    <p class="text-xs text-slate-500 max-w-[280px]">Print this QR code on banners, flyers, or business cards. Customers can scan this to visit your store instantly.</p>
                </div>

                <div class="w-48 h-48 border border-slate-200 dark:border-slate-800 bg-white flex items-center justify-center p-3 rounded-2xl shadow-inner relative">
                    <?php if ($download_status): ?>
                        <img src="<?php echo BASE_URL; ?>assets/images/qr_code.png?v=<?php echo time(); ?>" alt="Website QR Code" class="w-full h-full object-contain">
                    <?php else: ?>
                        <div class="text-xs text-rose-500 font-bold p-4">
                            Failed to generate. Make sure the server has active internet access.
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?php echo BASE_URL; ?>assets/images/qr_code.png" download="hr_traders_qr_code.png" class="w-full py-3 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-sm rounded-2xl transition-all shadow-lg text-center">
                    Download QR Scanner Code
                </a>
            </div>
        </div>

        <div class="mt-12 p-5 bg-blue-50 dark:bg-slate-800/40 border border-blue-200 dark:border-slate-800 rounded-2xl flex items-start gap-3.5">
            <span class="text-blue-500 text-xl"><i class="fas fa-info-circle"></i></span>
            <div class="space-y-1">
                <h4 class="font-bold text-sm text-blue-900 dark:text-blue-200">How to use the QR Code?</h4>
                <p class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">
                    Aap is QR Code ko design kar ke shop ke counters par, packaging flyers par ya pamphlets par print karwa sakte hain. Jab bhi koi customer apne phone ke camera ya scanner se isey scan karega, unka phone directly <strong>https://thehrtraders.com/</strong> website open kar dega.
                </p>
            </div>
        </div>
    </main>

    <footer class="bg-slate-100 dark:bg-slate-900/50 py-6 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-500">
        <p>&copy; <?php echo date('Y'); ?> HR Traders. All rights reserved.</p>
    </footer>

</body>
</html>
