<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config/db.php';

echo "=== DIAGNOSTICS ===\n";
echo "Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current File: " . __FILE__ . "\n";
echo "BASE_URL: " . BASE_URL . "\n\n";

echo "=== DB PRODUCTS ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, category, image FROM products ORDER BY id DESC LIMIT 20");
    $products = $stmt->fetchAll();
    foreach ($products as $p) {
        $db_image = $p['image'];
        $full_path = __DIR__ . '/' . $db_image;
        $exists = (!empty($db_image) && file_exists($full_path)) ? "YES" : "NO";
        echo "ID: {$p['id']} | Name: {$p['name']} | Category: {$p['category']} | Image Path: {$db_image} | Exists on Server: {$exists}\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== CATEGORY IMAGES ===\n";
foreach ($CATEGORIES as $key => $cat) {
    if (isset($cat['parent'])) continue;
    $img_name = $key === 'beverages' ? 'cold_drinks.png' : ($key === 'sauce' ? 'sauce.png' : $key . '.png');
    $img_path = 'assets/images/categories/' . $img_name;
    $full_path = __DIR__ . '/' . $img_path;
    $exists = file_exists($full_path) ? "YES" : "NO";
    echo "Category: {$key} | Expected Image: {$img_path} | Exists on Server: {$exists}\n";
}
echo "\n";

echo "=== PRODUCTS DIRECTORY ===\n";
$prod_dir = __DIR__ . '/assets/images/products';
if (is_dir($prod_dir)) {
    echo "Dir exists. Files:\n";
    $files = scandir($prod_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        echo "  - {$file} (" . filesize($prod_dir . '/' . $file) . " bytes)\n";
    }
} else {
    echo "Dir does not exist!\n";
}
