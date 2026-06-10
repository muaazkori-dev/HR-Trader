<?php
// HR Traders POS AJAX Search API
// Queries database by exact barcode or partial name for instant POS insertion

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Restrict to owner & manager
if (!is_logged_in() || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode([]);
    exit();
}

try {
    // 1. First attempt to fetch by exact barcode match
    $stmt = $pdo->prepare("SELECT id, barcode, name, price, purchase_price, stock_quantity, weight, category 
                           FROM products 
                           WHERE barcode = :barcode LIMIT 1");
    $stmt->execute(['barcode' => $query]);
    $product = $stmt->fetch();

    if ($product) {
        // Return as single-element array to unify interface format
        echo json_encode([$product]);
        exit();
    }

    // 2. Fallback to name search if barcode is not exact
    $search_term = "%" . $query . "%";
    $stmt = $pdo->prepare("SELECT id, barcode, name, price, purchase_price, stock_quantity, weight, category 
                           FROM products 
                           WHERE name LIKE :name OR barcode LIKE :barcode
                           LIMIT 10");
    $stmt->execute(['name' => $search_term, 'barcode' => $search_term]);
    $products = $stmt->fetchAll();

    echo json_encode($products);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed']);
}
exit();
