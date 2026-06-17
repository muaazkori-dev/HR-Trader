<?php
// HR Traders Owner Dashboard (Super Admin)
// Access restricted to role: 'owner'

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Role restriction
require_role(['owner', 'manager']);

$success_message = "";
$error_message = "";

// Handle creating new staff accounts (manager / owner)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_staff') {
    if (!is_owner()) {
        $error_message = "Access Denied: Managers do not have permission to register staff accounts.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'manager');
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($username) || empty($password) || empty($name)) {
            $error_message = "Username, Password, and Full Name fields are required.";
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :user");
                $stmt->execute(['user' => $username]);
                if ($stmt->fetch()) {
                    $error_message = "Username '{$username}' is already registered in database.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, phone, address) 
                                           VALUES (:username, :password, :role, :name, :phone, :address)");
                    $stmt->execute([
                        'username' => $username,
                        'password' => $hashed_password,
                        'role' => $role,
                        'name' => $name,
                        'phone' => $phone,
                        'address' => $address
                    ]);
                    $success_message = "Staff account '{$username}' registered successfully.";
                }
            } catch (PDOException $e) {
                $error_message = "Failed to register staff account: " . $e->getMessage();
            }
        }
    }
}

// Handle deleting staff accounts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_staff') {
    if (!is_owner()) {
        $error_message = "Access Denied: Managers cannot delete staff accounts.";
    } else {
        $staff_id = (int)$_POST['staff_id'];
        if ($staff_id === (int)$_SESSION['user_id']) {
            $error_message = "Error: You cannot delete your own logged-in account.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute(['id' => $staff_id]);
                $success_message = "Staff account deleted successfully.";
            } catch (PDOException $e) {
                $error_message = "Failed to delete staff account: " . $e->getMessage();
            }
        }
    }
}

// Handle deleting product demands
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_demand') {
    $demand_id = (int)$_POST['demand_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM product_demands WHERE id = :id");
        $stmt->execute(['id' => $demand_id]);
        $success_message = "Customer product demand request deleted successfully.";
    } catch (PDOException $e) {
        $error_message = "Failed to delete demand request: " . $e->getMessage();
    }
}

// Handle confirming product demands
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_demand') {
    $demand_id = (int)$_POST['demand_id'];
    try {
        $stmt = $pdo->prepare("UPDATE product_demands SET status = 'confirmed' WHERE id = :id");
        $stmt->execute(['id' => $demand_id]);
        $success_message = "Customer product demand request confirmed successfully.";
    } catch (PDOException $e) {
        $error_message = "Failed to confirm demand request: " . $e->getMessage();
    }
}

// Handle resetting staff password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    if (!is_owner()) {
        $error_message = "Access Denied: Managers cannot reset staff passwords.";
    } else {
        $staff_id = (int)$_POST['staff_id'];
        $new_password = trim($_POST['new_password'] ?? '');
        if (empty($new_password)) {
            $error_message = "Error: Password cannot be empty.";
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
                $stmt->execute(['pass' => $hashed_password, 'id' => $staff_id]);
                $success_message = "Password for staff member updated successfully.";
            } catch (PDOException $e) {
                $error_message = "Failed to reset password: " . $e->getMessage();
            }
        }
    }
}

