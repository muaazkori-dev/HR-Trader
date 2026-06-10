<?php
// HR Traders Admin Order Status Updater API
// Handles status transitions from dashboard (Pending -> Packaging -> Out for Delivery -> Delivered / Cancelled)

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Restrict to staff (owner / manager)
if (!is_logged_in() || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$new_status = isset($input['status']) ? trim($input['status']) : '';

$valid_statuses = ['pending', 'packaging', 'out_for_delivery', 'delivered', 'cancelled'];

if ($order_id <= 0 || !in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order details or status state']);
    exit();
}

try {
    $cashier_id = $_SESSION['user_id'];

    if ($new_status === 'delivered') {
        // Triggers order fulfillment log and sales register insertion
        $result = deliver_online_order($pdo, $order_id, $cashier_id);
    } elseif ($new_status === 'cancelled') {
        // Triggers order cancellation and returns items back to stock
        $result = cancel_online_order($pdo, $order_id);
    } else {
        // Normal state transition (Pending -> Packaging -> Out for Delivery)
        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $result = $stmt->execute(['status' => $new_status, 'id' => $order_id]);
    }

    if ($result) {
        echo json_encode(['success' => true, 'message' => "Order status updated to '" . ucfirst($new_status) . "' successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit();
