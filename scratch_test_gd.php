<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== GD Library Details ===\n";
if (extension_loaded('gd')) {
    echo "GD is enabled!\n";
    print_r(gd_info());
} else {
    echo "GD is NOT enabled!\n";
}

echo "=== Memory Limit ===\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";

echo "=== Load Test ===\n";
$file = __DIR__ . '/assets/images/categories/anaj.png';
if (file_exists($file)) {
    echo "File exists! Size: " . filesize($file) . " bytes\n";
    $im = @imagecreatefrompng($file);
    if ($im) {
        echo "Successfully loaded PNG!\n";
        imagedestroy($im);
    } else {
        echo "Failed to load PNG!\n";
        $err = error_get_last();
        if ($err) {
            print_r($err);
        } else {
            echo "No PHP error captured. Check GD PNG support.\n";
        }
    }
} else {
    echo "File not found: $file\n";
}
?>
