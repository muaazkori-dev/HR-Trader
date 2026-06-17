<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== IMAGE COMPRESSION & CONVERSION ===\n";

if (!extension_loaded('gd')) {
    die("Error: GD extension is not loaded. Cannot process images.\n");
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
    ],
    [
        'src' => __DIR__ . '/assets/images/categories/grocery.png',
        'dest' => __DIR__ . '/assets/images/categories/grocery.png',
        'width' => 120,
        'height' => 120,
        'name' => 'Grocery Category Icon'
    ]
];

foreach ($targets as $t) {
    echo "Processing {$t['name']}...\n";
    if (!file_exists($t['src'])) {
        echo "Source file does not exist: {$t['src']}\n";
        continue;
    }

    // Since these files are internally JPEGs, we load them using imagecreatefromjpeg
    $srcImg = @imagecreatefromjpeg($t['src']);
    if (!$srcImg) {
        // Fallback: try imagecreatefrompng if it's already a real PNG
        $srcImg = @imagecreatefrompng($t['src']);
    }

    if (!$srcImg) {
        echo "Failed to load image: {$t['src']}\n";
        continue;
    }

    $origW = imagesx($srcImg);
    $origH = imagesy($srcImg);
    echo "Original Dimensions: {$origW}x{$origH}\n";

    // Create target image
    $destImg = imagecreatetruecolor($t['width'], $t['height']);
    
    // Enable alpha blending and save alpha for true PNG transparency support
    imagealphablending($destImg, false);
    imagesavealpha($destImg, true);

    // Resize
    imagecopyresampled(
        $destImg,
        $srcImg,
        0, 0, 0, 0,
        $t['width'], $t['height'],
        $origW, $origH
    );

    // Save as true PNG
    // We temp-save to avoid conflicts, then overwrite
    $tempDest = $t['dest'] . '.tmp';
    $saved = imagepng($destImg, $tempDest, 9);
    
    if ($saved && file_exists($tempDest)) {
        if (@rename($tempDest, $t['dest'])) {
            echo "Successfully converted and compressed to {$t['width']}x{$t['height']} PNG.\n";
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

// Clear PHP OPcache if active
if (function_exists('opcache_reset')) {
    opcache_reset();
}
echo "Done.\n";
?>
