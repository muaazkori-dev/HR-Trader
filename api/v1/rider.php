<?php
// HR Traders Delivery Rider REST API Endpoint (v1)
// Handles assigned orders, 1-click navigation coordinates, live GPS pinging, and cash collection

require_once __DIR__ . '/helpers.php';

$auth = require_auth('rider');
$rider_id = $auth['user_id'];
$data = get_request_data();
$action = $data['action'] ?? ($_GET['action'] ?? 'assigned_orders');

switch ($action) {
    case 'assigned_orders':
        try {
            $stmt = $pdo->prepare("
                SELECT o.*, COUNT(oi.id) as item_count 
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.rider_id = :rid AND o.status IN ('packaging', 'out_for_delivery') 
                GROUP BY o.id 
                ORDER BY o.id DESC
            ");
            $stmt->execute(['rid' => $rider_id]);
            $orders = $stmt->fetchAll();

            $formatted = [];
            foreach ($orders as $ord) {
                // Fetch order items summary
                $it_stmt = $pdo->prepare("SELECT oi.quantity, oi.price, p.name, p.weight, p.unit 
                                          FROM order_items oi 
                                          JOIN products p ON oi.product_id = p.id 
                                          WHERE oi.order_id = :oid");
                $it_stmt->execute(['oid' => $ord['id']]);
                $items = $it_stmt->fetchAll();

                $has_gps = !empty($ord['latitude']) && !empty($ord['longitude']);
                $maps_url = $has_gps 
                    ? "https://www.google.com/maps/dir/?api=1&destination={$ord['latitude']},{$ord['longitude']}&travelmode=two-wheeler"
                    : "https://www.google.com/maps/search/?api=1&query=" . urlencode($ord['customer_address'] . ', Tando Adam');

                $formatted[] = [
                    'order_id' => (int)$ord['id'],
                    'order_ref' => '#HRT-' . str_pad($ord['id'], 5, '0', STR_PAD_LEFT),
                    'status' => $ord['status'],
                    'customer_name' => $ord['customer_name'],
                    'customer_phone' => $ord['customer_phone'],
                    'customer_address' => $ord['customer_address'],
                    'delivery_lat' => !empty($ord['latitude']) ? (float)$ord['latitude'] : null,
                    'delivery_lng' => !empty($ord['longitude']) ? (float)$ord['longitude'] : null,
                    'delivery_notes' => $ord['delivery_notes'] ?? '',
                    'total_amount' => (float)$ord['total_amount'],
                    'cash_to_collect' => $ord['payment_method'] === 'COD' ? (float)$ord['total_amount'] : 0.00,
                    'payment_method' => $ord['payment_method'],
                    'created_at' => $ord['created_at'],
                    'navigation_url' => $maps_url,
                    'items' => $items
                ];
            }

            send_success($formatted);
        } catch (PDOException $e) {
            send_error('Failed to load rider orders: ' . $e->getMessage(), 500);
        }
        break;

    case 'update_status':
        $order_id = (int)($data['order_id'] ?? 0);
        $new_status = trim($data['status'] ?? ''); // 'out_for_delivery' or 'delivered'

        if ($order_id <= 0 || !in_array($new_status, ['out_for_delivery', 'delivered'])) {
            send_error('Valid order ID and status (out_for_delivery or delivered) are required.');
        }

        try {
            // Check order ownership
            $chk = $pdo->prepare("SELECT id, status FROM orders WHERE id = :id AND rider_id = :rid LIMIT 1");
            $chk->execute(['id' => $order_id, 'rid' => $rider_id]);
            $ord = $chk->fetch();

            if (!$ord && $auth['role'] !== 'owner') {
                send_error('Order is not assigned to you.', 403);
            }

            if ($new_status === 'out_for_delivery') {
                $upd = $pdo->prepare("UPDATE orders SET status = 'out_for_delivery', picked_at = COALESCE(picked_at, NOW()) WHERE id = :id");
                $upd->execute(['id' => $order_id]);
                $msg = 'Order picked up. On the way to customer!';
            } else {
                $upd = $pdo->prepare("UPDATE orders SET status = 'delivered', delivered_at = NOW() WHERE id = :id");
                $upd->execute(['id' => $order_id]);
                $msg = 'Order marked as Delivered! Cash collected.';
            }

            send_success(['order_id' => $order_id, 'status' => $new_status], $msg);
        } catch (PDOException $e) {
            send_error('Status update failed: ' . $e->getMessage(), 500);
        }
        break;

    case 'update_location':
        $lat = !empty($data['latitude']) ? (float)$data['latitude'] : null;
        $lng = !empty($data['longitude']) ? (float)$data['longitude'] : null;
        $heading = isset($data['heading']) ? (float)$data['heading'] : 0.00;

        if ($lat === null || $lng === null) {
            send_error('Latitude and Longitude are required.');
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO rider_locations (rider_id, latitude, longitude, heading, updated_at) 
                VALUES (:rid, :lat, :lng, :heading, NOW()) 
                ON DUPLICATE KEY UPDATE 
                    latitude = VALUES(latitude), 
                    longitude = VALUES(longitude), 
                    heading = VALUES(heading), 
                    updated_at = NOW()
            ");
            $stmt->execute([
                'rid' => $rider_id,
                'lat' => $lat,
                'lng' => $lng,
                'heading' => $heading
            ]);

            send_success(['rider_id' => $rider_id, 'lat' => $lat, 'lng' => $lng], 'Location updated.');
        } catch (PDOException $e) {
            send_error('Location update failed: ' . $e->getMessage(), 500);
        }
        break;

    case 'today_summary':
        try {
            // Count today's delivered orders and total COD cash
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as delivered_count, 
                    COALESCE(SUM(total_amount), 0) as total_cash_collected 
                FROM orders 
                WHERE rider_id = :rid 
                  AND status = 'delivered' 
                  AND DATE(COALESCE(delivered_at, created_at)) = CURDATE()
            ");
            $stmt->execute(['rid' => $rider_id]);
            $stats = $stmt->fetch();

            send_success([
                'delivered_count' => (int)$stats['delivered_count'],
                'total_cash_collected' => (float)$stats['total_cash_collected'],
                'date' => date('Y-m-d')
            ]);
        } catch (PDOException $e) {
            send_error('Summary failed: ' . $e->getMessage(), 500);
        }
        break;

    default:
        send_error('Invalid rider action requested.');
}
