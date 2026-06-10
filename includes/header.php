<?php
// HR Traders E-commerce Header Component
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Calculate cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}

// Fetch dynamic SEO configuration settings
$seo_title = get_setting('seo_title', STORE_NAME . ' - Premium Online Grocery & Grain Store');
$seo_desc = get_setting('seo_description', 'Shop the freshest grains, cold drinks, dairy, and cosmetics online with fast delivery.');
$seo_key = get_setting('seo_keywords', 'grain store, online grocery, cosmetics shop, dry fruits, fresh milk');
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seo_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_desc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo_key); ?>">
    <!-- Define BASE_URL globally for client-side JS AJAX fetches -->
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <!-- Locally saved Tailwind CSS compiler script (offline-ready) -->
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--theme-primary)',
                        emerald: {
                            50: 'var(--theme-emerald-50, #f0fdf4)',
                            100: 'var(--theme-emerald-100, #dcfce7)',
                            200: 'var(--theme-emerald-200, #bbf7d0)',
                            500: 'var(--theme-primary-hover)',
                            600: 'var(--theme-primary)',
                            700: 'var(--theme-primary-hover)',
                            800: 'var(--theme-primary-hover)',
                        },
                        darkbg: '#0b0f19',  // Sleek deep background
                        glass: 'rgba(255, 255, 255, 0.85)',
                    }
                }
            }
        }
    </script>
    <!-- Favicon link -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png?v=2.1">
    <!-- Google Fonts (Preconnected & Loaded in Parallel) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.0">
    <!-- FontAwesome Icons for graphics (Fallback to unicode if slow) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- PWA Support -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>manifest.json">
    <meta name="theme-color" content="#10b981">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo BASE_URL; ?>sw.js')
                    .then(reg => console.log('Service Worker registered successfully.'))
                    .catch(err => console.log('Service Worker registration failed: ', err));
            });
        }
    </script>
</head>
<body class="theme-<?php echo get_setting('active_theme', 'emerald_green'); ?> bg-slate-50 text-slate-800 min-h-screen flex flex-col">

<!-- STICKY HEADER -->
<header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm text-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-4">
            
            <!-- Brand Logo -->
            <a href="<?php echo BASE_URL; ?>" class="flex-shrink-0 flex items-center gap-2.5">
                <div class="h-11 w-11 rounded-full overflow-hidden border border-slate-200 bg-white shadow-sm flex-shrink-0 flex items-center justify-center p-1">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo.png?v=2.1" alt="HR Traders Logo" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tight leading-none text-slate-900 flex items-center gap-1.5">
                        <?php echo htmlspecialchars(STORE_NAME); ?>
                        <span class="bg-emerald-50 text-emerald-600 text-[10px] px-1.5 py-0.5 rounded-full border border-emerald-200 font-bold hidden sm:inline-block">Grocery</span>
                    </span>
                    <span class="text-[9px] text-slate-400 font-normal uppercase tracking-wider mt-0.5">Premium Store</span>
                </div>
            </a>

            <!-- Search Bar (Live AJAX Search) -->
            <form action="<?php echo BASE_URL; ?>shop.php" method="GET" class="flex-1 max-w-lg relative hidden md:block">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="storefront-search" name="query" autocomplete="off" value="<?php echo isset($_GET['query']) ? sanitize($_GET['query']) : ''; ?>"
                           class="w-full pl-10 pr-10 py-2 bg-slate-100 border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 placeholder-slate-400 text-sm text-slate-900 transition-all"
                           placeholder="Search pulses, snacks, drinks, ice-creams...">
                    <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-emerald-600 transition-colors">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                <!-- Dropdown suggestions wrapper -->
                <div id="search-results-dropdown" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-2xl hidden z-50 max-h-96 overflow-y-auto">
                    <!-- Loaded dynamically via AJAX -->
                </div>
            </form>

            <!-- Header Menu Actions -->
            <div class="flex items-center gap-4">
                <nav class="hidden lg:flex items-center gap-6 text-sm font-medium text-slate-655">
                    <a href="<?php echo BASE_URL; ?>" class="hover:text-emerald-600 transition-colors">Home</a>
                    <a href="<?php echo BASE_URL; ?>shop.php" class="hover:text-emerald-600 transition-colors">All Products</a>
                    <a href="<?php echo BASE_URL; ?>shop.php?category=anaj#shop-container" class="hover:text-emerald-600 transition-colors">Anaj</a>
                    <a href="<?php echo BASE_URL; ?>shop.php?category=ice_cream#shop-container" class="hover:text-emerald-600 transition-colors">Ice Cream</a>
                    <a href="<?php echo BASE_URL; ?>shop.php?category=beverages#shop-container" class="hover:text-emerald-600 transition-colors">Beverages</a>
                    <a href="<?php echo BASE_URL; ?>shop.php?category=milk#shop-container" class="hover:text-emerald-600 transition-colors">Milk</a>
                    <a href="<?php echo BASE_URL; ?>shop.php?category=cosmetics#shop-container" class="hover:text-emerald-600 transition-colors">Cosmetics</a>
                    <a href="<?php echo BASE_URL; ?>shop.php?category=snacks#shop-container" class="hover:text-emerald-600 transition-colors">Snacks</a>
                </nav>



                <!-- Cart Button -->
                <button onclick="toggleCartDrawer(true)" class="relative p-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-xl transition-all">
                    <i class="fas fa-shopping-basket text-lg text-slate-700"></i>
                    <span id="cart-badge" class="absolute -top-1.5 -right-1.5 bg-emerald-600 text-white font-bold text-xs w-5 h-5 rounded-full flex items-center justify-center transition-all <?php echo $cart_count > 0 ? '' : 'hidden'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- MOBILE SEARCH BAR -->
