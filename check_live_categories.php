<?php
header('Content-Type: text/plain; charset=utf-8');
$dir = __DIR__ . '/assets/images/categories';
echo "=== LIVE CATEGORY IMAGES ===\n\n";

if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_file($path)) {
            $size = filesize($path);
            echo "$file:\n";
            echo "  Size: $size bytes (" . round($size / 1024, 2) . " KB)\n";
            $info = @getimagesize($path);
            if ($info) {
                echo "  Mime-Type: " . $info['mime'] . "\n";
                echo "  Dimensions: " . $info[0] . "x" . $info[1] . "\n";
            } else {
                echo "  Mime-Type: Unknown / Not an image\n";
            }
            echo "\n";
        }
    }
} else {
    echo "Directory does not exist: $dir\n";
}
?>
