<?php
header('Content-Type: text/plain; charset=utf-8');
$files = [
    'logo.png' => __DIR__ . '/assets/images/logo.png',
    'favicon.png' => __DIR__ . '/assets/images/favicon.png',
    'grocery.png' => __DIR__ . '/assets/images/categories/grocery.png'
];

echo "=== LIVE IMAGE DIAGNOSTICS ===\n\n";
foreach ($files as $name => $path) {
    echo "$name:\n";
    if (file_exists($path)) {
        $size = filesize($path);
        echo "  Exists: YES\n";
        echo "  Size: $size bytes (" . round($size / 1024, 2) . " KB)\n";
        
        // Check image details using getimagesize
        $info = @getimagesize($path);
        if ($info) {
            echo "  Mime-Type: " . $info['mime'] . "\n";
            echo "  Dimensions: " . $info[0] . "x" . $info[1] . "\n";
        } else {
            echo "  Mime-Type: Unknown / Not an image\n";
        }
    } else {
        echo "  Exists: NO\n";
    }
    echo "\n";
}
?>
