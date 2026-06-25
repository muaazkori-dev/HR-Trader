<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== SEARCHING FOR LOST IMAGES ===\n\n";

$start_dir = __DIR__ . '/..';
echo "Searching from parent directory: " . realpath($start_dir) . "\n\n";

function search_images($dir, &$results, $depth = 0) {
    if ($depth > 4) return; // Limit depth to prevent timeout / CageFS lockups
    
    $files = @glob($dir . '/*');
    if ($files === false) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        if (@is_dir($file)) {
            // Avoid scanning system dirs that might trigger CageFS blocks
            $base = basename($file);
            if (in_array($base, ['.git', '.cagefs', 'tmp', 'logs', 'etc', 'var', 'mail', 'ssl', '.cpanel'])) {
                continue;
            }
            search_images($file, $results, $depth + 1);
        } else {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $filename = basename($file);
                // Check if it matches our product image naming convention or was recently modified
                $mtime = @filemtime($file);
                $age_hours = (time() - $mtime) / 3600;
                
                if (strpos($filename, 'prod_') === 0 || $age_hours < 48) {
                    $results[] = [
                        'path' => $file,
                        'size' => @filesize($file),
                        'modified' => date('Y-m-d H:i:s', $mtime),
                        'age_hours' => round($age_hours, 1)
                    ];
                }
            }
        }
    }
}

$results = [];
search_images($start_dir, $results);

echo "Found " . count($results) . " matching image files:\n\n";
echo str_pad("Path", 70) . str_pad("Size (KB)", 12) . str_pad("Modified", 22) . "Age (Hours)\n";
echo str_repeat("-", 115) . "\n";

foreach ($results as $r) {
    // Hide absolute path structure if WAF blocks it
    $display_path = str_replace(realpath(__DIR__ . '/../..'), '', realpath($r['path']));
    $display_path = str_replace('\\', '/', $display_path);
    
    echo str_pad($display_path, 70) . 
         str_pad(round($r['size'] / 1024, 1), 12) . 
         str_pad($r['modified'], 22) . 
         $r['age_hours'] . "\n";
}

echo "\nSearch completed.\n";
?>
