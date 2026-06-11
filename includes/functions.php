<?php
// HR Traders Business Logic & Database Helpers
// Contains operations for stock tracking, POS checkout, and online orders sync

require_once __DIR__ . '/../config/db.php';

// Self-healing database check for sale_items table columns (ensures 'price' and 'purchase_price' exist)
try {
    $q = $pdo->query("SHOW COLUMNS FROM sale_items LIKE 'price'");
    if (!$q->fetch()) {
        $pdo->exec("ALTER TABLE sale_items ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }
} catch (PDOException $e) {
    // Ignore
}

// Self-healing database check for product_demands table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_demands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(50) NOT NULL,
        demand_details TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Ignore
}

// Self-healing database check for users table (Google Sign-In integration)
try {
    $q = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'");
    if (!$q->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(100) DEFAULT NULL UNIQUE AFTER password");
    }
    
    $q2 = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
    if (!$q2->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) DEFAULT NULL UNIQUE AFTER google_id");
    }
    
    $pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {
    // Ignore
}

try {
    $q = $pdo->query("SHOW COLUMNS FROM sale_items LIKE 'purchase_price'");
    if (!$q->fetch()) {
        $pdo->exec("ALTER TABLE sale_items ADD COLUMN purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }
} catch (PDOException $e) {
    // Ignore
}


/**
 * Sanitize output strings
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Safe currency format helper
 * @param float $amount
 * @return string
 */
function format_price($amount) {
    return CURRENCY . ' ' . number_format($amount, 2);
}

/**
 * Adjust stock quantity for a product
 * @param PDO $pdo
 * @param int $product_id
 * @param int $quantity Change quantity (negative to decrement, positive to increment)
 * @return bool
 */
function adjust_stock($pdo, $product_id, $quantity) {
    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id");
    return $stmt->execute(['qty' => $quantity, 'id' => $product_id]);
}

/**
 * Verify if product has sufficient stock
 * @param PDO $pdo
 * @param int $product_id
 * @param int $required_qty
 * @return bool
 */
function has_sufficient_stock($pdo, $product_id, $required_qty) {
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch();
    return $product && $product['stock_quantity'] >= $required_qty;
}

/**
 * Records an instant POS sale transaction in the database
 * Decrements stock and inserts details inside a single database transaction.
 * @param PDO $pdo
 * @param int $cashier_id Logged in staff ID
 * @param array $cart_items Format: [ ['product_id' => X, 'quantity' => Y], ... ]
 * @param string $payment_method 'Cash', 'Card', etc.
 * @return int Created Sale ID
 * @throws Exception
 */
function process_pos_sale($pdo, $cashier_id, $cart_items, $payment_method = 'Cash') {
    if (empty($cart_items)) {
        throw new Exception("Cannot process an empty sale.");
    }

    $pdo->beginTransaction();

    try {
        $total_amount = 0;
        $total_profit = 0;
        $items_to_insert = [];

        // 1. Validate items, prices, stock, and calculate totals
        foreach ($cart_items as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (int)$item['quantity'];

            if ($quantity <= 0) {
                throw new Exception("Invalid item quantity.");
            }

            // Fetch current product details securely
            $stmt = $pdo->prepare("SELECT id, name, price, purchase_price, stock_quantity FROM products WHERE id = :id");
            $stmt->execute(['id' => $product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("Product not found (ID: $product_id).");
            }

            if ($product['stock_quantity'] < $quantity) {
                throw new Exception("Insufficient stock for '" . $product['name'] . "'. Available: " . $product['stock_quantity']);
            }

            $item_price = $product['price'];
            $item_purchase_price = $product['purchase_price'];
            
            $item_total = $item_price * $quantity;
            $item_profit = ($item_price - $item_purchase_price) * $quantity;

            $total_amount += $item_total;
            $total_profit += $item_profit;

            $items_to_insert[] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $item_price,
                'purchase_price' => $item_purchase_price
            ];
        }

        // 2. Insert into sales table
        $stmt_sale = $pdo->prepare("INSERT INTO sales (transaction_type, order_id, total_amount, total_profit, payment_method, cashier_id) 
                                    VALUES ('POS', NULL, :total_amount, :total_profit, :payment_method, :cashier_id)");
        $stmt_sale->execute([
            'total_amount' => $total_amount,
            'total_profit' => $total_profit,
            'payment_method' => $payment_method,
            'cashier_id' => $cashier_id
        ]);
        $sale_id = $pdo->lastInsertId();

        // 3. Insert sale items and decrement stock
        $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, purchase_price) 
                                     VALUES (:sale_id, :product_id, :quantity, :price, :purchase_price)");
        
        foreach ($items_to_insert as $insert_item) {
            $stmt_item->execute([
                'sale_id' => $sale_id,
                'product_id' => $insert_item['product_id'],
                'quantity' => $insert_item['quantity'],
                'price' => $insert_item['price'],
                'purchase_price' => $insert_item['purchase_price']
            ]);

            // Decrement product inventory stock
            adjust_stock($pdo, $insert_item['product_id'], -$insert_item['quantity']);
        }

        $pdo->commit();
        return $sale_id;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handles online order delivery fulfillment.
 * Marks the order as 'delivered' and registers it in the sales registry.
 * @param PDO $pdo
 * @param int $order_id
 * @param int $cashier_id User ID of manager/staff operating dashboard
 * @return bool
 * @throws Exception
 */
function deliver_online_order($pdo, $order_id, $cashier_id) {
    $pdo->beginTransaction();

    try {
        // Fetch order details
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $order_id]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception("Order not found.");
        }

        if ($order['status'] === 'delivered') {
            $pdo->commit();
            return true; // Already processed
        }

        if ($order['status'] === 'cancelled') {
            throw new Exception("Cannot fulfill a cancelled order.");
        }

        // Fetch order items to calculate cost and net profit
        $stmt_items = $pdo->prepare("SELECT oi.*, p.purchase_price 
                                     FROM order_items oi 
                                     JOIN products p ON oi.product_id = p.id 
                                     WHERE oi.order_id = :order_id");
        $stmt_items->execute(['order_id' => $order_id]);
        $order_items = $stmt_items->fetchAll();

        $total_profit = 0;
        foreach ($order_items as $item) {
            // Profit = (selling_price - purchase_price) * quantity
            $total_profit += ($item['price'] - $item['purchase_price']) * $item['quantity'];
        }

        // 1. Update order status to 'delivered'
        $stmt_update = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE id = :id");
        $stmt_update->execute(['id' => $order_id]);

        // 2. Insert record into consolidated sales registry
        $stmt_sales = $pdo->prepare("INSERT INTO sales (transaction_type, order_id, total_amount, total_profit, payment_method, cashier_id) 
                                     VALUES ('Online', :order_id, :total_amount, :total_profit, :payment_method, :cashier_id)");
        $stmt_sales->execute([
            'order_id' => $order_id,
            'total_amount' => $order['total_amount'],
            'total_profit' => $total_profit,
            'payment_method' => $order['payment_method'],
            'cashier_id' => $cashier_id
        ]);
        $sale_id = $pdo->lastInsertId();

        // 3. Copy order items into sale_items table
        $stmt_sale_items = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, purchase_price) 
                                          VALUES (:sale_id, :product_id, :quantity, :price, :purchase_price)");
        
        foreach ($order_items as $item) {
            $stmt_sale_items->execute([
                'sale_id' => $sale_id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'purchase_price' => $item['purchase_price']
            ]);
        }

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Reverts an online order. Marks as 'cancelled' and returns stock to the inventory.
 * @param PDO $pdo
 * @param int $order_id
 * @return bool
 * @throws Exception
 */
function cancel_online_order($pdo, $order_id) {
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $order_id]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception("Order not found.");
        }

        if ($order['status'] === 'cancelled') {
            $pdo->commit();
            return true; // Already cancelled
        }

        if ($order['status'] === 'delivered') {
            throw new Exception("Cannot cancel an already delivered and closed order.");
        }

        // 1. Update order status to 'cancelled'
        $stmt_update = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :id");
        $stmt_update->execute(['id' => $order_id]);

        // 2. Fetch order items to return stock
        $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id");
        $stmt_items->execute(['order_id' => $order_id]);
        $order_items = $stmt_items->fetchAll();

        foreach ($order_items as $item) {
            // Return items back to stock (positive addition)
            adjust_stock($pdo, $item['product_id'], $item['quantity']);
        }

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
