<?php
// HR Traders Owner / Store Manager Mobile REST API Endpoint (v1)
// Dashboard metrics, live order management, rider dispatch, and fast stock toggle

require_once __DIR__ . '/helpers.php';

$auth = require_auth('owner');
$data = get_request_data();
$action = $data['action'] ?? ($_GET['action'] ?? 'dashboard');

switch ($action) {
    case 'dashboard':
        try {
            // 1. Today sales & orders
            $today_stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_orders_today,
                    COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as today_sales,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_orders,
                    COALESCE(SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END), 0) as on_the_way
                FROM orders 
                WHERE DATE(created_at) = CURDATE()
            ");
            $metrics = $today_stmt->fetch();

            // 2. Low stock items (stock <= 5)
            $low_stmt = $pdo->query("
                SELECT id, name, category, stock_quantity, price, image 
                FROM products 
                WHERE stock_quantity <= 5 
                ORDER BY stock_quantity ASC LIMIT 10
            ");
            $low_stock = $low_stmt->fetchAll();
            foreach ($low_stock as &$ls) {
                $ls['image_url'] = get_full_asset_url($ls['image']);
            }

            // 3. Recent 5 orders
            $rec_stmt = $pdo->query("
                SELECT id, customer_name, customer_phone, total_amount, status, created_at 
                FROM orders 
                ORDER BY id DESC LIMIT 5
            ");
            $recent_orders = $rec_stmt->fetchAll();
            foreach ($recent_orders as &$ro) {
                $ro['order_ref'] = '#HRT-' . str_pad($ro['id'], 5, '0', STR_PAD_LEFT);
                $ro['total_amount'] = (float)$ro['total_amount'];
            }

            send_success([
                'today_sales' => (float)$metrics['today_sales'],
                'total_orders_today' => (int)$metrics['total_orders_today'],
                'pending_orders' => (int)$metrics['pending_orders'],
                'on_the_way' => (int)$metrics['on_the_way'],
                'low_stock_items' => $low_stock,
                'recent_orders' => $recent_orders
            ]);
        } catch (PDOException $e) {
            send_error('Failed to load owner dashboard: ' . $e->getMessage(), 500);
        }
        break;

    case 'orders':
        $status_filter = trim($data['status'] ?? 'all');
        $search = trim($data['search'] ?? '');
        $page = max(1, (int)($data['page'] ?? 1));
        $limit = min(50, max(5, (int)($data['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($status_filter) && $status_filter !== 'all') {
            $where[] = "o.status = :status";
            $params['status'] = $status_filter;
        }

        if (!empty($search)) {
            $clean_id = preg_replace('/[^0-9]/', '', $search);
            if (!empty($clean_id)) {
                $where[] = "(o.id = :oid OR o.customer_phone LIKE :search OR o.customer_name LIKE :search)";
                $params['oid'] = (int)$clean_id;
            } else {
                $where[] = "(o.customer_name LIKE :search OR o.customer_phone LIKE :search)";
            }
            $params['search'] = '%' . $search . '%';
        }

        $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        try {
            $stmt = $pdo->prepare("
                SELECT o.*, u.name as rider_name 
                FROM orders o 
                LEFT JOIN users u ON o.rider_id = u.id 
                {$where_sql} 
                ORDER BY o.id DESC 
                LIMIT :limit OFFSET :offset
            ");
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $orders = $stmt->fetchAll();

            $formatted = [];
            foreach ($orders as $ord) {
                $formatted[] = [
                    'id' => (int)$ord['id'],
                    'order_ref' => '#HRT-' . str_pad($ord['id'], 5, '0', STR_PAD_LEFT),
                    'customer_name' => $ord['customer_name'],
                    'customer_phone' => $ord['customer_phone'],
                    'customer_address' => $ord['customer_address'],
                    'total_amount' => (float)$ord['total_amount'],
                    'status' => $ord['status'],
                    'payment_method' => $ord['payment_method'],
                    'rider_id' => $ord['rider_id'] ? (int)$ord['rider_id'] : null,
                    'rider_name' => $ord['rider_name'] ?? 'Unassigned',
                    'created_at' => $ord['created_at']
                ];
            }

            send_success($formatted);
        } catch (PDOException $e) {
            send_error('Failed to load orders: ' . $e->getMessage(), 500);
        }
        break;

    case 'riders_list':
        try {
            // Fetch all users with role 'rider'
            $stmt = $pdo->query("
                SELECT u.id, u.name, u.phone, 
                       COALESCE(act.active_count, 0) as active_orders_count 
                FROM users u 
                LEFT JOIN (
                    SELECT rider_id, COUNT(*) as active_count 
                    FROM orders 
                    WHERE status IN ('packaging', 'out_for_delivery') 
                    GROUP BY rider_id
                ) act ON u.id = act.rider_id 
                WHERE u.role = 'rider' 
                ORDER BY active_orders_count ASC, u.name ASC
            ");
            $riders = $stmt->fetchAll();

            send_success($riders);
        } catch (PDOException $e) {
            send_error('Failed to load riders: ' . $e->getMessage(), 500);
        }
        break;

    case 'assign_rider':
        $order_id = (int)($data['order_id'] ?? 0);
        $rider_id = (int)($data['rider_id'] ?? 0);

        if ($order_id <= 0 || $rider_id <= 0) {
            send_error('Valid Order ID and Rider ID are required.');
        }

        try {
            // Verify rider exists
            $r_stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = :id AND role = 'rider' LIMIT 1");
            $r_stmt->execute(['id' => $rider_id]);
            $rider = $r_stmt->fetch();

            if (!$rider) {
                send_error('Rider not found.', 404);
            }

            // Assign rider and advance status to packaging
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET rider_id = :rid, 
                    status = CASE WHEN status = 'pending' THEN 'packaging' ELSE status END, 
                    assigned_at = NOW() 
                WHERE id = :oid
            ");
            $stmt->execute(['rid' => $rider_id, 'oid' => $order_id]);

            send_success([
                'order_id' => $order_id,
                'rider_id' => $rider_id,
                'rider_name' => $rider['name']
            ], "Order assigned to {$rider['name']} successfully.");
        } catch (PDOException $e) {
            send_error('Failed to assign rider: ' . $e->getMessage(), 500);
        }
        break;

    case 'toggle_stock':
        $product_id = (int)($data['product_id'] ?? 0);
        $in_stock = isset($data['in_stock']) ? (bool)$data['in_stock'] : true;
        $custom_qty = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : null;

        if ($product_id <= 0) {
            send_error('Valid product ID is required.');
        }

        try {
            $new_qty = $custom_qty !== null ? $custom_qty : ($in_stock ? 50 : 0);

            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = :qty WHERE id = :id");
            $stmt->execute(['qty' => $new_qty, 'id' => $product_id]);

            send_success([
                'product_id' => $product_id,
                'stock_quantity' => $new_qty,
                'in_stock' => $new_qty > 0
            ], "Product stock updated to {$new_qty}.");
        } catch (PDOException $e) {
            send_error('Failed to toggle stock: ' . $e->getMessage(), 500);
        }
        break;

    default:
        send_error('Invalid owner action requested.');
}
