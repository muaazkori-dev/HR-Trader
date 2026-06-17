<?php
header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__ . '/assets/images/products';
$file = $dir . '/test_write.txt';

echo "Directory: $dir\n";
echo "Exists: " . (is_dir($dir) ? "YES" : "NO") . "\n";
echo "Writable: " . (is_writable($dir) ? "YES" : "NO") . "\n";

$written = @file_put_contents($file, "test write");
if ($written !== false) {
    echo "Successfully wrote to test_write.txt!\n";
    @unlink($file);
} else {
    echo "Failed to write to test_write.txt.\n";
}
?>
