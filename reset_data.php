<?php
// HR Traders - Database Reset Script
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: text/html; charset=utf-8');

// Determine which user IDs to preserve (default admin users and the current active testing session)
$keep_user_ids = [];

// Seed defaults from schema
$keep_user_ids[] = 1; // Default Owner
$keep_user_ids[] = 2; // Default Manager
$keep_user_ids[] = 3; // Default Test Customer

if (isset($_SESSION['user_id'])) {
    $keep_user_ids[] = (int)$_SESSION['user_id'];
}

$keep_ids_str = implode(',', array_filter(array_unique($keep_user_ids)));

try {
    // Disable foreign keys to truncate safely
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 1. Clear all orders & order line items
    $pdo->exec("TRUNCATE TABLE order_items");
    $pdo->exec("TRUNCATE TABLE orders");

    // 2. Clear all sales entries & cashier registry logs
    $pdo->exec("TRUNCATE TABLE sale_items");
    $pdo->exec("TRUNCATE TABLE sales");

    // 3. Clear customer demands
    $pdo->exec("TRUNCATE TABLE product_demands");

    // 4. Delete custom test accounts, preserving admins and current logged-in user
    if (!empty($keep_ids_str)) {
        $pdo->exec("DELETE FROM users WHERE id NOT IN ($keep_ids_str) AND role NOT IN ('owner', 'manager')");
    } else {
        $pdo->exec("DELETE FROM users WHERE role NOT IN ('owner', 'manager')");
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<div style='font-family: sans-serif; max-width: 500px; margin: 40px auto; padding: 20px; border: 1px solid #10b981; background-color: #f0fdf4; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>";
    echo "<h2 style='color: #10b981; margin-top: 0;'>Database Reset Completed!</h2>";
    echo "<p style='color: #374151; font-size: 14px;'>Saray test orders, sales records, demands, aur fake test sign-ups ko kamyabi se clear kar diya gaya hai.</p>";
    echo "<p style='color: #374151; font-size: 14px;'>Aapka active logged-in session aur default admin credentials bilkul mehfooz hain.</p>";
    echo "<hr style='border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;'>";
    echo "<p style='color: #6b7280; font-size: 12px; font-style: italic;'>Security Notice: Yeh script chalne ke baad server se khud-ba-khud delete (self-deleted) ho gayi hai.</p>";
    echo "</div>";

} catch (Exception $e) {
    // Attempt recovery of foreign key checks in case of error
    try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); } catch(Exception $ex) {}
    
    echo "<div style='font-family: sans-serif; max-width: 500px; margin: 40px auto; padding: 20px; border: 1px solid #fecaca; background-color: #fef2f2; border-radius: 12px; color: #991b1b;'>";
    echo "<h2 style='margin-top: 0;'>Reset Failed!</h2>";
    echo "<p style='font-size: 14px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Self-delete the file for security
@unlink(__FILE__);
exit();
