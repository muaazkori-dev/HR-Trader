<?php
header('Content-Type: text/plain; charset=utf-8');
$file = $_GET['file'] ?? '';
// Limit to current directory files for safety
$file = basename($file);
if (empty($file) || !file_exists(__DIR__ . '/' . $file)) {
    die("File not found or invalid: $file");
}
echo file_get_contents(__DIR__ . '/' . $file);
?>
