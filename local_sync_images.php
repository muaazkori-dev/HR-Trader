<?php
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

$files = [
    'anaj.png',
    'bakery.png',
    'cold_drinks.png',
    'cosmetics.png',
    'grocery.png',
    'ice_cream.jpg',
    'ice_cream.png',
    'milk.jpg',
    'milk.png',
    'sauce.png',
    'snacks.png',
    'water.png'
];

$src_url_base = 'https://thehrtraders.com/assets/images/categories/';
$dest_dir = __DIR__ . '/assets/images/categories/';

if (!is_dir($dest_dir)) {
    die("Destination directory not found: $dest_dir");
}

echo "=== Syncing Optimized Images from Server ===\n";

foreach ($files as $filename) {
    $url = $src_url_base . $filename . '?v=' . time();
    $dest = $dest_dir . $filename;
    
    echo "Downloading $filename... ";
    
    $data = @file_get_contents($url);
    if ($data === false) {
        echo "FAILED to download from $url\n";
        continue;
    }
    
    $size = strlen($data);
    if ($size < 100) {
        echo "FAILED: file too small ($size bytes). Check url content.\n";
        continue;
    }
    
    if (file_put_contents($dest, $data) !== false) {
        clearstatcache(true, $dest);
        echo "SUCCESS! Saved local size: " . number_format(filesize($dest) / 1024, 1) . " KB\n";
    } else {
        echo "FAILED to write to $dest\n";
    }
}

echo "\nSyncing complete!\n";
?>
