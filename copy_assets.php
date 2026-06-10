<?php
header('Content-Type: text/plain; charset=utf-8');

$copies = [
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/hr_traders_logo_1781111060749.png' => __DIR__ . '/assets/images/logo.png',
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/hr_traders_favicon_1781111078204.png' => __DIR__ . '/assets/images/favicon.png',
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/anaj_category_1781111095610.png' => __DIR__ . '/assets/images/categories/anaj.png',
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/ice_cream_category_1781111112679.png' => __DIR__ . '/assets/images/categories/ice_cream.png',
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/beverages_category_1781111130213.png' => __DIR__ . '/assets/images/categories/cold_drinks.png',
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/milk_category_1781111150329.png' => __DIR__ . '/assets/images/categories/milk.png',
    'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/cosmetics_category_1781111169166.png' => __DIR__ . '/assets/images/categories/cosmetics.png'
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
