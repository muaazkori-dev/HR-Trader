<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config/db.php';

echo "=== DB PRODUCTS IMAGES ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, image, stock_quantity FROM products ORDER BY id DESC LIMIT 30");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Image Path: {$row['image']} | Stock: {$row['stock_quantity']}\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "\n=== DIRECTORY DIAGNOSTICS ===\n";
$target = __DIR__ . '/assets/images/products';
echo "Target Path: $target\n";
if (file_exists($target)) {
    echo "Exists: Yes\n";
    if (is_link($target)) {
        echo "Is Symlink: Yes\n";
        echo "Link Target: " . readlink($target) . "\n";
    } else {
        echo "Is Symlink: No (It is a normal directory)\n";
    }
    
    echo "\n=== LIST FILES IN TARGET ===\n";
    $files = scandir($target);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $target . '/' . $file;
        echo " - $file (" . (is_dir($filePath) ? 'dir' : filesize($filePath) . ' bytes') . ")\n";
    }
} else {
    echo "Exists: No\n";
}

echo "\n=== LIST FILES IN SOURCE (IF ACCESSIBLE) ===\n";
$source = '/home/u622906513/domains/thehrtraders.com/product_uploads';
if (is_dir($source)) {
    echo "Source Exists: Yes\n";
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $source . '/' . $file;
        echo " - $file (" . (is_dir($filePath) ? 'dir' : filesize($filePath) . ' bytes') . ")\n";
    }
} else {
    echo "Source Exists: No or Not Readable\n";
}
?>
