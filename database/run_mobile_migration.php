<?php
// HR Traders Safe Database Migration Runner for Mobile Apps
// Can be executed via CLI or accessed directly via browser

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$results = [
    'success' => true,
    'messages' => []
];

try {
    // 1. Check & Update users role ENUM
    $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('owner', 'manager', 'customer', 'rider') NOT NULL DEFAULT 'customer'");
    $results['messages'][] = "Role 'rider' added to users table successfully.";

    // Helper function to check if column exists
    function ensure_column($pdo, $table, $column, $definition, &$messages) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
        $stmt->execute(['col' => $column]);
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            $messages[] = "Column '{$column}' added to '{$table}'.";
        } else {
            $messages[] = "Column '{$column}' already exists in '{$table}'.";
        }
    }

    // 2. Add fcm_token to users
    ensure_column($pdo, 'users', 'fcm_token', "VARCHAR(255) DEFAULT NULL AFTER `address`", $results['messages']);

    // 3. Add delivery & tracking columns to orders
    ensure_column($pdo, 'orders', 'rider_id', "INT DEFAULT NULL AFTER `user_id`", $results['messages']);
    ensure_column($pdo, 'orders', 'latitude', "DECIMAL(10, 8) DEFAULT NULL AFTER `customer_address`", $results['messages']);
    ensure_column($pdo, 'orders', 'longitude', "DECIMAL(11, 8) DEFAULT NULL AFTER `latitude`", $results['messages']);
    ensure_column($pdo, 'orders', 'delivery_notes', "TEXT DEFAULT NULL AFTER `longitude`", $results['messages']);
    ensure_column($pdo, 'orders', 'assigned_at', "DATETIME DEFAULT NULL AFTER `status`", $results['messages']);
    ensure_column($pdo, 'orders', 'picked_at', "DATETIME DEFAULT NULL AFTER `assigned_at`", $results['messages']);
    ensure_column($pdo, 'orders', 'delivered_at', "DATETIME DEFAULT NULL AFTER `picked_at`", $results['messages']);

    // 4. Create rider_locations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rider_locations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `rider_id` INT NOT NULL UNIQUE,
        `latitude` DECIMAL(10, 8) NOT NULL,
        `longitude` DECIMAL(11, 8) NOT NULL,
        `heading` DECIMAL(5, 2) DEFAULT 0.00,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`rider_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $results['messages'][] = "Table 'rider_locations' verified.";

    // 5. Create default Rider user if not present (Password: rider123)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'rider1' OR role = 'rider' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('rider123', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (username, password, role, name, phone, address) VALUES ('rider1', :pwd, 'rider', 'Express Rider 1', '03001234567', 'Tando Adam')");
        $ins->execute(['pwd' => $hash]);
        $results['messages'][] = "Demo Rider created: username='rider1', password='rider123'";
    } else {
        $results['messages'][] = "Rider user already exists.";
    }

} catch (PDOException $e) {
    $results['success'] = false;
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
