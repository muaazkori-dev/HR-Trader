<?php
// HR Traders Online Cart AJAX API Endpoint
// Handles cart state inside $_SESSION['cart'] and returns product details

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input if sent as JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
    $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
} else {
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
}

switch ($action) {
    case 'add':
        if ($product_id <= 0) {
            $response['message'] = 'Invalid Product ID';
            break;
        }

        // Check if product exists in database and has stock
        $stmt = $pdo->prepare("SELECT id, name, stock_quantity, price FROM products WHERE id = :id");
        $stmt->execute(['id' => $product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $response['message'] = 'Product not found';
            break;
        }

        $current_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
        $new_qty = $current_qty + $quantity;

        if ($product['stock_quantity'] < $new_qty) {
            $response['message'] = "Cannot add more. Only " . $product['stock_quantity'] . " items left in stock.";
            break;
        }

        $_SESSION['cart'][$product_id] = $new_qty;
        $response['success'] = true;
        $response['message'] = "'{$product['name']}' added to cart.";
        $response['cart_count'] = array_sum($_SESSION['cart']);
        break;

    case 'update':
        if ($product_id <= 0) {
            $response['message'] = 'Invalid Product ID';
            break;
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            $response['success'] = true;
            $response['message'] = "Item removed from cart.";
        } else {
            // Check stock
            $stmt = $pdo->prepare("SELECT name, stock_quantity FROM products WHERE id = :id");
            $stmt->execute(['id' => $product_id]);
            $product = $stmt->fetch();

            if ($product && $product['stock_quantity'] < $quantity) {
                $_SESSION['cart'][$product_id] = $product['stock_quantity']; // cap to max
                $response['message'] = "Adjusted quantity to max available stock (" . $product['stock_quantity'] . ")";
                $response['success'] = true;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
                $response['success'] = true;
                $response['message'] = "Cart updated.";
            }
        }
        $response['cart_count'] = array_sum($_SESSION['cart']);
        break;

    case 'remove':
        if ($product_id > 0) {
            unset($_SESSION['cart'][$product_id]);
            $response['success'] = true;
            $response['message'] = "Product removed from cart.";
        }
        $response['cart_count'] = array_sum($_SESSION['cart']);
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        $response['success'] = true;
        $response['message'] = "Cart cleared successfully.";
        $response['cart_count'] = 0;
        break;

    case 'get':
        $items = [];
        $subtotal = 0;

        if (!empty($_SESSION['cart'])) {
            // Retrieve all products details currently in the cart
            $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
            $stmt = $pdo->prepare("SELECT id, name, price, weight, category, image, stock_quantity FROM products WHERE id IN ($placeholders)");
            $stmt->execute(array_keys($_SESSION['cart']));
            $products = $stmt->fetchAll();

            foreach ($products as $prod) {
                $p_id = $prod['id'];
                $qty = $_SESSION['cart'][$p_id];
                
                // If stock was updated and is now less than cart quantity, adjust it
                if ($prod['stock_quantity'] < $qty) {
                    $qty = $prod['stock_quantity'];
                    if ($qty <= 0) {
                        unset($_SESSION['cart'][$p_id]);
                        continue;
                    } else {
                        $_SESSION['cart'][$p_id] = $qty;
                    }
                }

                $item_total = $prod['price'] * $qty;
                $subtotal += $item_total;

                $items[] = [
                    'id' => $prod['id'],
                    'name' => $prod['name'],
                    'price' => (float)$prod['price'],
                    'weight' => $prod['weight'],
                    'category' => $prod['category'],
                    'image_url' => $prod['image'] ? BASE_URL . $prod['image'] : BASE_URL . 'assets/images/placeholder.jpg',
                    'qty' => $qty,
                    'item_total' => $item_total
                ];
            }
        }

        $response['success'] = true;
        $response['message'] = "Cart loaded.";
        $response['items'] = $items;
        $response['subtotal'] = $subtotal;
        $response['cart_count'] = array_sum($_SESSION['cart']);
        break;
}

echo json_encode($response);
exit();
