<?php
// HR Traders API - Link Guest Orders to Authenticated Account
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required to link orders.']);
    exit();
}

$orders_str = isset($_POST['orders']) ? trim($_POST['orders']) : '';

if (empty($orders_str)) {
    echo json_encode(['success' => true, 'message' => 'No orders to link.']);
    exit();
}

$order_ids = array_filter(array_map('intval', explode(',', $orders_str)));

if (empty($order_ids)) {
    echo json_encode(['success' => true, 'message' => 'No valid order IDs provided.']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // We only link orders where user_id is currently NULL (guest) to prevent claiming other accounts' orders
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt = $pdo->prepare("UPDATE orders SET user_id = ? WHERE id IN ($placeholders) AND user_id IS NULL");
    
    // Bind parameters: user_id followed by the order_ids array
    $params = array_merge([$user_id], $order_ids);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Guest orders successfully linked to account.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error while linking orders: ' . $e->getMessage()]);
}
exit();
