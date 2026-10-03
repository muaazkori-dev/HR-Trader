<?php
// HR Traders Orders & Live Tracking REST API Endpoint (v1)
// Handles mobile checkout, order history, and real-time delivery status

require_once __DIR__ . '/helpers.php';

$data = get_request_data();
$action = $data['action'] ?? ($_GET['action'] ?? 'create');

switch ($action) {
    case 'create':
        $customer_name = trim($data['customer_name'] ?? '');
        $customer_phone = trim($data['customer_phone'] ?? '');
        $customer_address = trim($data['customer_address'] ?? '');
        $latitude = !empty($data['latitude']) ? (float)$data['latitude'] : null;
        $longitude = !empty($data['longitude']) ? (float)$data['longitude'] : null;
        $delivery_notes = trim($data['delivery_notes'] ?? '');
        $payment_method = trim($data['payment_method'] ?? 'COD');
        $items = $data['items'] ?? [];

        if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
            send_error('Customer name, phone, and complete delivery address are required.');
        }

        if (empty($items) || !is_array($items)) {
            send_error('Order cart cannot be empty.');
        }

        // Get authenticated user ID if logged in, else null
        $auth_user = verify_mobile_token();
        $user_id = $auth_user ? $auth_user['user_id'] : null;

        $pdo->beginTransaction();
        try {
            $total_amount = 0.00;
            $verified_items = [];

            // 1. Verify stock and calculate price from DB (prevents client price tampering)
            foreach ($items as $item) {
                $pid = (int)($item['product_id'] ?? ($item['id'] ?? 0));
                $qty = max(1, (int)($item['quantity'] ?? ($item['qty'] ?? 1)));

                $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = :id FOR UPDATE");
                $stmt->execute(['id' => $pid]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new Exception("Product ID {$pid} no longer exists.");
                }

                if ($product['stock_quantity'] < $qty) {
                    throw new Exception("Only {$product['stock_quantity']} units left for '{$product['name']}'. Please adjust your cart.");
                }

                $price = (float)$product['price'];
                $line_total = $price * $qty;
                $total_amount += $line_total;

                $verified_items[] = [
                    'product_id' => $pid,
                    'name' => $product['name'],
                    'price' => $price,
                    'quantity' => $qty
                ];
            }

            // Get shipping fee from settings or default to 0 if threshold met
            $shipping_fee = 0.00;
            try {
                $ship_stmt = $pdo->prepare("SELECT val_value FROM settings WHERE key_name = 'shipping_fee' LIMIT 1");
                $ship_stmt->execute();
                $fee_val = $ship_stmt->fetchColumn();
                if ($fee_val !== false) {
                    $shipping_fee = (float)$fee_val;
                }
            } catch (Exception $e) {
                $shipping_fee = 0.00;
            }

            $order_total = $total_amount + $shipping_fee;

            // 2. Insert into orders table
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, customer_name, customer_phone, customer_address, 
                    latitude, longitude, delivery_notes, total_amount, payment_method, status
                ) VALUES (
                    :user_id, :name, :phone, :address, 
                    :lat, :lng, :notes, :total, :payment_method, 'pending'
                )
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'name' => $customer_name,
                'phone' => $customer_phone,
                'address' => $customer_address,
                'lat' => $latitude,
                'lng' => $longitude,
                'notes' => !empty($delivery_notes) ? $delivery_notes : null,
                'total' => $order_total,
                'payment_method' => $payment_method
            ]);
            $order_id = (int)$pdo->lastInsertId();

            // 3. Insert order items & decrement stock
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (:order_id, :pid, :price, :qty)");
            $stock_stmt = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - :qty) WHERE id = :id");

            foreach ($verified_items as $vi) {
                $item_stmt->execute([
                    'order_id' => $order_id,
                    'pid' => $vi['product_id'],
                    'price' => $vi['price'],
                    'qty' => $vi['quantity']
                ]);

                $stock_stmt->execute([
                    'qty' => $vi['quantity'],
                    'id' => $vi['product_id']
                ]);
            }

            $pdo->commit();

            $order_ref = '#HRT-' . str_pad($order_id, 5, '0', STR_PAD_LEFT);

            send_success([
                'order_id' => $order_id,
                'order_ref' => $order_ref,
                'total_amount' => $order_total,
                'shipping_fee' => $shipping_fee,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ], 'Order placed successfully!');

        } catch (Exception $e) {
            $pdo->rollBack();
            send_error('Order placement failed: ' . $e->getMessage(), 400);
        }
        break;

    case 'list':
        $auth_user = verify_mobile_token();
        $phone = trim($data['phone'] ?? '');

        if (!$auth_user && empty($phone)) {
            send_error('Authentication or phone number required to view orders.', 401);
        }

        try {
            if ($auth_user) {
                $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                                       FROM orders o 
                                       LEFT JOIN order_items oi ON o.id = oi.order_id 
                                       WHERE o.user_id = :uid 
                                       GROUP BY o.id 
                                       ORDER BY o.id DESC LIMIT 50");
                $stmt->execute(['uid' => $auth_user['user_id']]);
            } else {
                $clean_phone = preg_replace('/[^0-9]/', '', $phone);
                $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                                       FROM orders o 
                                       LEFT JOIN order_items oi ON o.id = oi.order_id 
                                       WHERE o.customer_phone LIKE :phone 
                                       GROUP BY o.id 
                                       ORDER BY o.id DESC LIMIT 30");
                $stmt->execute(['phone' => '%' . $clean_phone . '%']);
            }

            $orders = $stmt->fetchAll();
            $formatted = [];

            foreach ($orders as $ord) {
                $formatted[] = [
                    'id' => (int)$ord['id'],
                    'order_ref' => '#HRT-' . str_pad($ord['id'], 5, '0', STR_PAD_LEFT),
                    'total_amount' => (float)$ord['total_amount'],
                    'status' => $ord['status'],
                    'item_count' => (int)$ord['item_count'],
                    'payment_method' => $ord['payment_method'],
                    'created_at' => $ord['created_at']
                ];
            }

            send_success($formatted);
        } catch (PDOException $e) {
            send_error('Failed to load orders: ' . $e->getMessage(), 500);
        }
        break;

    case 'track':
        $order_id = (int)($data['order_id'] ?? ($data['id'] ?? 0));
        if ($order_id <= 0) {
            send_error('Valid order ID is required.');
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                send_error('Order not found.', 404);
            }

            // Get items
            $item_stmt = $pdo->prepare("SELECT oi.*, p.name, p.weight, p.unit, p.image 
                                        FROM order_items oi 
                                        JOIN products p ON oi.product_id = p.id 
                                        WHERE oi.order_id = :order_id");
            $item_stmt->execute(['order_id' => $order_id]);
            $items = $item_stmt->fetchAll();

            $formatted_items = [];
            foreach ($items as $it) {
                $formatted_items[] = [
                    'product_id' => (int)$it['product_id'],
                    'name' => $it['name'],
                    'weight' => $it['weight'],
                    'unit' => $it['unit'],
                    'price' => (float)$it['price'],
                    'quantity' => (int)$it['quantity'],
                    'line_total' => (float)$it['price'] * (int)$it['quantity'],
                    'image_url' => get_full_asset_url($it['image'])
                ];
            }

            // Rider info if assigned
            $rider_info = null;
            if (!empty($order['rider_id'])) {
                $r_stmt = $pdo->prepare("SELECT u.id, u.name, u.phone, rl.latitude, rl.longitude, rl.heading, rl.updated_at 
                                         FROM users u 
                                         LEFT JOIN rider_locations rl ON u.id = rl.rider_id 
                                         WHERE u.id = :rid LIMIT 1");
                $r_stmt->execute(['rid' => $order['rider_id']]);
                $rider_row = $r_stmt->fetch();

                if ($rider_row) {
                    $rider_info = [
                        'name' => $rider_row['name'],
                        'phone' => $rider_row['phone'],
                        'current_lat' => !empty($rider_row['latitude']) ? (float)$rider_row['latitude'] : null,
                        'current_lng' => !empty($rider_row['longitude']) ? (float)$rider_row['longitude'] : null,
                        'location_updated_at' => $rider_row['updated_at']
                    ];
                }
            }

            // Status stage mapping for progress bar
            $status_steps = [
                'pending' => ['step' => 1, 'label' => 'Order Received', 'description' => 'Waiting for store confirmation'],
                'packaging' => ['step' => 2, 'label' => 'Packaging Groceries', 'description' => 'Order is being packed at store'],
                'out_for_delivery' => ['step' => 3, 'label' => 'Out for Delivery', 'description' => 'Rider is on the way to your doorstep'],
                'delivered' => ['step' => 4, 'label' => 'Delivered', 'description' => 'Order completed. Enjoy your groceries!'],
                'cancelled' => ['step' => 0, 'label' => 'Cancelled', 'description' => 'Order was cancelled']
            ];

            $step_info = $status_steps[$order['status']] ?? ['step' => 1, 'label' => ucfirst($order['status']), 'description' => ''];

            send_success([
                'order_id' => (int)$order['id'],
                'order_ref' => '#HRT-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT),
                'status' => $order['status'],
                'step_info' => $step_info,
                'customer_name' => $order['customer_name'],
                'customer_phone' => $order['customer_phone'],
                'customer_address' => $order['customer_address'],
                'delivery_lat' => !empty($order['latitude']) ? (float)$order['latitude'] : null,
                'delivery_lng' => !empty($order['longitude']) ? (float)$order['longitude'] : null,
                'total_amount' => (float)$order['total_amount'],
                'payment_method' => $order['payment_method'],
                'created_at' => $order['created_at'],
                'assigned_at' => $order['assigned_at'] ?? null,
                'delivered_at' => $order['delivered_at'] ?? null,
                'rider' => $rider_info,
                'items' => $formatted_items
            ]);

        } catch (PDOException $e) {
            send_error('Failed to load tracking info: ' . $e->getMessage(), 500);
        }
        break;

    default:
        send_error('Invalid action requested.');
}
