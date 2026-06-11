<?php
// HR Traders Dynamic Shipping Fee Checker API
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$is_first = true;

// 1. Check by logged-in user ID
if (is_logged_in()) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status != 'cancelled'");
        $stmt->execute(['uid' => $_SESSION['user_id']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $is_first = false;
        }
    } catch (PDOException $e) {
        // Ignore
    }
}

// 2. Check by phone number
if ($is_first && !empty($phone)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_phone = :phone AND status != 'cancelled'");
        $stmt->execute(['phone' => $phone]);
        if ((int)$stmt->fetchColumn() > 0) {
            $is_first = false;
        }
    } catch (PDOException $e) {
        // Ignore
    }
}

// Get standard shipping fee setting, default to 180.00
$standard_fee = (float)get_setting('shipping_fee', '180.00');
$fee = $is_first ? 0.00 : $standard_fee;

echo json_encode([
    'success' => true,
    'is_first' => $is_first,
    'shipping_fee' => $fee,
    'standard_fee' => $standard_fee
]);
exit();
