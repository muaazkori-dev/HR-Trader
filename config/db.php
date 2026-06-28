<?php
// HR Traders Central Configuration File
// Sets up database connection and global helpers

ob_start();

// Prevent dynamic page caching on mobile browsers
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");
}


if (file_exists(__DIR__ . '/../compress_assets.php')) {
    echo "<pre>";
    include __DIR__ . '/../compress_assets.php';
    echo "</pre>";
    @unlink(__DIR__ . '/../compress_assets.php');
}

if (session_status() == PHP_SESSION_NONE) {
    // Keep user logged in for 1 year (31,536,000 seconds) safely
    if (function_exists('session_set_cookie_params')) {
        session_set_cookie_params([
            'lifetime' => 31536000,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    if (function_exists('ini_set')) {
        @ini_set('session.gc_maxlifetime', 31536000);
    }
    session_start();
}

// Determine web-accessible base path dynamically (supports subdirectories like /HR Traders/ or root domain)
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_url = preg_replace('/(\/(admin|pos|api|includes|config|database)(\/.*)?)$/i', '', $script_dir);
$base_url = '/' . trim($base_url, '/') . '/';
if ($base_url === '//') {
    $base_url = '/';
}
define('BASE_URL', $base_url);

// Automatic offline Tailwind CDN downloader hook
$tailwind_local = __DIR__ . '/../assets/js/tailwind.min.js';
if (!file_exists($tailwind_local)) {
    $dir = dirname($tailwind_local);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $url = 'https://cdn.tailwindcss.com';
    $data = @file_get_contents($url);
    if (!$data && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
    }
    if ($data) {
        @file_put_contents($tailwind_local, $data);
    }
}
// Dynamic database credentials check for localhost vs live server
$is_local = false;
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
// Remove port if present (e.g. localhost:8080)
$host_ip = preg_replace('/:.*$/', '', $host);

if ($host_ip === 'localhost' || $host_ip === '127.0.0.1' || $host_ip === '::1') {
    $is_local = true;
} elseif (filter_var($host_ip, FILTER_VALIDATE_IP)) {
    // Check if host_ip is a private (local) IP address requested in the browser
    $is_private = !filter_var(
        $host_ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    );
    if ($is_private) {
        $is_local = true;
    }
}

if ($is_local) {
    // Local XAMPP settings
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'hr_traders');
} else {
    // Live Hostinger settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u622906513_hrtrader');
    define('DB_PASS', 'Haroon124421');
    define('DB_NAME', 'u622906513_hrtrader');
}

// Ensure upload folder exists locally
$target_upload_dir = __DIR__ . '/../assets/images/products';
if ($is_local) {
    if (!is_dir($target_upload_dir)) {
        @mkdir($target_upload_dir, 0777, true);
        @file_put_contents($target_upload_dir . '/.gitkeep', 'keep');
    }
}
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    if (isset($_GET['debug_images'])) {
        $stmt = $pdo->query("SELECT id, name, category, image FROM products ORDER BY id DESC LIMIT 15");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($rows, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Check if migrations have already run
    $run_migrations = true;
    try {
        $stmt_ver = $pdo->query("SELECT val_value FROM settings WHERE key_name = 'db_schema_version' LIMIT 1");
        $row_ver = $stmt_ver->fetch();
        if ($row_ver && $row_ver['val_value'] === '2.7') {
            $run_migrations = false;
        }
    } catch (PDOException $e) {
        // Settings table might not exist yet
    }

    if ($run_migrations) {
        // Automatic DB migrations for category updates
        try {
            $pdo->exec("ALTER TABLE products MODIFY COLUMN category VARCHAR(50) NOT NULL");
    } catch (PDOException $e) {
        // Ignore
    }
    $pdo->exec("UPDATE products SET category = 'anaj' WHERE category = 'pulses_rice'");
    $pdo->exec("UPDATE products SET category = 'anaj' WHERE category = 'snacks_chips'");
    $pdo->exec("UPDATE products SET category = 'beverages' WHERE category IN ('cold_drinks', 'water')");
    $pdo->exec("UPDATE products SET category = 'ice_cream' WHERE category = 'frozen_icecream'");

    // Automatic product table column migrations (self-healing schema)
    try {
        $q = $pdo->query("SHOW COLUMNS FROM products LIKE 'unit'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE products ADD COLUMN unit VARCHAR(20) DEFAULT 'pcs'");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM products LIKE 'purchase_price'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE products ADD COLUMN purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM products LIKE 'image'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM products LIKE 'old_price'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE products ADD COLUMN old_price DECIMAL(10,2) NULL DEFAULT NULL");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM products LIKE 'discount_percentage'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE products ADD COLUMN discount_percentage INT NOT NULL DEFAULT 0");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    // Automatic orders table migrations (add user_id, payment_method, status if missing)
    try {
        $q = $pdo->query("SHOW COLUMNS FROM orders LIKE 'user_id'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL");
            try {
                $pdo->exec("ALTER TABLE orders ADD CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
            } catch (PDOException $ex) {
                // Ignore constraint failure
            }
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'COD'");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN status ENUM('pending', 'packaging', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending'");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    // Automatic users table migrations (add phone, address if missing)
    try {
        $q = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM users LIKE 'address'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    // Automatic sales register tables migration
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sales` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `transaction_type` ENUM('POS', 'Online') NOT NULL DEFAULT 'POS',
          `order_id` INT DEFAULT NULL,
          `total_amount` DECIMAL(10,2) NOT NULL,
          `total_profit` DECIMAL(10,2) NOT NULL,
          `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash',
          `cashier_id` INT DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
          INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        // Ignore
    }

    // Automatic sales table column migrations (if table already exists under old schema)
    try {
        $q = $pdo->query("SHOW COLUMNS FROM sales LIKE 'order_id'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN order_id INT DEFAULT NULL");
            try {
                $pdo->exec("ALTER TABLE sales ADD CONSTRAINT fk_sales_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL");
            } catch (PDOException $ex) {
                // Ignore constraint failure
            }
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM sales LIKE 'total_profit'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN total_profit DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM sales LIKE 'payment_method'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash'");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM sales LIKE 'cashier_id'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN cashier_id INT DEFAULT NULL");
            try {
                $pdo->exec("ALTER TABLE sales ADD CONSTRAINT fk_sales_cashier_id FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL");
            } catch (PDOException $ex) {
                // Ignore constraint failure
            }
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sale_items` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `sale_id` INT NOT NULL,
          `product_id` INT NOT NULL,
          `quantity` INT NOT NULL,
          `price` DECIMAL(10,2) NOT NULL,
          `purchase_price` DECIMAL(10,2) NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        // Ignore
    }

    // Automatic reviews table migration
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `reviews` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `product_id` INT NOT NULL,
          `reviewer_name` VARCHAR(100) NOT NULL,
          `rating` INT NOT NULL,
          `comment` TEXT NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Seed a dummy review if reviews table is empty
        $check_reviews = $pdo->query("SELECT COUNT(*) FROM `reviews`")->fetchColumn();
        if ($check_reviews == 0) {
            $first_product = $pdo->query("SELECT id FROM `products` LIMIT 1")->fetch();
            if ($first_product) {
                $stmt = $pdo->prepare("INSERT INTO `reviews` (product_id, reviewer_name, rating, comment) VALUES (:pid, :name, :rating, :comment)");
                $stmt->execute([
                    'pid' => $first_product['id'],
                    'name' => 'Musa Kori',
                    'rating' => 5,
                    'comment' => 'Bohot achi quality hai aur packaging bhi kamaal ki thi! Shandar service.'
                ]);
            }
        }
    } catch (PDOException $e) {
        // Ignore
    }

    // Automatic sale_items table column migrations (if table already exists under old schema)
    try {
        $q = $pdo->query("SHOW COLUMNS FROM sale_items LIKE 'price'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE sale_items ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    try {
        $q = $pdo->query("SHOW COLUMNS FROM sale_items LIKE 'purchase_price'");
        if (!$q->fetch()) {
            $pdo->exec("ALTER TABLE sale_items ADD COLUMN purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }
    } catch (PDOException $e) {
        // Ignore
    }

    // Automatic settings table schema migration
    try {
        $pdo->query("SELECT 1 FROM `settings` LIMIT 1");
    } catch (PDOException $ex) {
        $schema_path = __DIR__ . '/../database/settings_schema.sql';
        if (file_exists($schema_path)) {
            $queries = file_get_contents($schema_path);
            $queries_arr = explode(';', $queries);
            foreach ($queries_arr as $q) {
                $q = trim($q);
                if (!empty($q)) {
                    $pdo->exec($q);
                }
            }
        }
    }

    // Default standard shipping fee to 180.00 if unset or 0.00
    try {
        $fee_val = get_setting('shipping_fee', '');
        if ($fee_val === '' || (float)$fee_val === 0.00) {
            update_setting('shipping_fee', '180.00');
        }
    } catch (Exception $e) {
        // Ignore
    }

    // Default minimum order value to 250.00 if unset or 0.00
    try {
        $min_val = get_setting('min_order_value', '');
        if ($min_val === '' || (float)$min_val === 0.00) {
            update_setting('min_order_value', '250.00');
        }
    } catch (Exception $e) {
        // Ignore
    }

    // Seed new default social media links if not set or equal to default '#' or empty
    try {
        $fb = get_setting('facebook_url', '#');
        if ($fb === '#' || empty($fb) || $fb === 'https://facebook.com') {
            update_setting('facebook_url', 'https://www.facebook.com/share/19NUvTTDPS/');
        }
        $ig = get_setting('instagram_url', '#');
        if ($ig === '#' || empty($ig) || $ig === 'https://instagram.com') {
            update_setting('instagram_url', 'https://www.instagram.com/hrtraderstdm?utm_source=qr&igsh=OHNjb2Vpb241ZGdq');
        }
        $tt = get_setting('tiktok_url', '#');
        if ($tt === '#' || empty($tt)) {
            update_setting('tiktok_url', 'https://www.tiktok.com/@hr_traders3?_r=1&_t=ZS-97B8A6PrV3p');
        }
        // Auto-healing category image files copy
        $src_grocery_img = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/grocery_icon_1781453922347.png';
        $dest_grocery_img = __DIR__ . '/../assets/images/categories/grocery.png';
        if (file_exists($src_grocery_img) && !file_exists($dest_grocery_img)) {
            @copy($src_grocery_img, $dest_grocery_img);
        }
    } catch (Exception $e) {
        // Ignore
    }

    // Save schema version to settings table to avoid running migrations on every single page load
    try {
        $pdo->prepare("INSERT INTO settings (key_name, val_value) VALUES ('db_schema_version', '2.7') 
                       ON DUPLICATE KEY UPDATE val_value = VALUES(val_value)")->execute();
    } catch (PDOException $ex_ver) {
        // Ignore
    }
    // Syncer moved to end of file to run outside migrations safely
}

} catch (PDOException $e) {
    // If the database does not exist yet, we will output a link to install.php
    die("Database Connection Failed: " . $e->getMessage() . "<br><br><strong>Tip:</strong> If you are setting up the system for the first time, run the <a href='" . BASE_URL . "install.php' style='color:blue;text-decoration:underline;'>System Installer (install.php)</a> to automatically create the database and tables.");
}

$CATEGORIES = [];

$SETTINGS_CACHE = null;

/**
 * Get setting value by key name
 */
function get_setting($key, $default = '') {
    global $pdo, $SETTINGS_CACHE;
    if ($SETTINGS_CACHE === null) {
        $SETTINGS_CACHE = [];
        try {
            $stmt = $pdo->query("SELECT key_name, val_value FROM settings");
            while ($row = $stmt->fetch()) {
                $SETTINGS_CACHE[$row['key_name']] = $row['val_value'];
            }
        } catch (PDOException $e) {
            // Settings table might not exist yet
        }
    }
    return isset($SETTINGS_CACHE[$key]) ? $SETTINGS_CACHE[$key] : $default;
}

/**
 * Update setting value by key name
 */
function update_setting($key, $value) {
    global $pdo, $SETTINGS_CACHE;
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, val_value) VALUES (:key, :val) 
                               ON DUPLICATE KEY UPDATE val_value = VALUES(val_value)");
        $stmt->execute(['key' => $key, 'val' => $value]);
        if ($SETTINGS_CACHE !== null) {
            $SETTINGS_CACHE[$key] = $value;
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
$categories_json = get_setting('store_categories', '');
if (empty($categories_json)) {
    $CATEGORIES = [
        'anaj' => ['name' => 'Anaj', 'urdu' => 'اناج'],
        'grocery' => ['name' => 'Grocery', 'urdu' => 'گروسری'],
        'ice_cream' => ['name' => 'Ice Cream', 'urdu' => 'آئس کریم', 'alert' => 'Available for nearby locations only'],
        'beverages' => ['name' => 'Beverages', 'urdu' => 'مشروبات'],
        'milk' => ['name' => 'Milk', 'urdu' => 'دودھ'],
        'cosmetics' => ['name' => 'Cosmetics', 'urdu' => 'کاسمیٹکس'],
        'snacks' => ['name' => 'Snacks', 'urdu' => 'سنیکس'],
        'bakery' => ['name' => 'Bakery', 'urdu' => 'بیکری'],
        'sauce' => ['name' => 'Sauces', 'urdu' => 'سوس'],
        'shampoo' => ['name' => 'Cosmetics (Shampoo)', 'urdu' => 'کاسمیٹکس (شیمپو)', 'parent' => 'cosmetics'],
        'soap' => ['name' => 'Cosmetics (Soap)', 'urdu' => 'کاسمیٹکس (صابن)', 'parent' => 'cosmetics'],
        'toothpaste' => ['name' => 'Cosmetics (Toothpaste)', 'urdu' => 'کاسمیٹکس (ٹوتھ پیسٹ)', 'parent' => 'cosmetics'],
        'body_wash' => ['name' => 'Cosmetics (Body Wash)', 'urdu' => 'کاسمیٹکس (باڈی واش)', 'parent' => 'cosmetics'],
        'deodorant' => ['name' => 'Cosmetics (Deodorant)', 'urdu' => 'کاسمیٹکس (ڈیوڈرینٹ)', 'parent' => 'cosmetics']
    ];
    update_setting('store_categories', json_encode($CATEGORIES));
} else {
    $CATEGORIES = json_decode($categories_json, true);
}

// Define dynamic global metadata configurations (with database settings override)
define('STORE_NAME', get_setting('store_name', 'HR Traders'));
define('CURRENCY', get_setting('store_currency', 'Rs.'));
define('WHATSAPP_NUMBER', get_setting('whatsapp_number', '923033943814')); // WhatsApp shop number (international format without +)

/**
 * Get category icon URL, with clean SVG fallback data URI if file is missing from disk
 */
function get_category_icon_url($category) {
    $mapping = [
        'beverages' => 'cold_drinks',
        'shampoo' => 'cosmetics',
        'soap' => 'cosmetics',
        'toothpaste' => 'cosmetics',
        'body_wash' => 'cosmetics',
        'deodorant' => 'cosmetics'
    ];
    $category_key = isset($mapping[$category]) ? $mapping[$category] : $category;
    
    $local_path = 'assets/images/categories/' . $category_key . '.png';
    if ($category_key === 'ice_cream' || $category_key === 'milk') {
        // Double check jpg extension fallback
        if (!file_exists(__DIR__ . '/../' . $local_path)) {
            $local_jpg = 'assets/images/categories/' . $category_key . '.jpg';
            if (file_exists(__DIR__ . '/../' . $local_jpg)) {
                return BASE_URL . $local_jpg;
            }
        }
    }
    
    if (file_exists(__DIR__ . '/../' . $local_path)) {
        return BASE_URL . $local_path;
    }

    // Dynamic, high-quality, lightweight SVG fallbacks keyed by category type
    $fallbacks = [
        'anaj' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23ecfdf5" stroke="%2334d399" stroke-width="2"/><path d="M50 22 C55 37, 65 47, 50 82 C35 47, 45 37, 50 22 Z" fill="%23059669"/><path d="M50 32 C53 43, 58 48, 50 72 C42 48, 47 43, 50 32 Z" fill="%2334d399"/></svg>',
        'grocery' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23f0fdf4" stroke="%2310b981" stroke-width="2"/><path d="M30 32 h40 l-8 30 h-24 Z M34 62 l-4 13 M62 62 l4 13" stroke="%23059669" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/><circle cx="34" cy="75" r="4" fill="%23059669"/><circle cx="66" cy="75" r="4" fill="%23059669"/></svg>',
        'ice_cream' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23fff5f5" stroke="%23f87171" stroke-width="2"/><path d="M38 52 L50 82 L62 52 Z" fill="%23d97706"/><circle cx="50" cy="42" r="16" fill="%23e11d48"/><circle cx="45" cy="46" r="12" fill="%23f43f5e"/><circle cx="55" cy="46" r="10" fill="%23fb7185"/></svg>',
        'beverages' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23f0f9ff" stroke="%2338bdf8" stroke-width="2"/><path d="M38 32 L42 76 h16 L62 32 Z" fill="%230284c7"/><path d="M42 22 L50 32 M34 32 h32" stroke="%230284c7" stroke-width="4" stroke-linecap="round"/></svg>',
        'milk' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23f8fafc" stroke="%2394a3b8" stroke-width="2"/><path d="M42 22 h16 v8 h-16 Z M37 30 h26 v46 H37 Z" fill="%23cbd5e1" stroke="%23475569" stroke-width="3"/><rect x="43" y="42" width="14" height="18" fill="%2338bdf8"/></svg>',
        'cosmetics' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23fdf2f8" stroke="%23f472b6" stroke-width="2"/><rect x="43" y="48" width="14" height="30" rx="2" fill="%23be185d"/><path d="M45 48 L45 28 C45 23, 55 23, 55 28 L55 48 Z" fill="%23db2777"/></svg>',
        'snacks' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23fef3c7" stroke="%23fbbf24" stroke-width="2"/><path d="M32 28 L68 28 L72 72 L28 72 Z" fill="%23d97706"/><path d="M32 28 l6 6 l6-6 l6 6 l6-6 l6 6 l6-6 l6 6 l6-6" stroke="%2392400e" stroke-width="3" fill="none"/></svg>',
        'bakery' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23fffbeb" stroke="%23fbbf24" stroke-width="2"/><path d="M32 62 C32 48, 40 43, 50 43 C60 43, 68 48, 68 62 Z" fill="%23d97706" stroke="%2392400e" stroke-width="3"/><path d="M28 62 h44 v8 h-44 Z" fill="%2392400e"/></svg>',
        'sauce' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="48" fill="%23fff1f2" stroke="%23fda4af" stroke-width="2"/><path d="M44 22 h12 v10 h-12 Z M37 32 L42 44 v30 h16 V44 L63 32 Z" fill="%23be123c"/><path d="M42 52 h16" stroke="%23ffffff" stroke-width="3"/></svg>'
    ];

    if (isset($fallbacks[$category_key])) {
        return $fallbacks[$category_key];
    }
    
    // Default dynamic placeholder link fallback if key not matched
    return 'https://placehold.co/100x100/10b981/ffffff?text=' . urlencode(ucfirst($category_key));
}

// Live self-healing database-driven product images restore (runs outside migrations)
// Checked at most once every 5 minutes per user session to minimize filesystem reads
if (isset($pdo) && !$is_local) {
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }
    if (!isset($_SESSION['images_synced_time']) || (time() - $_SESSION['images_synced_time']) > 300) {
        try {
            $public_html_root = dirname(__DIR__);
            $target_upload_dir = $public_html_root . '/assets/images/products';
            $backup_upload_dir = dirname($public_html_root) . '/product_uploads';

            if (!@is_dir($target_upload_dir)) {
                @mkdir($target_upload_dir, 0777, true);
            }
            if (!@is_dir($backup_upload_dir)) {
                @mkdir($backup_upload_dir, 0777, true);
            }

            // Sync missing product images using DB active paths
            $stmt_sync = $pdo->query("SELECT image FROM products WHERE image IS NOT NULL AND image != ''");
            while ($row_sync = $stmt_sync->fetch()) {
                $img_rel_path = $row_sync['image'];
                $filename = basename($img_rel_path);
                
                $t_file = $target_upload_dir . '/' . $filename;
                $b_file = $backup_upload_dir . '/' . $filename;
                
                if (!@file_exists($t_file) && @file_exists($b_file) && @is_file($b_file)) {
                    @copy($b_file, $t_file);
                }
            }

            // Sync promo popup image if set and missing
            $promo_img = get_setting('promo_popup_image', '');
            if (!empty($promo_img)) {
                $filename = basename($promo_img);
                $t_file = $target_upload_dir . '/' . $filename;
                $b_file = $backup_upload_dir . '/' . $filename;
                if (!@file_exists($t_file) && @file_exists($b_file) && @is_file($b_file)) {
                    @copy($b_file, $t_file);
                }
            }

            // Sync promo cards images if set and missing
            $promo_cards_json = get_setting('homepage_promo_cards', '');
            if (!empty($promo_cards_json)) {
                $cards = json_decode($promo_cards_json, true);
                if (is_array($cards)) {
                    foreach ($cards as $c) {
                        $img = $c['image'] ?? '';
                        if (!empty($img) && strpos($img, 'categories/') === false) {
                            $filename = basename($img);
                            $t_file = $target_upload_dir . '/' . $filename;
                            $b_file = $backup_upload_dir . '/' . $filename;
                            if (!@file_exists($t_file) && @file_exists($b_file) && @is_file($b_file)) {
                                @copy($b_file, $t_file);
                            }
                        }
                    }
                }
            }

            // Sync category custom icons if missing from disk
            $sync_cats_json = get_setting('store_categories', '');
            $sync_cats = !empty($sync_cats_json) ? json_decode($sync_cats_json, true) : [];
            if (is_array($sync_cats)) {
                foreach (array_keys($sync_cats) as $cat_k) {
                    $t_file = $public_html_root . '/assets/images/categories/' . $cat_k . '.png';
                    $b_file = dirname($public_html_root) . '/product_uploads/categories/' . $cat_k . '.png';
                    if (!@file_exists($t_file) && @file_exists($b_file) && @is_file($b_file)) {
                        $cat_dir = dirname($t_file);
                        if (!@is_dir($cat_dir)) {
                            @mkdir($cat_dir, 0777, true);
                        }
                        @copy($b_file, $t_file);
                    }
                }
            }
            $_SESSION['images_synced_time'] = time();
        } catch (Throwable $sync_err) {
            // Silently catch sync errors to prevent crash
        }
    }
}