// Handle Settings updates (theme, timings, status, threshold, whatsapp template)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    if (!is_owner()) {
        $error_message = "Access Denied: Managers cannot modify settings.";
    } else {
        // 1. Theme
        if (isset($_POST['active_theme'])) {
            $theme = trim($_POST['active_theme']);
            $valid_themes = [
                'emerald_green', 'midnight_indigo', 'rose_gold', 'cyberpunk_neon', 
                'slate_blue', 'amber_honey', 'deep_purple', 'forest_dark', 
                'crimson_dark', 'classic_light'
            ];
            if (in_array($theme, $valid_themes)) {
                update_setting('active_theme', $theme);
                $success_message = "Theme changed to " . ucwords(str_replace('_', ' ', $theme)) . " successfully.";
            }
        }
        
        // 2. Shop Status
        if (isset($_POST['shop_status'])) {
            $status = trim($_POST['shop_status']) === 'open' ? 'open' : 'closed';
            update_setting('shop_status', $status);
            $success_message = "Shop status updated successfully.";
        }

        // 3. Low stock threshold
        if (isset($_POST['low_stock_threshold'])) {
            $threshold = (int)$_POST['low_stock_threshold'];
            if ($threshold >= 0) {
                update_setting('low_stock_threshold', $threshold);
                $success_message = "Low stock alert threshold updated to {$threshold}.";
            }
        }

        // 4. WhatsApp Template
        if (isset($_POST['whatsapp_dispatch_template'])) {
            $template = trim($_POST['whatsapp_dispatch_template']);
            update_setting('whatsapp_dispatch_template', $template);
            $success_message = "WhatsApp order alert template updated.";
        }

        // 5. Timings
        if (isset($_POST['shop_timings']) && is_array($_POST['shop_timings'])) {
            $timings_json = json_encode($_POST['shop_timings']);
            update_setting('shop_timings', $timings_json);
            $success_message = "Weekly store hours updated successfully.";
        }

        // 6. Homepage Announcement
        if (isset($_POST['homepage_announcement'])) {
            $ann = trim($_POST['homepage_announcement']);
            update_setting('homepage_announcement', $ann);
            $success_message = "Storefront banner message updated.";
        }

        // 7. Store Branding & Identity
        if (isset($_POST['store_name'])) {
            update_setting('store_name', trim($_POST['store_name']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['store_currency'])) {
            update_setting('store_currency', trim($_POST['store_currency']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['store_phone'])) {
            update_setting('store_phone', trim($_POST['store_phone']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['store_email'])) {
            update_setting('store_email', trim($_POST['store_email']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['store_address'])) {
            update_setting('store_address', trim($_POST['store_address']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['store_maps_url'])) {
            update_setting('store_maps_url', trim($_POST['store_maps_url']));
            $success_message = "Store configurations updated successfully.";
        }
        
        // 8. Social links
        if (isset($_POST['whatsapp_number'])) {
            $wa = preg_replace('/\D/', '', $_POST['whatsapp_number']);
            update_setting('whatsapp_number', $wa);
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['facebook_url'])) {
            update_setting('facebook_url', trim($_POST['facebook_url']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['instagram_url'])) {
            update_setting('instagram_url', trim($_POST['instagram_url']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['tiktok_url'])) {
            update_setting('tiktok_url', trim($_POST['tiktok_url']));
            $success_message = "Store configurations updated successfully.";
        }
        
        // 9. Checkout rules
        if (isset($_POST['min_order_value'])) {
            update_setting('min_order_value', number_format((float)$_POST['min_order_value'], 2, '.', ''));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['shipping_fee'])) {
            update_setting('shipping_fee', number_format((float)$_POST['shipping_fee'], 2, '.', ''));
            $success_message = "Store configurations updated successfully.";
        }
        
        // 10. SEO Metadata
        if (isset($_POST['seo_title'])) {
            update_setting('seo_title', trim($_POST['seo_title']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['seo_description'])) {
            update_setting('seo_description', trim($_POST['seo_description']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['seo_keywords'])) {
            update_setting('seo_keywords', trim($_POST['seo_keywords']));
            $success_message = "Store configurations updated successfully.";
        }
        if (isset($_POST['admin_secret_key'])) {
            $new_key = trim($_POST['admin_secret_key']);
            if (!empty($new_key)) {
                update_setting('admin_secret_key', $new_key);
                $success_message = "Store configurations updated successfully.";
            }
        }
        
        // 11. Google Auth Settings
        if (isset($_POST['google_client_id'])) {
            update_setting('google_client_id', trim($_POST['google_client_id']));
            $success_message = "Store configurations updated successfully.";
        }
        $google_auth_enabled_post = isset($_POST['google_auth_enabled']) ? '1' : '0';
        update_setting('google_auth_enabled', $google_auth_enabled_post);
    }
}

// FETCH ANALYTIC METRICS
try {
    // 1. Today's POS & Online Sales combined
    $stmt = $pdo->query("SELECT SUM(total_amount) as today_sales FROM sales WHERE DATE(created_at) = CURRENT_DATE");
    $today_sales = (float)($stmt->fetch()['today_sales'] ?? 0.0);

    // 2. Monthly POS & Online Sales combined
    $stmt = $pdo->query("SELECT SUM(total_amount) as month_sales FROM sales WHERE MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)");
    $month_sales = (float)($stmt->fetch()['month_sales'] ?? 0.0);

    // 3. Total Net Profit combined
    $stmt = $pdo->query("SELECT SUM(total_profit) as total_profit FROM sales");
    $total_profit = (float)($stmt->fetch()['total_profit'] ?? 0.0);

    // 4. Online Orders counts
    $stmt = $pdo->query("SELECT COUNT(*) as online_count FROM orders");
    $online_count = (int)($stmt->fetch()['online_count'] ?? 0);

    // 5. In-Store POS counts
    $stmt = $pdo->query("SELECT COUNT(*) as pos_count FROM sales WHERE transaction_type = 'POS'");
    $pos_count = (int)($stmt->fetch()['pos_count'] ?? 0);

    // 6. POS vs Online Shares
    $stmt = $pdo->query("SELECT transaction_type, SUM(total_amount) as type_total FROM sales GROUP BY transaction_type");
    $shares = $stmt->fetchAll();
    $pos_share_val = 0;
    $online_share_val = 0;
    foreach ($shares as $sh) {
        if ($sh['transaction_type'] === 'POS') $pos_share_val = (float)$sh['type_total'];
        if ($sh['transaction_type'] === 'Online') $online_share_val = (float)$sh['type_total'];
    }

    // 7. Recent Orders list
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll();

    // 8. Staff / Managers Activity list
    $stmt = $pdo->query("SELECT id, username, role, name, phone, created_at FROM users WHERE role IN ('owner', 'manager') ORDER BY id ASC");
    $staff_members = $stmt->fetchAll();

    // Fetch customer product demands
    try {
        $stmt_demands = $pdo->query("SELECT * FROM product_demands ORDER BY id DESC LIMIT 30");
        $customer_demands = $stmt_demands->fetchAll();
    } catch (PDOException $e) {
        $customer_demands = [];
    }

    // 9. Fetch system settings
    $low_stock_threshold = (int)get_setting('low_stock_threshold', '5');
    $stmt_low = $pdo->prepare("SELECT id, barcode, name, stock_quantity, category, price FROM products WHERE stock_quantity <= :thresh ORDER BY stock_quantity ASC");
    $stmt_low->execute(['thresh' => $low_stock_threshold]);
    $low_stock_products = $stmt_low->fetchAll();

    $shop_status = get_setting('shop_status', 'open');
    $homepage_announcement = get_setting('homepage_announcement', '');
    $whatsapp_dispatch_template = get_setting('whatsapp_dispatch_template', '');
    
    $timings_json = get_setting('shop_timings', '{}');
    $shop_timings = json_decode($timings_json, true);

    // Fetch newly introduced dynamic branding and configuration settings
    $store_name = get_setting('store_name', 'HR Traders');
    $store_currency = get_setting('store_currency', 'Rs.');
    $store_phone = get_setting('store_phone', '+92 303 3943814');
    $store_email = get_setting('store_email', 'info@hrtraders.com');
    $store_address = get_setting('store_address', 'Toor Colony, Front of Hira Public School, Tando Adam');
    $store_maps_url = get_setting('store_maps_url', 'https://maps.app.goo.gl/S7BB1SyefKsfKX5K7');
    $whatsapp_number = get_setting('whatsapp_number', '923033943814');
    $facebook_url = get_setting('facebook_url', '#');
    $instagram_url = get_setting('instagram_url', '#');
    $tiktok_url = get_setting('tiktok_url', '#');
    $min_order_value = get_setting('min_order_value', '0.00');
    $shipping_fee = get_setting('shipping_fee', '0.00');
    $seo_title = get_setting('seo_title', 'HR Traders - Premium Online Grocery & Grain Store');
    $seo_description = get_setting('seo_description', 'Shop the freshest grains, cold drinks, dairy, and cosmetics online with fast delivery.');
    $seo_keywords = get_setting('seo_keywords', 'grain store, online grocery, cosmetics shop, dry fruits, fresh milk');
    $admin_secret_key = get_setting('admin_secret_key', 'hr_secure_desk_99');
    $google_client_id = get_setting('google_client_id', '');
    $google_auth_enabled = get_setting('google_auth_enabled', '0');

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
$current_theme = get_setting('active_theme', 'emerald_green');
$dark_themes = ['midnight_indigo', 'cyberpunk_neon', 'deep_purple', 'forest_dark', 'forest_green', 'crimson_dark', 'crimson_rose'];
$html_class = in_array($current_theme, $dark_themes) ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $html_class; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
    <title>HR Traders - <?php echo is_owner() ? 'Owner' : 'Manager'; ?> Dashboard</title>
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
                        darkbg: '#090d16',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.3">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/all.min.css">
</head>
<body class="theme-<?php echo get_setting('active_theme', 'emerald_green'); ?> bg-slate-50 text-slate-800 min-h-screen flex flex-col">

<!-- HEADER -->
<header class="bg-white border-b border-slate-200 px-4 py-3 md:px-6 md:py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3 z-10 flex-shrink-0">
    <div class="flex items-center justify-between w-full md:w-auto">
        <span class="text-base md:text-lg font-black text-emerald-600 tracking-wider">
            HR TRADERS <span class="text-[10px] md:text-xs text-slate-500 font-bold uppercase"><?php echo is_owner() ? 'OWNER DASHBOARD' : 'MANAGER DASHBOARD'; ?></span>
        </span>
        <!-- Mobile Logout button -->
        <a href="<?php echo BASE_URL; ?>logout.php" class="md:hidden px-2.5 py-1.5 bg-rose-50 border border-rose-200 hover:bg-rose-500 hover:text-white text-rose-600 text-xs rounded-xl font-bold transition-all">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
    
    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto pb-1 md:pb-0">
        <span class="text-[10px] md:text-xs px-2.5 py-1.5 bg-slate-100 border border-slate-200 text-slate-700 font-semibold rounded-xl flex-shrink-0">
            Profile: <?php echo sanitize($_SESSION['name']); ?>
        </span>
        <!-- Sound Alert Toggle Button -->
        <button id="toggle-sound-btn" onclick="toggleSound()" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] md:text-xs rounded-xl font-bold border border-slate-300 transition-colors flex-shrink-0 flex items-center gap-1.5">
            <span id="sound-status-icon"><i class="fas fa-volume-up text-emerald-600"></i> Sound On</span>
        </button>
        <?php if (is_owner()): ?>
            <a href="<?php echo BASE_URL; ?>admin/products.php" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] md:text-xs rounded-xl font-bold transition-colors flex-shrink-0">
                <i class="fas fa-cubes"></i> Products
            </a>
            <a href="<?php echo BASE_URL; ?>admin/manager.php" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] md:text-xs rounded-xl font-bold border border-slate-300 transition-colors flex-shrink-0">
                <i class="fas fa-truck-fast"></i> Order Desk
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>admin/products.php" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] md:text-xs rounded-xl font-bold border border-slate-300 transition-colors flex-shrink-0">
                <i class="fas fa-cubes"></i> Products
            </a>
            <a href="<?php echo BASE_URL; ?>admin/manager.php" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] md:text-xs rounded-xl font-bold transition-colors flex-shrink-0">
                <i class="fas fa-truck-fast"></i> Order Desk
            </a>
        <?php endif; ?>
        <!-- Desktop Logout button -->
        <a href="<?php echo BASE_URL; ?>logout.php" class="hidden md:flex px-3.5 py-1.5 bg-rose-50 border border-rose-200 hover:bg-rose-500 hover:text-white text-rose-600 text-xs rounded-xl font-bold transition-all flex-shrink-0">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </a>
    </div>
</header>

<main class="flex-1 max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 w-full space-y-8">
    
    <!-- Floating Toast Notification Overlay -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm pointer-events-none"></div>

    <!-- Toast Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="p-4 bg-emerald-50 border border-emerald-250 rounded-2xl text-emerald-700 text-xs flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="p-4 bg-rose-50 border border-rose-250 rounded-2xl text-rose-700 text-xs flex items-center gap-2">
            <i class="fas fa-triangle-exclamation"></i>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <!-- TABS NAVIGATION -->
    <div class="flex items-center gap-4 border-b border-slate-200 mb-6 flex-wrap print-hidden">
        <button onclick="switchTab('overview-tab')" id="btn-overview-tab" class="tab-btn pb-3 text-sm font-bold text-emerald-600 border-b-2 border-emerald-600 tracking-wide focus:outline-none transition-all">
            <i class="fas fa-chart-pie mr-1"></i> Overview
        </button>
        <button onclick="switchTab('inventory-tab')" id="btn-inventory-tab" class="tab-btn pb-3 text-sm font-bold text-slate-500 hover:text-slate-800 border-b-2 border-transparent tracking-wide focus:outline-none transition-all">
            <i class="fas fa-cubes mr-1"></i> Stock Alerts &amp; Planner
        </button>
        <?php if (is_owner()): ?>
        <button onclick="switchTab('settings-tab')" id="btn-settings-tab" class="tab-btn pb-3 text-sm font-bold text-slate-500 hover:text-slate-800 border-b-2 border-transparent tracking-wide focus:outline-none transition-all">
            <i class="fas fa-sliders mr-1"></i> Storefront &amp; Themes
        </button>
        <?php endif; ?>
    </div>

    <!-- OVERVIEW TAB CONTENT -->
    <div id="overview-tab" class="tab-content space-y-8">

    <!-- METRICS CARDS ROW -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Today's Sales -->
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 flex items-center justify-between shadow-sm bg-white">
            <div class="space-y-1">
                <span class="text-[10px] text-slate-500 uppercase font-semibold block">Today's Sales</span>
                <strong id="stat-today-sales" class="text-2xl font-black text-slate-900 block"><?php echo format_price($today_sales); ?></strong>
                <span class="text-[10px] text-slate-500 block">POS + Online Delivery</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-xl">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>

        <!-- Monthly Sales -->
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 flex items-center justify-between shadow-sm bg-white">
            <div class="space-y-1">
                <span class="text-[10px] text-slate-500 uppercase font-semibold block">Monthly Sales</span>
                <strong id="stat-month-sales" class="text-2xl font-black text-slate-900 block"><?php echo format_price($month_sales); ?></strong>
                <span class="text-[10px] text-slate-500 block">Current calendar month</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center text-xl">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <!-- Net Profit -->
        <?php if (is_owner()): ?>
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 flex items-center justify-between shadow-sm bg-white">
            <div class="space-y-1">
                <span class="text-[10px] text-slate-500 uppercase font-semibold block">Cumulative Net Profit</span>
                <strong id="stat-total-profit" class="text-2xl font-black text-emerald-600 block"><?php echo format_price($total_profit); ?></strong>
                <span class="text-[10px] text-slate-500 block">Sales - Grains cost</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 border border-teal-200 flex items-center justify-center text-xl">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>
        <?php else: ?>
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 flex items-center justify-between shadow-sm bg-slate-50/50 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-16 h-16 bg-amber-500/5 rounded-full blur-lg"></div>
            <div class="space-y-1 z-10">
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Cumulative Net Profit</span>
                <div class="flex items-center gap-1.5 py-1">
                    <i class="fas fa-lock text-amber-500 text-xs"></i>
                    <span class="text-xs font-bold text-slate-400 tracking-wide">Access Restricted</span>
                </div>
                <span class="text-[9px] text-slate-400 block">Requires Owner level access</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 border border-amber-200 flex items-center justify-center text-lg z-10">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>
        <?php endif; ?>

        <!-- Orders overview counts -->
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 flex items-center justify-between shadow-sm bg-white">
            <div class="space-y-1">
                <span class="text-[10px] text-slate-500 uppercase font-semibold block">Order Counter Volumes</span>
                <strong id="stat-total-volume" class="text-2xl font-black text-slate-900 block"><?php echo $pos_count + $online_count; ?> Total</strong>
                <span id="stat-volume-subtitle" class="text-[10px] text-slate-500 block">POS: <?php echo $pos_count; ?> | Online: <?php echo $online_count; ?></span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 border border-purple-200 flex items-center justify-center text-xl">
                <i class="fas fa-boxes-stacked"></i>
            </div>
        </div>
    </div>

    <!-- SALES SHARE GRAPH & USER ACCESS MANAGER BLOCK -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Sales Shares representation bar chart -->
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 <?php echo is_owner() ? 'lg:col-span-2' : 'lg:col-span-3'; ?> space-y-6 bg-white shadow-sm">
            <div>
                <h3 class="font-bold text-base text-slate-900">Revenue Stream Comparison</h3>
                <p class="text-[11px] text-slate-500">Comparing values between counter POS operations and online deliveries</p>
            </div>

            <!-- Offline Vector visual chart representation -->
            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 flex flex-col justify-center gap-6">
                <?php 
                $grand_sales = $pos_share_val + $online_share_val;
                $pos_pct = $grand_sales > 0 ? round(($pos_share_val / $grand_sales) * 100) : 0;
                $online_pct = $grand_sales > 0 ? round(($online_share_val / $grand_sales) * 100) : 0;
                ?>
                
                <!-- POS Share progress bar -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span id="label-pos-share" class="text-slate-650">In-Store POS Sales (<?php echo $pos_pct; ?>%)</span>
                        <strong class="text-slate-800"><?php echo format_price($pos_share_val); ?></strong>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3.5 border border-slate-300 overflow-hidden">
                        <div id="progress-pos-bar" class="bg-emerald-600 h-full rounded-full transition-all" style="width: <?php echo $pos_pct; ?>%;"></div>
                    </div>
                </div>

                <!-- Online Share progress bar -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span id="label-online-share" class="text-slate-650">Online Store Deliveries (<?php echo $online_pct; ?>%)</span>
                        <strong class="text-slate-800"><?php echo format_price($online_share_val); ?></strong>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3.5 border border-slate-300 overflow-hidden">
                        <div id="progress-online-bar" class="bg-blue-600 h-full rounded-full transition-all" style="width: <?php echo $online_pct; ?>%;"></div>
                    </div>
                </div>

                <div class="text-[10px] text-slate-400 flex items-center justify-between pt-2 border-t border-slate-200">
                    <span>*Values update instantly upon marking online orders as delivered or completing checkout at cash register.</span>
                </div>
            </div>
        </div>

        <!-- Right: Register New Manager / Staff Accounts -->
        <?php if (is_owner()): ?>
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm">
            <h3 class="font-bold text-base text-slate-900 mb-1">Add Staff Account</h3>
            <p class="text-[10px] text-slate-500 mb-4">Create access profiles for store managers or cashier staffs</p>

            <form action="dashboard.php" method="POST" class="space-y-3.5">
                <input type="hidden" name="action" value="create_staff">
                
                <div>
                    <label for="username" class="block text-[10px] font-bold text-slate-600 mb-1 uppercase">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off"
                           class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-900 focus:bg-slate-50/50">
                </div>

                <div>
                    <label for="password" class="block text-[10px] font-bold text-slate-600 mb-1 uppercase">Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-900 focus:bg-slate-50/50">
                </div>

                <div>
                    <label for="role" class="block text-[10px] font-bold text-slate-600 mb-1 uppercase">Security Role</label>
                    <select id="role" name="role" required
                            class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-700">
                        <option value="manager">Store Manager (restricted billing desk)</option>
                        <option value="owner">Owner Admin (full access)</option>
                    </select>
                </div>

                <div>
                    <label for="name" class="block text-[10px] font-bold text-slate-600 mb-1 uppercase">Full Name</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-900 focus:bg-slate-50/50">
                </div>

                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-2.5 shadow-md shadow-emerald-600/10">
                    Register Staff Account
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- RECENT ONLINE ORDERS & REGISTERED STAFF DETAILS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Recent online orders lists -->
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 <?php echo is_owner() ? 'lg:col-span-2' : 'lg:col-span-3'; ?> space-y-4 bg-white shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-base text-slate-900">Recent Online Orders</h3>
                <a href="<?php echo BASE_URL; ?>admin/manager.php" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">View All &rarr;</a>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider">
                            <th class="py-3 pr-2">Ref ID</th>
                            <th class="py-3">Customer</th>
                            <th class="py-3">Address</th>
                            <th class="py-3 text-right">Invoice Total</th>
                            <th class="py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="recent-orders-tbody" class="divide-y divide-slate-200 text-slate-700">
                        <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No customer orders placed yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $ord): ?>
                                <tr>
                                    <td class="py-3 pr-2 font-mono font-bold">#HRT-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td class="py-3">
                                        <span class="block font-bold text-slate-805"><?php echo sanitize($ord['customer_name']); ?></span>
                                        <span class="block text-[10px] text-slate-400 font-mono"><?php echo sanitize($ord['customer_phone']); ?></span>
                                    </td>
                                    <td class="py-3 max-w-[150px] truncate" title="<?php echo sanitize($ord['customer_address']); ?>">
                                        <?php echo sanitize($ord['customer_address']); ?>
                                    </td>
                                    <td class="py-3 text-right font-bold text-emerald-600"><?php echo format_price($ord['total_amount']); ?></td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-[4px] text-[8px] font-bold uppercase border <?php 
                                            switch($ord['status']) {
                                                case 'pending': echo 'bg-amber-50 text-amber-700 border-amber-200'; break;
                                                case 'packaging': echo 'bg-blue-50 text-blue-700 border-blue-200'; break;
                                                case 'out_for_delivery': echo 'bg-purple-50 text-purple-700 border-purple-200'; break;
                                                case 'delivered': echo 'bg-emerald-50 text-emerald-700 border-emerald-200'; break;
                                                case 'cancelled': echo 'bg-rose-50 text-rose-700 border-rose-200'; break;
                                            }
                                        ?>">
                                            <?php echo $ord['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Stacked Card View -->
            <div id="recent-orders-mobile-list" class="block md:hidden space-y-3">
                <?php if (empty($recent_orders)): ?>
                    <div class="py-6 text-center text-slate-400 text-xs">No customer orders placed yet.</div>
                <?php else: ?>
                    <?php foreach ($recent_orders as $ord): ?>
                        <?php 
                        $ref = "#HRT-" . str_pad($ord['id'], 5, '0', STR_PAD_LEFT);
                        $status_class = "";
                        switch($ord['status']) {
                            case 'pending': $status_class = 'bg-amber-50 text-amber-700 border-amber-200'; break;
                            case 'packaging': $status_class = 'bg-blue-50 text-blue-700 border-blue-200'; break;
                            case 'out_for_delivery': $status_class = 'bg-purple-50 text-purple-700 border-purple-200'; break;
                            case 'delivered': $status_class = 'bg-emerald-50 text-emerald-700 border-emerald-200'; break;
                            case 'cancelled': $status_class = 'bg-rose-50 text-rose-700 border-rose-200'; break;
                        }
                        ?>
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-mono font-bold text-slate-800"><?php echo $ref; ?></span>
                                <span class="px-2 py-0.5 rounded text-[8px] font-bold uppercase border <?php echo $status_class; ?>"><?php echo $ord['status']; ?></span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <div>
                                    <strong class="block text-slate-808"><?php echo sanitize($ord['customer_name']); ?></strong>
                                    <span class="text-[10px] text-slate-400 font-mono"><?php echo sanitize($ord['customer_phone']); ?></span>
                                </div>
                                <strong class="text-emerald-650 font-bold"><?php echo format_price($ord['total_amount']); ?></strong>
                            </div>
                            <p class="text-[10px] text-slate-500 truncate" title="<?php echo sanitize($ord['customer_address']); ?>"><?php echo sanitize($ord['customer_address']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Registered Managers List -->
        <?php if (is_owner()): ?>
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 space-y-4 bg-white shadow-sm">
            <h3 class="font-bold text-base text-slate-900">Registered Staffs</h3>
            
            <div class="divide-y divide-slate-200 max-h-80 overflow-y-auto pr-1">
                <?php foreach ($staff_members as $sm): ?>
                    <div class="py-3 flex items-center justify-between first:pt-0 last:pb-0 text-xs">
                        <div class="space-y-0.5">
                            <span class="font-bold text-slate-800 block"><?php echo sanitize($sm['name']); ?></span>
                            <span class="text-slate-500 block">User: <strong class="font-mono"><?php echo sanitize($sm['username']); ?></strong></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded-[4px] text-[8px] font-bold uppercase <?php echo $sm['role'] === 'owner' ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-750 border border-slate-200'; ?>">
                                    <?php echo $sm['role']; ?>
                                </span>
                                <span class="text-[9px] text-slate-500 block font-mono mt-1">ID: #<?php echo $sm['id']; ?></span>
                            </div>
                            <div class="flex items-center gap-1.5 pl-2 border-l border-slate-200">
                                <button onclick="triggerResetPassword(<?php echo $sm['id']; ?>, '<?php echo htmlspecialchars(addslashes($sm['name']), ENT_QUOTES, 'UTF-8'); ?>')" 
                                        class="w-7 h-7 rounded-lg bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-600 flex items-center justify-center transition-colors shadow-sm"
                                        title="Reset Password">
                                    <i class="fas fa-key text-[10px]"></i>
                                </button>
                                <?php if ($sm['id'] !== (int)$_SESSION['user_id']): ?>
                                <button onclick="triggerDeleteStaff(<?php echo $sm['id']; ?>, '<?php echo htmlspecialchars(addslashes($sm['name']), ENT_QUOTES, 'UTF-8'); ?>')" 
                                        class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center transition-colors shadow-sm"
                                        title="Delete Staff">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </button>
                                <?php else: ?>
                                <div class="w-7 h-7 flex items-center justify-center text-slate-300" title="Self (Cannot delete)">
                                    <i class="fas fa-ban text-[10px]"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form id="delete-staff-form" action="dashboard.php" method="POST" style="display:none;">
            <input type="hidden" name="action" value="delete_staff">
            <input type="hidden" name="staff_id" value="">
        </form>

        <form id="reset-password-form" action="dashboard.php" method="POST" style="display:none;">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="staff_id" value="">
            <input type="hidden" name="new_password" value="">
        </form>
        <?php endif; ?>

        <!-- Customer Demands Panel -->
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 space-y-4 bg-white shadow-sm mt-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-amber-500"></i> Customer Demands
                </h3>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    <?php echo count($customer_demands); ?> Requests
                </span>
            </div>
            
            <div class="divide-y divide-slate-200 max-h-96 overflow-y-auto pr-1 space-y-2.5">
                <?php if (empty($customer_demands)): ?>
                    <p class="text-xs text-slate-400 text-center py-6">No pending product demands.</p>
                <?php else: ?>
                    <?php foreach ($customer_demands as $cd): ?>
                        <div class="py-2.5 space-y-1.5 first:pt-0 last:pb-0 text-xs">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <strong class="text-slate-800 font-bold block"><?php echo sanitize($cd['customer_name']); ?></strong>
                                    <?php if (($cd['status'] ?? 'pending') === 'confirmed'): ?>
                                        <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Confirmed</span>
                                    <?php else: ?>
                                        <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Pending</span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-[9px] text-slate-400 font-mono"><?php echo date('d-M h:i A', strtotime($cd['created_at'])); ?></span>
                            </div>
                            <div class="flex items-center justify-between text-[11px]">
                                <a href="tel:<?php echo sanitize($cd['customer_phone']); ?>" class="text-slate-500 hover:text-emerald-600 transition-colors font-mono flex items-center gap-1">
                                    <i class="fas fa-phone-alt text-[9px]"></i> <?php echo sanitize($cd['customer_phone']); ?>
                                </a>
                                <div class="flex items-center gap-1">
                                    <?php if (($cd['status'] ?? 'pending') !== 'confirmed'): ?>
                                        <button onclick="triggerConfirmDemand(<?php echo $cd['id']; ?>)" 
                                                class="w-6 h-6 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-600 flex items-center justify-center transition-colors"
                                                title="Confirm Demand">
                                            <i class="fas fa-check text-[9px]"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="triggerDeleteDemand(<?php echo $cd['id']; ?>)" 
                                            class="w-6 h-6 rounded bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-colors"
                                            title="Delete Demand">
                                        <i class="fas fa-trash-alt text-[9px]"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-655 border border-slate-200/50 mt-1 italic break-words">
                                "<?php echo sanitize($cd['demand_details']); ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <form id="delete-demand-form" action="dashboard.php" method="POST" style="display:none;">
            <input type="hidden" name="action" value="delete_demand">
            <input type="hidden" name="demand_id" value="">
        </form>
        <form id="confirm-demand-form" action="dashboard.php" method="POST" style="display:none;">
            <input type="hidden" name="action" value="confirm_demand">
            <input type="hidden" name="demand_id" value="">
        </form>
    </div>
    </div> <!-- OVERVIEW TAB END -->

    <!-- INVENTORY & LOW STOCK ALERTS TAB -->
    <div id="inventory-tab" class="tab-content hidden space-y-6">
        <!-- Low Stock Alerts Panel -->
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Inventory Stock Planner</h2>
                    <p class="text-xs text-slate-500 mt-1">Monitor products running low on stock and generate restocking sheets.</p>
                </div>
                <?php if (is_owner()): ?>
                <!-- Alert threshold settings form -->
                <form action="dashboard.php" method="POST" class="flex items-center gap-2">
                    <input type="hidden" name="action" value="update_settings">
                    <label for="low_stock_threshold" class="text-xs font-bold text-slate-600 uppercase">Alert Threshold:</label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" value="<?php echo $low_stock_threshold; ?>" min="0" required
                           class="w-16 px-2.5 py-1 bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-500 text-xs font-bold text-slate-800 text-center">
                    <button type="submit" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-lg transition-colors">
                        Save
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- List of low stock products -->
            <div id="printable-stock-sheet">
                <div class="print-only hidden mb-6 text-center">
                    <h1 class="text-2xl font-black uppercase text-slate-900">HR Traders</h1>
                    <p class="text-sm font-semibold text-slate-500">Inventory Restocking Planner &mdash; <?php echo date('d-M-Y'); ?></p>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider">
                                <th class="p-4 pl-6">Barcode</th>
                                <th class="p-4">Product Name</th>
                                <th class="p-4">Category</th>
                                <th class="p-4 text-center">Current Stock</th>
                                <th class="p-4 text-right">Price</th>
                                <th class="p-4 text-center pr-6">Recommended Restock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            <?php if (empty($low_stock_products)): ?>
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400">
                                        <i class="fas fa-check-double text-4xl text-emerald-550 mb-3 opacity-60 block"></i>
                                        All inventory products are well stocked! (Threshold: <?php echo $low_stock_threshold; ?> units)
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($low_stock_products as $lp): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-4 pl-6 font-mono font-bold"><?php echo sanitize($lp['barcode']); ?></td>
                                        <td class="p-4 font-bold text-slate-900"><?php echo sanitize($lp['name']); ?></td>
                                        <td class="p-4 uppercase font-semibold text-slate-555 text-[10px]"><?php echo $CATEGORIES[$lp['category']]['name'] ?? $lp['category']; ?></td>
                                        <td class="p-4 text-center">
                                            <span class="px-2.5 py-1 bg-rose-50 text-rose-700 font-bold rounded-lg border border-rose-200 text-xs">
                                                <?php echo $lp['stock_quantity']; ?> left
                                            </span>
                                        </td>
                                        <td class="p-4 text-right font-bold text-slate-800"><?php echo format_price($lp['price']); ?></td>
                                        <td class="p-4 text-center pr-6 font-bold text-emerald-650">
                                            +<?php echo max(20, 50 - $lp['stock_quantity']); ?> units
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-wrap gap-3 pt-2 print-hidden">
                <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition-colors shadow-md shadow-emerald-600/10">
                    <i class="fas fa-print"></i> Print Restock Sheet
                </button>
            </div>
        </div>
    </div>

    <!-- OPERATIONS & SETTINGS TAB -->
    <?php if (is_owner()): ?>
    <div id="settings-tab" class="tab-content hidden space-y-8">
        
        <!-- Theme switcher section -->
        <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-6">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Platform Theme Engine</h2>
                <p class="text-xs text-slate-500 mt-1">Select from 10 dynamic light and dark modes designed to adjust storefront and dashboard styles instantly.</p>
            </div>

            <form action="dashboard.php" method="POST" id="theme-selector-form">
                <input type="hidden" name="action" value="update_settings">
                <input type="hidden" name="active_theme" id="selected-theme-input">

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                    <?php
                    $themes_list = [
                        'emerald_green' => ['name' => 'Emerald Green', 'type' => 'light', 'primary' => '#10b981', 'bg' => '#f8fafc', 'border' => '#e2e8f0'],
                        'midnight_indigo' => ['name' => 'Midnight Indigo', 'type' => 'dark', 'primary' => '#6366f1', 'bg' => '#030712', 'border' => '#1e293b'],
                        'rose_gold' => ['name' => 'Rose Gold', 'type' => 'light', 'primary' => '#db2777', 'bg' => '#fffbfd', 'border' => '#fbcfe8'],
                        'cyberpunk_neon' => ['name' => 'Cyberpunk Neon', 'type' => 'dark', 'primary' => '#06b6d4', 'bg' => '#050508', 'border' => '#ff007f'],
                        'slate_blue' => ['name' => 'Slate Blue', 'type' => 'light', 'primary' => '#2563eb', 'bg' => '#f8fafc', 'border' => '#cbd5e1'],
                        'amber_honey' => ['name' => 'Amber Honey', 'type' => 'light', 'primary' => '#d97706', 'bg' => '#fffdf5', 'border' => '#fde68a'],
                        'deep_purple' => ['name' => 'Deep Purple', 'type' => 'dark', 'primary' => '#a855f7', 'bg' => '#0b0716', 'border' => '#4a148c'],
                        'forest_dark' => ['name' => 'Forest Emerald', 'type' => 'dark', 'primary' => '#10b981', 'bg' => '#020804', 'border' => '#047857'],
                        'crimson_dark' => ['name' => 'Crimson Ruby', 'type' => 'dark', 'primary' => '#f43f5e', 'bg' => '#080103', 'border' => '#9f1239'],
                        'classic_light' => ['name' => 'Classic Monochrome', 'type' => 'light', 'primary' => '#000000', 'bg' => '#ffffff', 'border' => '#000000']
                    ];
                    $current_theme = get_setting('active_theme', 'emerald_green');
                    foreach ($themes_list as $key => $t):
                        $is_active = $current_theme === $key;
                    ?>
                        <div onclick="selectTheme('<?php echo $key; ?>')" 
                             class="cursor-pointer border-2 rounded-2xl p-4 flex flex-col justify-between h-32 transition-all hover:-translate-y-1 hover:shadow-md <?php echo $is_active ? 'border-emerald-600 bg-emerald-50/10 shadow-lg ring-1 ring-emerald-650' : 'border-slate-200 bg-white hover:border-slate-350'; ?>">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-800"><?php echo $t['name']; ?></span>
                                <span class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded <?php echo $t['type'] === 'dark' ? 'bg-slate-900 text-slate-300' : 'bg-slate-100 text-slate-600'; ?>">
                                    <?php echo $t['type']; ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 mt-2">
                                <div class="w-4 h-4 rounded-full border border-slate-300" style="background-color: <?php echo $t['primary']; ?>;"></div>
                                <div class="w-4 h-4 rounded border border-slate-300" style="background-color: <?php echo $t['bg']; ?>;"></div>
                                <div class="w-4 h-4 rounded border border-slate-300" style="background-color: <?php echo $t['border']; ?>;"></div>
                            </div>
                            <div class="text-right mt-2">
                                <span class="text-[10px] font-semibold <?php echo $is_active ? 'text-emerald-600 font-bold' : 'text-slate-400'; ?>">
                                    <?php echo $is_active ? '<i class="fas fa-check-circle"></i> Active' : 'Select'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <!-- Shop Timings & Status Editor -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Timing editor -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-4">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Operating Schedule Manager</h3>
                    <p class="text-[10px] text-slate-500">Configure store timings shown in the website footer and widgets.</p>
                </div>

                <form action="dashboard.php" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <?php 
                        $days_of_week = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                        foreach ($days_of_week as $day):
                            $val = $shop_timings[$day] ?? '6:00 AM - 12:00 PM';
                        ?>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1"><?php echo $day; ?></label>
                                <input type="text" name="shop_timings[<?php echo $day; ?>]" value="<?php echo sanitize($val); ?>" required
                                       class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-2.5 shadow-md shadow-emerald-600/10">
                        Update Weekly Timings
                    </button>
                </form>
            </div>

            <!-- Shop Status (Emergency Holidays) & Announcement -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm flex flex-col justify-between gap-6">
                
                <div class="space-y-4">
                    <div>
                        <h3 class="font-bold text-base text-slate-900">Storefront Status &amp; Announcements</h3>
                        <p class="text-[10px] text-slate-500">Toggle emergency closures and edit the storefront promotion text.</p>
                    </div>

                    <!-- Shop Status closure toggle -->
                    <form action="dashboard.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                            <div>
                                <span class="font-bold text-slate-800 text-xs block">Current Store Status</span>
                                <span class="text-[10px] text-slate-500 block">Open for order checkouts</span>
                            </div>
                            <select name="shop_status" onchange="this.form.submit()"
                                    class="px-3 py-1 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs font-bold text-slate-700">
                                <option value="open" <?php echo $shop_status === 'open' ? 'selected' : ''; ?>>OPEN</option>
                                <option value="closed" <?php echo $shop_status === 'closed' ? 'selected' : ''; ?>>CLOSED (Holiday)</option>
                            </select>
                        </div>
                    </form>

                    <!-- Homepage announcement form -->
                    <form action="dashboard.php" method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="update_settings">
                        <div>
                            <label for="homepage_announcement" class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Storefront Announcement Text</label>
                            <textarea id="homepage_announcement" name="homepage_announcement" rows="3" required
                                      class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800"><?php echo sanitize($homepage_announcement); ?></textarea>
                        </div>

                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-2.5 shadow-md shadow-emerald-600/10">
                            Save Banner Message
                        </button>
                    </form>
                </div>

            </div>

        </div>

        <!-- WhatsApp Alert Template Manager & Financial Margins CSV Download -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- WhatsApp Dispatch Template -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-4">
                <div>
                    <h3 class="font-bold text-base text-slate-900">WhatsApp Alert Template</h3>
                    <p class="text-[10px] text-slate-500">Configure message variables sent when dispatching orders (Supports: <code>{name}</code>, <code>{ref}</code>, <code>{total}</code>, <code>{address}</code>).</p>
                </div>

                <form action="dashboard.php" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div>
                        <textarea name="whatsapp_dispatch_template" rows="3" required
                                  class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 font-mono"><?php echo sanitize($whatsapp_dispatch_template); ?></textarea>
                    </div>

                    <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-2.5 shadow-md shadow-emerald-600/10">
                        Save Message Template
                    </button>
                </form>
            </div>

            <!-- Financial Margin Reports PDF/CSV Export -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm flex flex-col justify-between">
                <div class="space-y-2">
                    <h3 class="font-bold text-base text-slate-900">Detailed Financial Logs &amp; margins</h3>
                    <p class="text-[10px] text-slate-500 leading-relaxed">
                        Export transactions spreadsheets mapping total retail sales invoices, purchase costs of goods sold, and precise gross profit margins. Files are fully formatted as CSV files compatible with Excel, Google Sheets, or PDF converter printers.
                    </p>
                </div>

                <div class="pt-4">
                    <a href="<?php echo BASE_URL; ?>admin/api/export_reports.php" target="_blank"
                       class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors uppercase tracking-wider">
                        <i class="fas fa-file-csv text-emerald-500 text-sm"></i> Download Detailed Revenue &amp; Profit Margins (CSV)
                    </a>
                </div>
            </div>

        </div>

        <!-- Extensive Branding & SEO Options Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Branding, Identity & Contact Settings Form -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-4">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Store Branding &amp; Identity</h3>
                    <p class="text-[10px] text-slate-500">Configure public business details shown in headers, footers, and emails.</p>
                </div>

                <form action="dashboard.php" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Store Name</label>
                            <input type="text" name="store_name" value="<?php echo sanitize($store_name); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Currency Symbol</label>
                            <input type="text" name="store_currency" value="<?php echo sanitize($store_currency); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Support Phone</label>
                            <input type="text" name="store_phone" value="<?php echo sanitize($store_phone); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Support Email</label>
                            <input type="email" name="store_email" value="<?php echo sanitize($store_email); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Physical Store Address</label>
                        <input type="text" name="store_address" value="<?php echo sanitize($store_address); ?>" required
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Google Maps URL</label>
                        <input type="url" name="store_maps_url" value="<?php echo sanitize($store_maps_url); ?>" required
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                    </div>

                    <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-2.5 shadow-md shadow-emerald-600/10">
                        Save Identity Info
                    </button>
                </form>
            </div>

            <!-- Checkout Rules & SEO Settings Form -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-4">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Store Rules, Socials &amp; SEO</h3>
                    <p class="text-[10px] text-slate-500">Manage checkout limits, support channel links, and site search rankings.</p>
                </div>

                <form action="dashboard.php" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Min Order Value (<?php echo $store_currency; ?>)</label>
                            <input type="number" step="0.01" min="0" name="min_order_value" value="<?php echo sanitize($min_order_value); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Delivery Charge (<?php echo $store_currency; ?>)</label>
                            <input type="number" step="0.01" min="0" name="shipping_fee" value="<?php echo sanitize($shipping_fee); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">WhatsApp No</label>
                            <input type="text" name="whatsapp_number" value="<?php echo sanitize($whatsapp_number); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Facebook Link</label>
                            <input type="text" name="facebook_url" value="<?php echo sanitize($facebook_url); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Instagram Link</label>
                            <input type="text" name="instagram_url" value="<?php echo sanitize($instagram_url); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">TikTok Link</label>
                            <input type="text" name="tiktok_url" value="<?php echo sanitize($tiktok_url); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 font-mono">
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-3 space-y-2">
                        <h4 class="text-xs font-bold text-slate-700 uppercase">Admin Portal Security</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-550 uppercase mb-1">Portal Secret Key</label>
                            <input type="text" name="admin_secret_key" value="<?php echo sanitize($admin_secret_key); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 font-mono">
                            <span class="text-[9px] text-slate-400 block mt-1">This key locks the admin screen. Access URL: <strong class="font-mono text-emerald-600"><?php echo BASE_URL; ?>admin/?secret=<?php echo sanitize($admin_secret_key); ?></strong></span>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-3 space-y-2">
                        <h4 class="text-xs font-bold text-slate-700 uppercase">Search Engine Optimization (SEO)</h4>
                        
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">SEO Title</label>
                            <input type="text" name="seo_title" value="<?php echo sanitize($seo_title); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Meta Keywords</label>
                            <input type="text" name="seo_keywords" value="<?php echo sanitize($seo_keywords); ?>" required
                                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Meta Description</label>
                            <textarea name="seo_description" rows="2" required
                                      class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800"><?php echo sanitize($seo_description); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-2.5 shadow-md shadow-emerald-600/10">
                        Save Rules &amp; SEO
                    </button>
                </form>
            </div>

        </div>

        <!-- Google Authentication Options Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
            <!-- Google Sign-In Configurations Form -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-4">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Google Auth Settings</h3>
                    <p class="text-[10px] text-slate-505">Configure customer sign-up and login credentials using Google Accounts.</p>
                </div>

                <form action="dashboard.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_settings">

                    <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-2xl">
                        <div class="space-y-0.5 text-left">
                            <span class="block text-xs font-bold text-slate-800">Enable Google Authentication</span>
                            <span class="block text-[9px] text-slate-400">Allow customers to log in and sign up with Google</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="google_auth_enabled" value="1" <?php echo ($google_auth_enabled === '1') ? 'checked' : ''; ?> class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <div class="text-left">
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Google Client ID</label>
                        <input type="text" name="google_client_id" value="<?php echo sanitize($google_client_id); ?>" placeholder="e.g. 123456-abcde.apps.googleusercontent.com"
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-800 font-mono">
                        <span class="text-[9px] text-slate-400 block mt-1">Obtained from Google Cloud Developer Console. Authorized JavaScript Origin should match: <strong class="font-mono text-emerald-600"><?php echo BASE_URL; ?></strong></span>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-2.5 shadow-md shadow-emerald-600/10">
                        Save Google Auth Settings
                    </button>
                </form>
            </div>

            <!-- Developer Instructions Card -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm flex flex-col justify-between">
                <div class="space-y-3 text-left">
                    <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                        <i class="fab fa-google text-emerald-600"></i> Google Developer Setup Guide
                    </h3>
                    <p class="text-[11px] text-slate-505 leading-relaxed">
                        To enable customer Google Sign-In, follow these steps:<br>
                        1. Visit the <a href="https://console.cloud.google.com/" target="_blank" class="text-emerald-600 underline font-bold">Google Cloud Console</a>.<br>
                        2. Create a new project or select an existing one.<br>
                        3. Go to **APIs & Services** ➔ **Credentials**.<br>
                        4. Click **Create Credentials** and choose **OAuth client ID**.<br>
                        5. Set application type to **Web application**.<br>
                        6. Under **Authorized JavaScript origins**, add your website root URL:<br>
                           <code class="px-1.5 py-0.5 bg-slate-100 rounded text-emerald-650 font-mono text-[10px]"><?php echo BASE_URL; ?></code><br>
                        7. Copy the generated **Client ID** and paste it into the form on the left.
                    </p>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>
</main>

<!-- Tab switcher script & Live updating polling engine -->
<script>
let audioEnabled = true;
let audioCtx = null;

function initAudio() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
}

function toggleSound() {
    initAudio();
    audioEnabled = !audioEnabled;
    const iconSpan = document.getElementById('sound-status-icon');
    if (audioEnabled) {
        iconSpan.innerHTML = '<i class="fas fa-volume-up text-emerald-600"></i> Sound On';
    } else {
        iconSpan.innerHTML = '<i class="fas fa-volume-mute text-rose-500"></i> Sound Mute';
    }
}

function playOrderAlert() {
    if (!audioEnabled) return;
    try {
        initAudio();
        // Tone 1: E6
        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(1318.51, audioCtx.currentTime); // E6
        gain1.gain.setValueAtTime(0, audioCtx.currentTime);
        gain1.gain.linearRampToValueAtTime(0.25, audioCtx.currentTime + 0.05);
        gain1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.45);
        osc1.start(audioCtx.currentTime);
        osc1.stop(audioCtx.currentTime + 0.45);

        // Tone 2: A6 (150ms delay)
        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(1760.00, audioCtx.currentTime + 0.15); // A6
        gain2.gain.setValueAtTime(0, audioCtx.currentTime + 0.15);
        gain2.gain.linearRampToValueAtTime(0.25, audioCtx.currentTime + 0.20);
        gain2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.70);
        osc2.start(audioCtx.currentTime + 0.15);
        osc2.stop(audioCtx.currentTime + 0.70);
    } catch (e) {
        console.error("Audio Context playback error:", e);
    }
}

// Live orders poll variable
let lastOrderId = <?php 
    $stmt_init = $pdo->query("SELECT MAX(id) as max_id FROM orders");
    echo (int)($stmt_init->fetch()['max_id'] ?? 0); 
?>;

function showToast(message) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = 'p-4 bg-white border border-emerald-250 rounded-2xl shadow-xl flex items-center gap-3 text-xs pointer-events-auto transform translate-y-5 opacity-0 transition-all duration-300';
    toast.innerHTML = `
        <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
            <i class="fas fa-bell animate-bounce"></i>
        </div>
        <div class="flex-1">
            <strong class="font-black text-slate-900 block">Naya Order Aaya!</strong>
            <span class="text-slate-600">${message}</span>
        </div>
    `;
    container.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-y-5', 'opacity-0');
    }, 50);

    // Remove after 7 seconds
    setTimeout(() => {
        toast.classList.add('translate-y-5', 'opacity-0');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 7000);
}

