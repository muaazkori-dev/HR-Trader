<?php
header('Content-Type: text/plain; charset=utf-8');
$dir = __DIR__ . '/assets/images/categories/';
$files = glob($dir . '*');
echo "Total files found: " . count($files) . "\n";
foreach ($files as $file) {
    if (is_file($file)) {
        clearstatcache(true, $file);
        echo basename($file) . ": " . number_format(filesize($file) / 1024, 1) . " KB\n";
    }
}
?>
