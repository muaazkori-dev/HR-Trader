<?php
// HR Traders POS AJAX Checkout Endpoint
// Handles POS cart submission, decrements stock, records sale, and returns invoice ID

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Restrict to owner & manager
if (!is_logged_in() || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['items'])) {
    echo json_encode(['success' => false, 'message' => 'Billing cart is empty']);
    exit();
}

$cashier_id = $_SESSION['user_id'];
$items = $input['items'];
$payment_method = sanitize($input['payment_method'] ?? 'Cash');
$discount_percent = isset($input['discount']) ? (float)$input['discount'] : 0;

$pdo->beginTransaction();

try {
    $total_amount = 0;
    $total_profit = 0;
    $sale_items_to_insert = [];

    // 1. Process items and calculate values
    foreach ($items as $item) {
        $product_id = (int)$item['product_id'];
        $qty = (int)$item['quantity'];

        if ($qty <= 0) {
            throw new Exception("Invalid quantity for product ID: $product_id");
        }

        // Fetch live product details
        $stmt = $pdo->prepare("SELECT id, name, price, purchase_price, stock_quantity FROM products WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception("Product ID $product_id not found in registry.");
        }

        if ($product['stock_quantity'] < $qty) {
            throw new Exception("Stock low for '{$product['name']}'. Available: {$product['stock_quantity']}, Requested: $qty");
        }

        $selling_price = $product['price'];
        $purchase_price = $product['purchase_price'];

        $item_total = $selling_price * $qty;
        // profit = (selling - purchase) * qty
        $item_profit = ($selling_price - $purchase_price) * $qty;

        $total_amount += $item_total;
        $total_profit += $item_profit;

        $sale_items_to_insert[] = [
            'product_id' => $product_id,
            'quantity' => $qty,
            'price' => $selling_price,
            'purchase_price' => $purchase_price
        ];
    }

    // 2. Adjust for discount
    if ($discount_percent > 0) {
        $discount_amount = ($total_amount * ($discount_percent / 100));
        $total_amount -= $discount_amount;
        
        // Adjust profit proportionally to discount
        $total_profit -= $discount_amount;
    }

    // 3. Log sale transaction
    $stmt_sale = $pdo->prepare("INSERT INTO sales (transaction_type, order_id, total_amount, total_profit, payment_method, cashier_id) 
                                VALUES ('POS', NULL, :total, :profit, :method, :cashier)");
    $stmt_sale->execute([
        'total' => $total_amount,
        'profit' => $total_profit,
        'method' => $payment_method,
        'cashier' => $cashier_id
    ]);
    $sale_id = $pdo->lastInsertId();

    // 4. Log individual sale items and decrement stock
    $stmt_sale_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, purchase_price) 
                                     VALUES (:sale_id, :product_id, :quantity, :price, :purchase_price)");
    
    foreach ($sale_items_to_insert as $insert_item) {
        $stmt_sale_item->execute([
            'sale_id' => $sale_id,
            'product_id' => $insert_item['product_id'],
            'quantity' => $insert_item['quantity'],
            'price' => $insert_item['price'],
            'purchase_price' => $insert_item['purchase_price']
        ]);

        // Decrement product inventory
        adjust_stock($pdo, $insert_item['product_id'], -$insert_item['quantity']);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'sale_id' => $sale_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit();
