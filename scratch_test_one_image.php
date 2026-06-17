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

$img_info = getimagesize($file);
if (!$img_info) {
    die("getimagesize failed!");
}

echo "MIME type: " . $img_info['mime'] . "\n";
echo "Width: " . $img_info[0] . ", Height: " . $img_info[1] . "\n";

$mime = $img_info['mime'];
$im = null;

if ($mime === 'image/png') {
    echo "Loading using imagecreatefrompng...\n";
    $im = imagecreatefrompng($file);
} elseif ($mime === 'image/jpeg') {
    echo "Loading using imagecreatefromjpeg...\n";
    $im = imagecreatefromjpeg($file);
}

if ($im) {
    echo "Successfully loaded image! Width: " . imagesx($im) . ", Height: " . imagesy($im) . "\n";
    imagedestroy($im);
} else {
    echo "Failed to load image!\n";
}
?>
