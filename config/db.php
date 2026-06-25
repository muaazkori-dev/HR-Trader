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
    
    // Check if migrations have already run
    $run_migrations = true;
    try {
        $stmt_ver = $pdo->query("SELECT val_value FROM settings WHERE key_name = 'db_schema_version' LIMIT 1");
        $row_ver = $stmt_ver->fetch();
        if ($row_ver && $row_ver['val_value'] === '2.6') {
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
        $pdo->prepare("INSERT INTO settings (key_name, val_value) VALUES ('db_schema_version', '2.6') 
                       ON DUPLICATE KEY UPDATE val_value = VALUES(val_value)")->execute();
    } catch (PDOException $ex_ver) {
        // Ignore
    }
}

} catch (PDOException $e) {
    // If the database does not exist yet, we will output a link to install.php
    die("Database Connection Failed: " . $e->getMessage() . "<br><br><strong>Tip:</strong> If you are setting up the system for the first time, run the <a href='" . BASE_URL . "install.php' style='color:blue;text-decoration:underline;'>System Installer (install.php)</a> to automatically create the database and tables.");
}

// Categories helper map (with English keys, English display, and Urdu translation)
$CATEGORIES = [
    'anaj' => [
        'name' => 'Anaj',
        'urdu' => 'اناج'
    ],
    'grocery' => [
        'name' => 'Grocery',
        'urdu' => 'گروسری'
    ],
    'ice_cream' => [
        'name' => 'Ice Cream',
        'urdu' => 'آئس کریم',
        'alert' => 'Available for nearby locations only'
    ],
    'beverages' => [
        'name' => 'Beverages',
        'urdu' => 'مشروبات'
    ],
    'milk' => [
        'name' => 'Milk',
        'urdu' => 'دودھ'
    ],
    'cosmetics' => [
        'name' => 'Cosmetics',
        'urdu' => 'کاسمیٹکس'
    ],
    'snacks' => [
        'name' => 'Snacks',
        'urdu' => 'سنیکس'
    ],
    'bakery' => [
        'name' => 'Bakery',
        'urdu' => 'بیکری'
    ],
    'sauce' => [
        'name' => 'Sauce',
        'urdu' => 'سوس'
    ],
    // Sub-items map to Cosmetics for unified display
    'shampoo' => [
        'name' => 'Cosmetics (Shampoo)',
        'urdu' => 'کاسمیٹکس (شیمپو)',
        'parent' => 'cosmetics'
    ],
    'soap' => [
        'name' => 'Cosmetics (Soap)',
        'urdu' => 'کاسمیٹکس (صابن)',
        'parent' => 'cosmetics'
    ],
    'toothpaste' => [
        'name' => 'Cosmetics (Toothpaste)',
        'urdu' => 'کاسمیٹکس (ٹوتھ پیسٹ)',
        'parent' => 'cosmetics'
    ],
    'body_wash' => [
        'name' => 'Cosmetics (Body Wash)',
        'urdu' => 'کاسمیٹکس (باڈی واش)',
        'parent' => 'cosmetics'
    ],
    'deodorant' => [
        'name' => 'Cosmetics (Deodorant)',
        'urdu' => 'کاسمیٹکس (ڈیوڈرینٹ)',
        'parent' => 'cosmetics'
    ]
];

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

// Define dynamic global metadata configurations (with database settings override)
define('STORE_NAME', get_setting('store_name', 'HR Traders'));
define('CURRENCY', get_setting('store_currency', 'Rs.'));
define('WHATSAPP_NUMBER', get_setting('whatsapp_number', '923033943814')); // WhatsApp shop number (international format without +)