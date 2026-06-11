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
    // If logged in, automatically include all active (non-delivered, non-cancelled) orders for tracking
    $logged_in_order_ids = [];
    if (is_logged_in()) {
        try {
            $stmt_u = $pdo->prepare("SELECT id, status FROM orders WHERE user_id = :uid AND status NOT IN ('delivered', 'cancelled')");
            $stmt_u->execute(['uid' => $_SESSION['user_id']]);
            $user_orders = $stmt_u->fetchAll(PDO::FETCH_ASSOC);
            foreach ($user_orders as $uo) {
                $orders[] = $uo;
                $logged_in_order_ids[] = (int)$uo['id'];
            }
        } catch (PDOException $ex) {}
    }

    if (!empty($ids_str)) {
        $ids_arr = array_filter(array_map('intval', explode(',', $ids_str)));
        // Filter out order IDs we already loaded from user account
        $ids_arr = array_diff($ids_arr, $logged_in_order_ids);
        
        if (!empty($ids_arr)) {
            $placeholders = implode(',', array_fill(0, count($ids_arr), '?'));
            $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id IN ($placeholders)");
            $stmt->execute(array_values($ids_arr));
            $fetched_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $orders = array_merge($orders, $fetched_orders);
        }
    }

    // If logged in, automatically include all active (pending) demands for tracking
    $logged_in_demand_ids = [];
    if (is_logged_in()) {
        try {
            // Find phone number of user
            $stmt_ph = $pdo->prepare("SELECT phone FROM users WHERE id = :uid LIMIT 1");
            $stmt_ph->execute(['uid' => $_SESSION['user_id']]);
            $u_phone = $stmt_ph->fetchColumn();
            
            if (!empty($u_phone)) {
                $stmt_d = $pdo->prepare("SELECT id, status, demand_details FROM product_demands WHERE phone = :phone AND status = 'pending'");
                $stmt_d->execute(['phone' => $u_phone]);
                $user_demands = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
                foreach ($user_demands as $ud) {
                    $demands[] = $ud;
                    $logged_in_demand_ids[] = (int)$ud['id'];
                }
            }
        } catch (PDOException $ex) {}
    }

    if (!empty($demands_str)) {
        $demands_arr = array_filter(array_map('intval', explode(',', $demands_str)));
        // Filter out demand IDs we already loaded from user account
        $demands_arr = array_diff($demands_arr, $logged_in_demand_ids);
        
        if (!empty($demands_arr)) {
            $placeholders = implode(',', array_fill(0, count($demands_arr), '?'));
            $stmt = $pdo->prepare("SELECT id, status, demand_details FROM product_demands WHERE id IN ($placeholders)");
            $stmt->execute(array_values($demands_arr));
            $fetched_demands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $demands = array_merge($demands, $fetched_demands);
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
