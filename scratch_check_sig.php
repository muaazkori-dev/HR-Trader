<?php
header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__ . '/assets/images/categories/';
$files = glob($dir . '*');

foreach ($files as $file) {
    if (is_file($file)) {
        $filename = basename($file);
        $size = filesize($file);
        $handle = fopen($file, 'rb');
        $bytes = fread($handle, 4);
        fclose($handle);
        
        $hex = bin2hex($bytes);
        echo "$filename (" . number_format($size / 1024, 1) . " KB): " . strtoupper($hex) . " -> ";
        
        if (strpos($hex, '89504e47') === 0) {
            echo "Valid PNG Signature\n";
        } elseif (strpos($hex, 'ffd8') === 0) {
            echo "Valid JPEG Signature\n";
        } elseif (strpos($hex, '47494638') === 0) {
            echo "Valid GIF Signature\n";
        } elseif (strpos($hex, '52494646') === 0) {
            echo "Valid WebP/RIFF Signature\n";
        } else {
            echo "Unknown Signature\n";
        }
    }
}
?>
