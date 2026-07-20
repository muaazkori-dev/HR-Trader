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
        // Fetch order details for notification
        $stmt_cust = $pdo->prepare("SELECT customer_name, customer_phone FROM orders WHERE id = :id");
        $stmt_cust->execute(['id' => $order_id]);
        $orderObj = $stmt_cust->fetch();
        
        if ($orderObj) {
            $name = $orderObj['customer_name'];
            $phone = $orderObj['customer_phone'];
            $padRef = str_pad($order_id, 5, '0', STR_PAD_LEFT);
            
            $messageBody = '';
            switch ($new_status) {
                case 'packaging':
                    $messageBody = "Hi {$name}, your order #HRT-{$padRef} is currently being packed!";
                    break;
                case 'out_for_delivery':
                    $messageBody = "Hi {$name}, your order #HRT-{$padRef} is out for delivery! Our rider is on their way.";
                    break;
                case 'delivered':
                    $messageBody = "Hi {$name}, your order #HRT-{$padRef} has been successfully delivered! Thank you.";
                    break;
                case 'cancelled':
                    $messageBody = "Hi {$name}, your order #HRT-{$padRef} has been cancelled.";
                    break;
                default:
                    $messageBody = "Hi {$name}, your order #HRT-{$padRef} status updated to: {$new_status}";
            }
            
            // Call next-store api using curl
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost:3000';
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $notify_url = $protocol . $host . '/api/push-notify';
            if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
                $notify_url = 'http://localhost:3000/api/push-notify';
            }
            
            $ch = curl_init($notify_url);
            $payload = json_encode([
                'phone' => $phone,
                'title' => 'Order Update - HR Traders',
                'body' => $messageBody,
                'url' => '/my-account'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            curl_close($ch);
        }

        echo json_encode(['success' => true, 'message' => "Order status updated to '" . ucfirst($new_status) . "' successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit();