<form action="<?php echo BASE_URL; ?>shop.php" method="GET" class="p-3 bg-white border-b border-slate-200 md:hidden block relative">
    <div class="relative">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
            <i class="fas fa-search"></i>
        </span>
        <input type="text" id="storefront-search-mobile" name="query" autocomplete="off" value="<?php echo isset($_GET['query']) ? sanitize($_GET['query']) : ''; ?>"
               class="w-full pl-10 pr-10 py-2 bg-slate-100 border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 placeholder-slate-400 text-sm text-slate-900"
               placeholder="Search grocery...">
        <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-emerald-600 transition-colors">
            <i class="fas fa-arrow-right"></i>
        </button>
    </div>
    <!-- Dropdown suggestions wrapper for mobile -->
    <div id="search-results-dropdown-mobile" class="absolute left-3 right-3 mt-2 bg-white border border-slate-200 rounded-xl shadow-2xl hidden z-50 max-h-80 overflow-y-auto">
        <!-- Loaded dynamically via AJAX -->
    </div>
</form>

<!-- CART DRAWER (SLIDEOUT PANEL) -->
<div id="cart-drawer-backdrop" onclick="toggleCartDrawer(false)" class="fixed inset-0 bg-slate-900/40 z-50 transition-opacity duration-300 opacity-0 pointer-events-none"></div>
<div id="cart-drawer" class="fixed right-0 top-0 bottom-0 w-full sm:w-[400px] bg-white border-l border-slate-200 z-50 translate-x-full transition-transform duration-300 flex flex-col text-slate-800">
    <!-- Drawer Header -->
    <div class="p-4 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fas fa-shopping-cart text-emerald-600"></i>
            <h3 class="font-bold text-lg text-slate-850">My Shopping Cart</h3>
        </div>
        <button onclick="toggleCartDrawer(false)" class="p-2 text-slate-400 hover:text-slate-800 rounded-lg hover:bg-slate-100">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- Drawer Contents (AJAX Loaded Items) -->
    <div id="cart-items-container" class="flex-1 overflow-y-auto p-4 space-y-4">
        <!-- Rendered dynamically -->
        <p class="text-slate-400 text-center py-10">Your cart is empty.</p>
    </div>

    <!-- Drawer Footer -->
    <div class="p-4 border-t border-slate-200 bg-slate-50 space-y-4">
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 font-medium">Subtotal</span>
            <span id="cart-drawer-total" class="font-extrabold text-lg text-emerald-600">Rs. 0.00</span>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <button onclick="clearCart()" class="py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-xl text-sm transition-colors">
                Clear Cart
            </button>
            <a href="<?php echo BASE_URL; ?>checkout.php" class="py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-sm text-center transition-colors shadow-lg shadow-emerald-600/10">
                Checkout &rarr;
            </a>
        </div>
    </div>
</div>

<main class="flex-1">
