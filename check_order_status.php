<?php
// HR Traders Customer Order & Demand Status Checker API
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$ids_str = isset($_GET['ids']) ? trim($_GET['ids']) : '';
$demands_str = isset($_GET['demands']) ? trim($_GET['demands']) : '';

$orders = [];
$demands = [];

try {
    if (!empty($ids_str)) {
        $ids_arr = array_filter(array_map('intval', explode(',', $ids_str)));
        if (!empty($ids_arr)) {
            $placeholders = implode(',', array_fill(0, count($ids_arr), '?'));
            $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id IN ($placeholders)");
            $stmt->execute($ids_arr);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!empty($demands_str)) {
        $demands_arr = array_filter(array_map('intval', explode(',', $demands_str)));
        if (!empty($demands_arr)) {
            $placeholders = implode(',', array_fill(0, count($demands_arr), '?'));
            $stmt = $pdo->prepare("SELECT id, status, demand_details FROM product_demands WHERE id IN ($placeholders)");
            $stmt->execute($demands_arr);
            $demands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'demands' => $demands
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit();
