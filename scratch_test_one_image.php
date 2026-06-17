<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$file = __DIR__ . '/assets/images/categories/grocery.png';
echo "File path: $file\n";
if (!file_exists($file)) {
    die("File does not exist!");
}

clearstatcache(true, $file);
echo "File size: " . filesize($file) . " bytes\n";

$data = file_get_contents($file);
if ($data === false) {
    die("Failed to read file data!");
}

echo "File data length: " . strlen($data) . " bytes\n";
echo "First 10 bytes (hex): " . bin2hex(substr($data, 0, 10)) . "\n";

echo "Attempting imagecreatefromstring...\n";
$im = @imagecreatefromstring($data);

if ($im) {
    echo "Successfully loaded image via string! Width: " . imagesx($im) . ", Height: " . imagesy($im) . "\n";
    imagedestroy($im);
} else {
    echo "Failed to load image via string!\n";
}
?>
