<?php
header('Content-Type: text/plain; charset=utf-8');
$dir = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89';
echo "=== SOURCE ARTIFACT SIZES ===\n";
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_file($path)) {
            echo "$file: " . filesize($path) . " bytes\n";
        }
    }
} else {
    echo "Directory does not exist: $dir\n";
}
?>
