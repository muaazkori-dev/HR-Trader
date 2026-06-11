<?php
header('Content-Type: text/plain; charset=utf-8');

$copies = [
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/snacks_category_icon_1781113888680.png' => __DIR__ . '/assets/images/categories/snacks.png',
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/bakery_category_icon_1781205886278.png' => __DIR__ . '/assets/images/categories/bakery.png',
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/sauce_category_icon_1781205898231.png' => __DIR__ . '/assets/images/categories/sauce.png'
];

echo "=== HR TRADERS ASSET COPY UTILITY ===\n\n";

foreach ($copies as $src => $dest) {
    if (!file_exists($src)) {
        echo "[ERROR] Source file does not exist: $src\n";
        continue;
    }
    
    // Ensure destination directory exists
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    if (copy($src, $dest)) {
        echo "[SUCCESS] Copied:\n  From: $src\n  To:   $dest\n\n";
    } else {
        echo "[ERROR] Failed to copy to $dest\n\n";
    }
}
?>
