<?php
// HR Traders Dynamic Shipping Fee Checker API
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$address = isset($_GET['address']) ? trim($_GET['address']) : '';

// Helper function to normalize addresses (alphanumeric only, lowercase)
if (!function_exists('normalize_address_for_check')) {
    function normalize_address_for_check($addr) {
        return preg_replace('/[^a-zA-Z0-9]/', '', strtolower($addr));
    }
}

// By default, guests do not qualify for free delivery
$is_first = false;

// Only logged in users qualify for first order free delivery
if (is_logged_in()) {
    $is_first = true;
    
    try {
        // 1. Check by logged-in user ID
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status != 'cancelled'");
        $stmt->execute(['uid' => $_SESSION['user_id']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $is_first = false;
        }
        
        // 2. Check by phone number
        if ($is_first && !empty($phone)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_phone = :phone AND status != 'cancelled'");
            $stmt->execute(['phone' => $phone]);
            if ((int)$stmt->fetchColumn() > 0) {
                $is_first = false;
            }
        }
        
        // 3. Check by normalized address matching
        if ($is_first && !empty($address)) {
            $input_addr_clean = normalize_address_for_check($address);
            
            // Retrieve all distinct non-cancelled order addresses
            $stmt_addr = $pdo->query("SELECT DISTINCT customer_address FROM orders WHERE status != 'cancelled'");
            $addresses = $stmt_addr->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($addresses as $addr) {
                if (normalize_address_for_check($addr) === $input_addr_clean) {
                    $is_first = false;
                    break;
                }
            }
        }
    } catch (PDOException $e) {
        // Fallback on error to standard rate
        $is_first = false;
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
