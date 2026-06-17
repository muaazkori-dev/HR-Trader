<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dir = __DIR__ . '/assets/images/products/';
echo "Directory path: $dir\n";

if (!is_dir($dir)) {
    echo "Directory does not exist. Attempting to create it...\n";
    if (mkdir($dir, 0777, true)) {
        echo "Successfully created directory.\n";
    } else {
        echo "FAILED to create directory.\n";
    }
} else {
    echo "Directory exists.\n";
}

if (is_dir($dir)) {
    echo "Is writable: " . (is_writable($dir) ? "YES" : "NO") . "\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
    
    $test_file = $dir . 'test_write.txt';
    echo "Testing file write to: $test_file\n";
    if (@file_put_contents($test_file, 'test') !== false) {
        echo "Write SUCCESS!\n";
        @unlink($test_file);
    } else {
        echo "Write FAILED!\n";
    }
}
?>