function checkNewOrders() {
    fetch('<?php echo BASE_URL; ?>admin/api/get_latest_orders.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.latest_order_id > lastOrderId) {
                    lastOrderId = data.latest_order_id;
                    
                    // Alert owner
                    playOrderAlert();
                    showToast(`Order #HRT-${String(lastOrderId).padStart(5, '0')} abhi receive hua hai!`);
                    
                    // Update stats
                    const todaySalesEl = document.getElementById('stat-today-sales');
                    if (todaySalesEl && data.today_sales) todaySalesEl.innerText = data.today_sales;

                    const monthSalesEl = document.getElementById('stat-month-sales');
                    if (monthSalesEl && data.month_sales) monthSalesEl.innerText = data.month_sales;

                    const profitEl = document.getElementById('stat-total-profit');
                    if (profitEl && data.total_profit) profitEl.innerText = data.total_profit;

                    const totalVolumeEl = document.getElementById('stat-total-volume');
                    if (totalVolumeEl && data.total_volume) totalVolumeEl.innerText = data.total_volume;

                    const volumeSubEl = document.getElementById('stat-volume-subtitle');
                    if (volumeSubEl && data.volume_subtitle) volumeSubEl.innerText = data.volume_subtitle;

                    // Comparison labels
                    const labelPosEl = document.getElementById('label-pos-share');
                    if (labelPosEl && data.pos_share_val) {
                        labelPosEl.innerHTML = `In-Store POS Sales (${data.pos_pct}%)`;
                    }
                    const labelOnlineEl = document.getElementById('label-online-share');
                    if (labelOnlineEl && data.online_share_val) {
                        labelOnlineEl.innerHTML = `Online Store Deliveries (${data.online_pct}%)`;
                    }

                    // Progress bars
                    const barPosEl = document.getElementById('progress-pos-bar');
                    if (barPosEl) barPosEl.style.width = `${data.pos_pct}%`;
                    
                    const barOnlineEl = document.getElementById('progress-online-bar');
                    if (barOnlineEl) barOnlineEl.style.width = `${data.online_pct}%`;

                    // Update Table
                    const tbodyEl = document.getElementById('recent-orders-tbody');
                    if (tbodyEl && data.orders_html) {
                        tbodyEl.innerHTML = data.orders_html;
                    }

                    // Update Mobile Stacked Card List
                    const mobileListEl = document.getElementById('recent-orders-mobile-list');
                    if (mobileListEl && data.orders_mobile_html) {
                        mobileListEl.innerHTML = data.orders_mobile_html;
                    }
                }
            }
        })
        .catch(err => console.error("Live order fetch failed:", err));
}

