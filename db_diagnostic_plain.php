<?php
// HR Traders - Plain-text Database Diagnostic Tool
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== DATABASE DIAGNOSTIC ===\n";
echo "Host: " . DB_HOST . "\n";
echo "DB Name: " . DB_NAME . "\n";

try {
    $stmt = $pdo->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category");
    $rows = $stmt->fetchAll();
    echo "\nCATEGORIES & PRODUCT COUNTS IN DATABASE:\n";
    foreach ($rows as $row) {
        $cat = $row['category'];
        $count = $row['cnt'];
        echo "- '" . $cat . "' (Length: " . strlen($cat) . "): " . $count . "\n";
    }
} catch (Exception $e) {
    echo "ERROR FETCHING CATEGORIES: " . $e->getMessage() . "\n";
}

try {
    echo "\nSAMPLE PRODUCTS:\n";
    $stmt = $pdo->query("SELECT id, name, category FROM products LIMIT 30");
    $products = $stmt->fetchAll();
    foreach ($products as $p) {
        echo "- ID " . $p['id'] . ": '" . $p['name'] . "' -> category: '" . $p['category'] . "' (Length: " . strlen($p['category']) . ")\n";
    }
} catch (Exception $e) {
    echo "ERROR FETCHING PRODUCTS: " . $e->getMessage() . "\n";
}
?>
