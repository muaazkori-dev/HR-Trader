<?php
// Database & Filesystem Diagnostics for HR Traders
require_once __DIR__ . '/config/db.php';

echo "=== DATABASE CONFIG ===\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "BASE_URL: " . BASE_URL . "\n\n";

echo "=== PRODUCTS IN DB ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, category, image FROM products ORDER BY id DESC LIMIT 15");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Cat: {$row['category']} | Image: " . ($row['image'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== FILESYSTEM CHECK ===\n";
$dirs = [
    'assets/images/',
    'assets/images/categories/',
    'assets/images/products/'
];

foreach ($dirs as $d) {
    $path = __DIR__ . '/' . $d;
    echo "Path: {$d} -> " . (is_dir($path) ? "EXISTS" : "MISSING") . " | Writable: " . (is_writable($path) ? "YES" : "NO") . "\n";
    if (is_dir($path)) {
        $files = scandir($path);
        echo "Files: " . implode(', ', array_diff($files, ['.', '..'])) . "\n";
    }
    echo "\n";
}
