<?php
header('Content-Type: text/plain');

$dirs = [
    'assets/images/categories' => __DIR__ . '/assets/images/categories',
    'product_uploads/categories' => __DIR__ . '/../product_uploads/categories',
    'assets/images/products' => __DIR__ . '/assets/images/products'
];

foreach ($dirs as $name => $path) {
    echo "Directory: $name ($path)\n";
    echo "Exists: " . (is_dir($path) ? "YES" : "NO") . "\n";
    echo "Writeable: " . (is_writable($path) ? "YES" : "NO") . "\n";
    if (is_dir($path)) {
        echo "Permissions: " . substr(sprintf('%o', fileperms($path)), -4) . "\n";
    }
    echo "---------------------------\n";
}
?>
