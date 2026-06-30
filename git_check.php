<?php
header('Content-Type: text/plain');

$paths = [
    'assets/images/categories' => __DIR__ . '/assets/images/categories',
    'product_uploads/categories' => __DIR__ . '/../product_uploads/categories'
];

foreach ($paths as $name => $full) {
    echo "--- $name ---\n";
    echo "Path: $full\n";
    echo "Exists: " . (is_dir($full) ? 'yes' : 'no') . "\n";
    echo "Writeable: " . (is_writable($full) ? 'yes' : 'no') . "\n";
    if (is_dir($full)) {
        echo "Files:\n";
        $files = scandir($full);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $f_path = $full . '/' . $f;
            if (is_dir($f_path)) {
                echo "  $f [DIR]\n";
            } else {
                echo "  $f (" . @filesize($f_path) . " bytes)\n";
            }
        }
    }
    echo "\n";
}
?>
