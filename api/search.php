<?php
// HR Traders Online Storefront Live AJAX Search API
// Queries products and returns a JSON list for search recommendations

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    // Perform partial match on product name, barcode, or category
    $search_term = "%" . $query . "%";
    $stmt = $pdo->prepare("SELECT id, barcode, name, price, weight, category, stock_quantity, image 
                           FROM products 
                           WHERE name LIKE :q OR barcode LIKE :q OR category LIKE :q
                           LIMIT 8");
    $stmt->execute(['q' => $search_term]);
    $products = $stmt->fetchAll();

    $results = [];
    foreach ($products as $prod) {
        $results[] = [
            'id' => $prod['id'],
            'barcode' => $prod['barcode'],
            'name' => sanitize($prod['name']),
            'price' => (float)$prod['price'],
            'weight' => sanitize($prod['weight']),
            'category' => sanitize($prod['category']),
            'stock_quantity' => (int)$prod['stock_quantity'],
            'image_url' => get_product_image_url($prod['image'] ?? '')
        ];
    }

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed']);
}
exit();
