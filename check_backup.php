<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== CHECKING BACKUP ARCHIVE CONTENT ===\n\n";

$archiveFile = __DIR__ . '/u622906513.20260622141112.tar.gz';
if (!file_exists($archiveFile)) {
    // Try in parent folder as well just in case they moved it out of the project folder
    $archiveFile = dirname(__DIR__) . '/u622906513.20260622141112.tar.gz';
}

if (!file_exists($archiveFile)) {
    echo "Backup archive u622906513.20260622141112.tar.gz not found in " . __DIR__ . " or " . dirname(__DIR__) . "!\n";
    exit;
}

echo "Found archive: $archiveFile (" . number_format(filesize($archiveFile)) . " bytes)\n\n";

try {
    // We can open tar.gz using PharData
    $phar = new PharData($archiveFile);
    echo "Successfully loaded archive. Scanning files...\n\n";
    
    $fileCount = 0;
    $productImages = [];
    
    // We need to iterate recursively
    $iterator = new RecursiveIteratorIterator($phar);
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        $fileCount++;
        
        if (strpos($path, 'assets/images/products') !== false) {
            $productImages[] = [
                'path' => $path,
                'size' => $file->getSize()
            ];
        }
    }
    
    echo "Total files in archive: $fileCount\n";
    echo "Product images found in archive: " . count($productImages) . "\n\n";
    
    foreach ($productImages as $img) {
        echo "  - " . basename($img['path']) . " (" . number_format($img['size']) . " bytes)\n";
    }
    
} catch (Exception $e) {
    echo "Error reading archive: " . $e->getMessage() . "\n";
}
?>
