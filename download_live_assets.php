<?php
// PHP Script to download all product images from the live site and restore favicon
header('Content-Type: text/plain; charset=utf-8');
ini_set('max_execution_time', 300); // 5 minutes

echo "--- HR TRADERS ASSET RESTORATION SCRIPT ---\n\n";

// 1. Restore Favicon
$favicon_src = "C:\\Users\\Administrator\\.gemini\\antigravity\\brain\\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\\hr_traders_new_favicon_1781114812720.png";
$favicon_destinations = [
    __DIR__ . '/next-store/src/app/favicon.ico',
    __DIR__ . '/next-store/src/app/icon.png',
    __DIR__ . '/next-store/public/favicon.ico',
    __DIR__ . '/next-store/public/assets/images/favicon.png',
    __DIR__ . '/assets/images/favicon.png'
];

if (file_exists($favicon_src)) {
    echo "Restoring custom brand favicon...\n";
    foreach ($favicon_destinations as $dest) {
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (copy($favicon_src, $dest)) {
            echo "-> Copied to: " . basename($dir) . '/' . basename($dest) . "\n";
        } else {
            echo "-> FAILED to copy to: " . $dest . "\n";
        }
    }
} else {
    echo "Favicon source not found at path: $favicon_src\n";
}

echo "\n----------------------------------------\n";

// 2. Download Product Images
$parse_file = __DIR__ . '/parse.php';
if (!file_exists($parse_file)) {
    die("Error: parse.php not found at $parse_file\n");
}

$content = file_get_contents($parse_file);
// Extract all product image filenames
preg_match_all('/assets\/images\/products\/([a-zA-Z0-9_\.\-]+)/', $content, $matches);

if (empty($matches[1])) {
    die("No product images found in parse.php.\n");
}

$filenames = array_unique($matches[1]);
echo "Found " . count($filenames) . " unique product images in parse.php.\n\n";

$next_products_dir = __DIR__ . '/next-store/public/assets/images/products/';
$php_products_dir = __DIR__ . '/assets/images/products/';

if (!is_dir($next_products_dir)) {
    mkdir($next_products_dir, 0777, true);
}
if (!is_dir($php_products_dir)) {
    mkdir($php_products_dir, 0777, true);
}

$success_count = 0;
$fail_count = 0;

foreach ($filenames as $filename) {
    // We don't download placeholder.png or generic folders
    if ($filename == '.gitkeep') continue;
    
    $remote_url = "https://thehrtraders.com/assets/images/products/" . rawurlencode($filename);
    $next_local_path = $next_products_dir . $filename;
    $php_local_path = $php_products_dir . $filename;
    
    echo "Downloading: $filename\n";
    
    // Bypass SSL verification for local XAMPP environment compatibility
    $arrContextOptions = array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
    );
    
    $img_data = @file_get_contents($remote_url, false, stream_context_create($arrContextOptions));
    if ($img_data !== false) {
        file_put_contents($next_local_path, $img_data);
        file_put_contents($php_local_path, $img_data);
        echo "   [OK] Saved successfully.\n";
        $success_count++;
    } else {
        $error = error_get_last();
        echo "   [FAILED] Could not download from: $remote_url. Error: " . ($error ? $error['message'] : 'unknown') . "\n";
        $fail_count++;
    }
}

echo "\n----------------------------------------\n";
echo "Restoration Complete!\n";
echo "Successfully downloaded: $success_count images.\n";
echo "Failed: $fail_count images.\n";
