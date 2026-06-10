<?php
// HR Traders Storefront Autocomplete Search API
// Returns JSON array matching name or category

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit();
}

try {
    $clean_query = preg_replace('/\s+/', '%', $query);
    $search_term = "%" . $clean_query . "%";
    
    $is_cosmetics = (stripos($query, 'cosmetics') !== false) ? 1 : 0;
    
    $stmt = $pdo->prepare("SELECT id, name, category, price, image 
                           FROM products 
                           WHERE name LIKE :search 
                              OR category LIKE :search 
                              OR (category IN ('shampoo', 'soap', 'toothpaste', 'body_wash', 'deodorant') AND :is_cosmetics = 1)
                           LIMIT 10");
    $stmt->execute(['search' => $search_term, 'is_cosmetics' => $is_cosmetics]);
    $products = $stmt->fetchAll();

    $results = [];
    foreach ($products as $prod) {
        $results[] = [
            'id' => (int)$prod['id'],
            'name' => sanitize($prod['name']),
            'category' => sanitize($prod['category']),
            'price' => (float)$prod['price'],
            'image' => $prod['image'] ? $prod['image'] : 'assets/images/placeholder.svg'
        ];
    }

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed']);
}
exit();
