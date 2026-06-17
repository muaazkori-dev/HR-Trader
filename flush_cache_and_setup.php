<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== CACHE FLUSHING ===\n";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "OPCache successfully reset!\n";
    } else {
        echo "OPCache reset failed.\n";
    }
} else {
    echo "OPCache function not available.\n";
}

if (function_exists('header')) {
    header('X-LiteSpeed-Purge: *');
    echo "LiteSpeed Cache purge header sent!\n";
}

echo "\n=== PRODUCT UPLOADS DIRECTORY CREATION ===\n";
$products_dir = __DIR__ . '/assets/images/products';
if (!is_dir($products_dir)) {
    $created = @mkdir($products_dir, 0755, true);
    if ($created) {
        echo "Successfully created: assets/images/products\n";
        @chmod($products_dir, 0755);
    } else {
        echo "Failed to create directory: assets/images/products\n";
    }
} else {
    echo "Directory already exists: assets/images/products\n";
    @chmod($products_dir, 0755);
}

$writable = is_writable($products_dir) ? "YES" : "NO";
$perms = substr(sprintf('%o', fileperms($products_dir)), -4);
echo "Directory perms: {$perms} | Writable: {$writable}\n";
?>
