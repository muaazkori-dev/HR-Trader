<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/config/db.php';

try {
    $stmt = $pdo->query("SELECT id, barcode, name, description, price, purchase_price, stock_quantity, weight, unit, category FROM products ORDER BY id ASC");
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo "No products found in the database.\n";
    } else {
        echo "Total Products: " . count($products) . "\n\n";
        foreach ($products as $p) {
            echo "ID: " . $p['id'] . "\n";
            echo "Barcode: " . $p['barcode'] . "\n";
            echo "Name: " . $p['name'] . "\n";
            echo "Description: " . $p['description'] . "\n";
            echo "Selling Price: " . $p['price'] . "\n";
            echo "Purchase Price: " . $p['purchase_price'] . "\n";
            echo "Stock Quantity: " . $p['stock_quantity'] . "\n";
            echo "Weight: " . $p['weight'] . " " . $p['unit'] . "\n";
            echo "Category: " . $p['category'] . "\n";
            echo "----------------------------------------\n";
        }
    }
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>
