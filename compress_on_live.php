<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== LIVE IMAGE COMPRESSION & CONVERSION ===\n";

if (!extension_loaded('gd')) {
    die("Error: GD extension is not loaded on this server.\n");
}

$targets = [
    [
        'src' => __DIR__ . '/assets/images/logo.png',
        'dest' => __DIR__ . '/assets/images/logo.png',
        'width' => 256,
        'height' => 256,
        'name' => 'Logo'
    ],
    [
        'src' => __DIR__ . '/assets/images/favicon.png',
        'dest' => __DIR__ . '/assets/images/favicon.png',
        'width' => 48,
        'height' => 48,
        'name' => 'Favicon'
    ]
];

// Add all category icons
$categories = ['anaj', 'bakery', 'cold_drinks', 'cosmetics', 'ice_cream', 'milk', 'sauce', 'snacks', 'grocery'];
foreach ($categories as $cat) {
    $targets[] = [
        'src' => __DIR__ . "/assets/images/categories/{$cat}.png",
        'dest' => __DIR__ . "/assets/images/categories/{$cat}.png",
        'width' => 120,
        'height' => 120,
        'name' => ucfirst($cat) . " Category Icon"
    ];
}

foreach ($targets as $t) {
    echo "Processing {$t['name']}...\n";
    if (!file_exists($t['src'])) {
        echo "Source file does not exist: {$t['src']}\n";
        continue;
    }

    // Attempt to load as JPEG first
    $srcImg = @imagecreatefromjpeg($t['src']);
    if (!$srcImg) {
        // Fallback to PNG loader
        $srcImg = @imagecreatefrompng($t['src']);
    }

    if (!$srcImg) {
        echo "Failed to load image: {$t['src']}\n";
        continue;
    }

    $origW = imagesx($srcImg);
    $origH = imagesy($srcImg);
    echo "Original Dimensions: {$origW}x{$origH}\n";

    // Create new blank image with target dimensions
    $destImg = imagecreatetruecolor($t['width'], $t['height']);
    
    // Enable transparency settings for true PNG
    imagealphablending($destImg, false);
    imagesavealpha($destImg, true);

    // Resample the image
    imagecopyresampled(
        $destImg,
        $srcImg,
        0, 0, 0, 0,
        $t['width'], $t['height'],
        $origW, $origH
    );

    // Temp save to prevent corruption
    $tempDest = $t['dest'] . '.tmp';
    $saved = imagepng($destImg, $tempDest, 9); // Maximum compression
    
    if ($saved && file_exists($tempDest)) {
        if (@rename($tempDest, $t['dest'])) {
            echo "Successfully converted and compressed {$t['name']}.\n";
            echo "New size: " . filesize($t['dest']) . " bytes.\n";
        } else {
            echo "Failed to rename temp file to {$t['dest']}\n";
            @unlink($tempDest);
        }
    } else {
        echo "Failed to save PNG image to {$tempDest}\n";
    }

    imagedestroy($srcImg);
    imagedestroy($destImg);
    echo "----------------------------------------\n";
}

if (function_exists('opcache_reset')) {
    opcache_reset();
}
echo "Conversion Completed.\n";
?>
