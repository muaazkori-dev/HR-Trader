<?php
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$file = __DIR__ . '/assets/images/categories/anaj.png';
if (!file_exists($file)) {
    die("File does not exist: $file");
}

echo "File size: " . filesize($file) . " bytes\n";

// Let's try to load using imagecreatefrompng
$im = imagecreatefrompng($file);
if ($im) {
    echo "SUCCESS! Loaded image. Width: " . imagesx($im) . ", Height: " . imagesy($im) . "\n";
    imagedestroy($im);
} else {
    echo "FAILED to load image via imagecreatefrompng\n";
    print_r(error_get_last());
}
?>
