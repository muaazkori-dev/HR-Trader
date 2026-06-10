<?php
// HR Traders - Duplicates & Screenshot Cleanup Utility
// Deletes duplicate image copies containing '(1)' from the Products directory
// and can also clean up accidentally uploaded screenshots from the root folder.

header('Content-Type: text/plain; charset=utf-8');

$products_dir = __DIR__ . '/Products';
$deleted_count = 0;

echo "--- Cleaning Products Directory ---\n";
if (is_dir($products_dir)) {
    $files = scandir($products_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        // Match files that have (1) in their names
        if (strpos($file, '(1)') !== false) {
            $file_path = $products_dir . '/' . $file;
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    echo "Deleted duplicate: $file\n";
                    $deleted_count++;
                } else {
                    echo "Failed to delete: $file\n";
                }
            }
        }
    }
} else {
    echo "Products directory not found.\n";
}

echo "\n--- Cleaning Root Directory Screenshots ---\n";
// Also clean up any duplicate screenshots or mistakenly uploaded screenshots with (1) in the root
$root_files = scandir(__DIR__);
$root_deleted = 0;
foreach ($root_files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    // Only delete screenshots in the root that are duplicates containing (1)
    if (strpos($file, 'Screenshot_') === 0 && strpos($file, '(1)') !== false) {
        $file_path = __DIR__ . '/' . $file;
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                echo "Deleted root duplicate screenshot: $file\n";
                $root_deleted++;
            }
        }
    }
}

echo "\nCleanup Finished.\n";
echo "Total duplicates deleted from Products folder: $deleted_count\n";
echo "Total duplicates deleted from root folder: $root_deleted\n";
?>
