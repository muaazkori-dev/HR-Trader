<?php
header('Content-Type: text/plain; charset=utf-8');

echo "--- QUICK SEARCH FOR PRODUCT IMAGES ---\n\n";

$search_paths = [
    'D:\\xampp orginal\\htdocs',
    'C:\\Users\\Administrator\\Downloads',
    'C:\\Users\\Administrator\\Desktop',
    'C:\\Users\\Administrator\\Documents',
    'D:\\B2B',
    'D:\\B2B Website',
    'D:\\works'
];

$found = [];

function search_prod_images($dir, &$found) {
    if (count($found) >= 500) return;
    
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);
    if (!is_dir($dir) || !is_readable($dir)) return;
    
    $files = @scandir($dir);
    if ($files === false) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            if (
                $file === 'node_modules' || 
                $file === '.next' || 
                $file === '.git' || 
                $file === 'AppData' ||
                $file === 'vendor'
            ) {
                continue;
            }
            search_prod_images($path, $found);
        } else {
            if (strpos($file, 'prod_') === 0 && (strpos($file, '.png') !== false || strpos($file, '.jpg') !== false || strpos($file, '.jpeg') !== false)) {
                $found[] = [
                    'path' => $path,
                    'size' => filesize($path)
                ];
            }
        }
    }
}

foreach ($search_paths as $path) {
    search_prod_images($path, $found);
}

$output_text = "Total files found: " . count($found) . "\n\n";
foreach ($found as $index => $item) {
    $output_text .= ($index + 1) . ". " . $item['path'] . " (" . $item['size'] . " bytes)\n";
}

file_put_contents(__DIR__ . '/search_results.txt', $output_text);
echo "Search completed. Found " . count($found) . " files. Results saved to search_results.txt.\n";
