<?php
header('Content-Type: text/plain');

$paths = [
    'assets' => __DIR__ . '/assets',
    'assets/images' => __DIR__ . '/assets/images',
    'assets/images/categories' => __DIR__ . '/assets/images/categories',
    'product_uploads' => __DIR__ . '/../product_uploads',
    'product_uploads/categories' => __DIR__ . '/../product_uploads/categories'
];

foreach ($paths as $name => $full) {
    echo "--- $name ---\n";
    echo "Path: $full\n";
    echo "Exists: " . (is_dir($full) ? 'yes' : 'no') . "\n";
    echo "Writeable: " . (is_writable($full) ? 'yes' : 'no') . "\n\n";
}
?>
