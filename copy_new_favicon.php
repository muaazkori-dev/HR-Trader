<?php
header('Content-Type: text/plain; charset=utf-8');

$src = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/media__1783628994946.png';

$destinations = [
    __DIR__ . '/next-store/src/app/icon.png',
    __DIR__ . '/next-store/src/app/favicon.ico',
    __DIR__ . '/next-store/public/favicon.ico',
    __DIR__ . '/next-store/public/assets/images/favicon.png'
];

echo "Copying new logo/favicon to all paths:\n\n";

if (!file_exists($src)) {
    echo "ERROR: Source file does not exist!\n";
    exit;
}

foreach ($destinations as $dest) {
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    if (copy($src, $dest)) {
        echo "Successfully copied to: $dest\n";
    } else {
        echo "FAILED to copy to: $dest\n";
    }
}
