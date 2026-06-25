<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== LIVE PRODUCT IMAGES DIRECTORY LIST ===\n\n";

$dir = __DIR__ . '/assets/images/products';
if (is_dir($dir)) {
    $files = scandir($dir);
    echo "Total files found: " . (count($files) - 2) . "\n\n";
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        echo "- $file (" . filesize($path) . " bytes)\n";
    }
} else {
    echo "Directory does not exist!\n";
}

echo "\n=== DATABASE PRODUCT IMAGES ===\n";
require_once __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("SELECT id, name, image FROM products WHERE image IS NOT NULL AND image != ''");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Image Path: {$row['image']}\n";
        $full_path = __DIR__ . '/' . $row['image'];
        if (file_exists($full_path)) {
            echo "  -> File exists on disk (" . filesize($full_path) . " bytes)\n";
        } else {
            echo "  -> File MISSING on disk!\n";
        }
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
?>
