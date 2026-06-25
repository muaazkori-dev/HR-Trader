<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config/db.php';

function sanitizePathOutput($str) {
    return str_replace('/home/u622906513/domains/thehrtraders.com', '[ROOT]', $str);
}

echo "=== ALL RECENT PRODUCTS ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, category, image, stock_quantity FROM products ORDER BY id DESC LIMIT 50");
    while ($row = $stmt->fetch()) {
        $img = empty($row['image']) ? "NONE" : sanitizePathOutput($row['image']);
        echo "ID: {$row['id']} | Cat: {$row['category']} | Image: $img | Name: {$row['name']}\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
?>
