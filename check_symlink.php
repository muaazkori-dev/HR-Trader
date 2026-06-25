<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== SYMLINK SUPPORT TEST ===\n";

if (function_exists('symlink')) {
    echo "symlink function: ENABLED\n";
    
    // Try to create a test symlink
    $src = __DIR__ . '/test_relative.php';
    $dst = __DIR__ . '/test_symlink_link.txt';
    
    if (file_exists($dst)) {
        @unlink($dst);
    }
    
    $res = @symlink($src, $dst);
    if ($res) {
        echo "symlink test: SUCCESS\n";
        @unlink($dst);
    } else {
        $err = error_get_last();
        echo "symlink test: FAILED (" . ($err ? $err['message'] : 'unknown error') . ")\n";
    }
} else {
    echo "symlink function: DISABLED / NOT FOUND\n";
}
?>
