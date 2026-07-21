<?php
header('Content-Type: text/plain');

$backup_dir = __DIR__ . '/product_uploads/';
$products_dir = __DIR__ . '/assets/images/products/';

echo "Checking Hostinger server directories:\n";
echo "Backup Directory: $backup_dir\n";
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    echo "Found " . (count($files) - 2) . " files in backup folder:\n";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo " - $file (" . filesize($backup_dir . $file) . " bytes)\n";
        }
    }
} else {
    echo "Backup directory does not exist on Hostinger server!\n";
}

echo "\nProducts Directory: $products_dir\n";
if (is_dir($products_dir)) {
    $files = scandir($products_dir);
    echo "Found " . (count($files) - 2) . " files in products folder:\n";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo " - $file (" . filesize($products_dir . $file) . " bytes)\n";
        }
    }
} else {
    echo "Products directory does not exist on Hostinger server!\n";
}
