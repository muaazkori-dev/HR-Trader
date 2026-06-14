<?php
// Diagnostics for HR Traders
require_once __DIR__ . '/config/db.php';

header('Content-Type: text/plain');

echo "=== DB SETTINGS ===\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "BASE_URL: " . BASE_URL . "\n\n";

echo "=== PRODUCTS ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, category, image FROM products ORDER BY id DESC LIMIT 15");
    while ($row = $stmt->fetch()) {
        $img_file = __DIR__ . '/' . $row['image'];
        $img_exists = (!empty($row['image']) && file_exists($img_file)) ? "YES" : "NO";
        echo "ID: {$row['id']} | Name: {$row['name']} | Cat: {$row['category']} | Image: " . ($row['image'] ?? 'NULL') . " | File Exists: {$img_exists}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== DIRECTORIES ===\n";
$dirs = [
    'assets/images/',
    'assets/images/categories/',
    'assets/images/products/'
];

foreach ($dirs as $d) {
    $path = __DIR__ . '/' . $d;
    echo "Dir: {$d}\n";
    echo "  Exists: " . (is_dir($path) ? "YES" : "NO") . "\n";
    echo "  Writable: " . (is_writable($path) ? "YES" : "NO") . "\n";
    if (is_dir($path)) {
        $files = array_diff(scandir($path), ['.', '..']);
        echo "  Files Count: " . count($files) . "\n";
        echo "  Sample Files: " . implode(', ', array_slice($files, 0, 10)) . "\n";
    }
    echo "\n";
}
