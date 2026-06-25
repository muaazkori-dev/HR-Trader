<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== RELATIVE DIR TESTS ===\n";

$dir = '../product_uploads';
echo "Checking directory: $dir\n";

try {
    $exists = @file_exists($dir);
    echo " - Exists: " . ($exists ? "Yes" : "No") . "\n";
    if ($exists) {
        $isDir = @is_dir($dir);
        echo " - Is Dir: " . ($isDir ? "Yes" : "No") . "\n";
        $isLink = @is_link($dir);
        echo " - Is Link: " . ($isLink ? "Yes" : "No") . "\n";
        
        $files = @scandir($dir);
        if ($files === false) {
            echo " - Scan: FAILED\n";
        } else {
            echo " - Scan: SUCCESS (" . count($files) . " items found)\n";
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                echo "   * $f\n";
            }
        }
    } else {
        // Try creating it
        $created = @mkdir($dir, 0777, true);
        echo " - Created: " . ($created ? "Yes" : "No") . "\n";
    }
} catch (Throwable $e) {
    echo " - Exception: " . $e->getMessage() . "\n";
}
?>
