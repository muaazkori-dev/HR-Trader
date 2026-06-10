<?php
// HR Traders Database Diagnostics & Manual Migrator
// Helps identify exactly why automatic migrations might be failing (e.g., permissions, locks)

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';

echo "<h2>HR Traders Database Diagnostic Tool</h2>";
echo "Connected successfully to database: <strong>" . DB_NAME . "</strong><br><br>";

$migrations = [
    "ALTER TABLE products ADD COLUMN unit VARCHAR(20) DEFAULT 'pcs'",
    "ALTER TABLE products ADD COLUMN purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    "ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL",
    "ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL",
    "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'COD'",
    "ALTER TABLE orders ADD COLUMN status ENUM('pending', 'packaging', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending'",
    "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL",
    "CREATE TABLE IF NOT EXISTS `sales` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `transaction_type` ENUM('POS', 'Online') NOT NULL DEFAULT 'POS',
      `order_id` INT DEFAULT NULL,
      `total_amount` DECIMAL(10,2) NOT NULL,
      `total_profit` DECIMAL(10,2) NOT NULL,
      `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash',
      `cashier_id` INT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "ALTER TABLE sales ADD COLUMN order_id INT DEFAULT NULL",
    "ALTER TABLE sales ADD COLUMN total_profit DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    "ALTER TABLE sales ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash'",
    "ALTER TABLE sales ADD COLUMN cashier_id INT DEFAULT NULL",
    "CREATE TABLE IF NOT EXISTS `sale_items` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `sale_id` INT NOT NULL,
      `product_id` INT NOT NULL,
      `quantity` INT NOT NULL,
      `price` DECIMAL(10,2) NOT NULL,
      `purchase_price` DECIMAL(10,2) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "ALTER TABLE sale_items ADD COLUMN purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00"
];

foreach ($migrations as $sql) {
    echo "Running: <code>$sql</code> ... ";
    try {
        $pdo->exec($sql);
        echo "<span style='color:green;font-weight:bold;'>SUCCESS / ALREADY EXISTS</span><br>";
    } catch (PDOException $e) {
        // If column or table already exists, it might throw an error but that's fine.
        // We want to see the error details if it is a permissions issue or key constraint mismatch.
        echo "<span style='color:orange;'>INFO: " . $e->getMessage() . "</span><br>";
    }
}

echo "<br><strong>Diagnostics completed. Please refresh the admin panel and try again.</strong>";
