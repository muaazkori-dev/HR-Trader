<?php
header('Content-Type: text/plain; charset=utf-8');

$src_dir = __DIR__ . '/assets/images/products/';
$dest_dir = __DIR__ . '/next-store/public/assets/images/products/';

if (!is_dir($dest_dir)) {
    mkdir($dest_dir, 0777, true);
}

echo "Copying product images to Next.js directory...\n\n";

$files = scandir($src_dir);
$count = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..' || $file === '.gitkeep') continue;
    
    $src_file = $src_dir . $file;
    $dest_file = $dest_dir . $file;
    
    if (copy($src_file, $dest_file)) {
        echo "Copied: $file\n";
        $count++;
    } else {
        echo "FAILED to copy: $file\n";
    }
}

echo "\nTotal files copied: $count\n";
