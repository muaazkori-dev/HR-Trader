<?php
header('Content-Type: text/plain; charset=utf-8');

$paths = [
    'assets/images/products/',
    'assets/images/categories/',
    'assets/css/',
    'assets/webfonts/',
];

echo "=== DIRECTORY PERMISSIONS ===\n";
foreach ($paths as $path) {
    $full = __DIR__ . '/' . $path;
    $exists = is_dir($full) ? "YES" : "NO";
    $writable = is_writable($full) ? "YES" : "NO";
    $perms = is_dir($full) ? substr(sprintf('%o', fileperms($full)), -4) : "N/A";
    echo "Path: {$path} | Exists: {$exists} | Writable: {$writable} | Perms: {$perms}\n";
    
    if (is_dir($full)) {
        // Try to write a dummy file
        $dummy = $full . 'test_write.txt';
        $write_test = @file_put_contents($dummy, 'test');
        if ($write_test !== false) {
            echo "  -> Write test: SUCCESS\n";
            @unlink($dummy);
        } else {
            echo "  -> Write test: FAILED\n";
        }
    }
}

echo "\n=== PHP ENVIRONMENT ===\n";
echo "GD Extension: " . (extension_loaded('gd') ? "ENABLED" : "DISABLED") . "\n";
echo "File Uploads: " . (ini_get('file_uploads') ? "ON" : "OFF") . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
?>