// Start AJAX polling check every 15 seconds
setInterval(checkNewOrders, 15000);

// Interaction trigger to play audio
document.addEventListener('click', function() {
    initAudio();
}, { once: true });

function selectTheme(themeKey) {
    document.getElementById('selected-theme-input').value = themeKey;
    document.getElementById('theme-selector-form').submit();
}

function triggerDeleteStaff(staffId, staffName) {
    if (confirm("Kya aap waqai staff member '" + staffName + "' ko delete karna chahte hain?")) {
        const form = document.getElementById('delete-staff-form');
        form.querySelector('input[name="staff_id"]').value = staffId;
        form.submit();
    }
}

function triggerResetPassword(staffId, staffName) {
    const newPassword = prompt("Staff member '" + staffName + "' ke liye naya password darj karein:");
    if (newPassword !== null) {
        if (newPassword.trim() === "") {
            alert("Password khali nahi ho sakta!");
            return;
        }
        const form = document.getElementById('reset-password-form');
        form.querySelector('input[name="staff_id"]').value = staffId;
        form.querySelector('input[name="new_password"]').value = newPassword;
        form.submit();
    }
}

function triggerDeleteDemand(demandId) {
    if (confirm("Kya aap waqai is customer demand request ko delete karna chahte hain?")) {
        const form = document.getElementById('delete-demand-form');
        form.querySelector('input[name="demand_id"]').value = demandId;
        form.submit();
    }
}

function triggerConfirmDemand(demandId) {
    if (confirm("Kya aap is customer demand request ko confirm/resolve karna chahte hain?")) {
        const form = document.getElementById('confirm-demand-form');
        form.querySelector('input[name="demand_id"]').value = demandId;
        form.submit();
    }
}

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(tabId).classList.remove('hidden');

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-emerald-600', 'border-emerald-600');
        btn.classList.add('text-slate-500', 'border-transparent');
    });
    
    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-500', 'border-transparent');
        activeBtn.classList.add('text-emerald-600', 'border-b-2', 'border-emerald-600');
    }
}
</script>

</body>
</html>
