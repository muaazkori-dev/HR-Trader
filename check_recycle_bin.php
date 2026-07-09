<?php
header('Content-Type: text/plain; charset=utf-8');

echo "--- RECYCLE BIN SEARCH FOR PRODUCT IMAGES ---\n\n";

$recycle_paths = [
    'C:\\$Recycle.Bin',
    'D:\\$RECYCLE.BIN'
];

$found = [];

function search_recycle($dir, &$found) {
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);
    if (!is_dir($dir)) return;
    
    $files = @scandir($dir);
    if ($files === false) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            search_recycle($path, $found);
        } else {
            // Check if the file content or original name matches (Recycle bin files are renamed to $I... and $R...)
            // But we can check files starting with $R because they contain the actual file data
            // and we can check if they are images of similar sizes, or check the original name in $I files.
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($file[0] === '$' && ($ext === 'png' || $ext === 'jpg' || $ext === 'jpeg')) {
                $found[] = [
                    'path' => $path,
                    'size' => filesize($path),
                    'name' => $file
                ];
            }
        }
    }
}

foreach ($recycle_paths as $path) {
    echo "Checking: $path...\n";
    search_recycle($path, $found);
}

echo "\nFound " . count($found) . " deleted image files in Recycle Bin:\n";
foreach ($found as $index => $item) {
    echo ($index + 1) . ". " . $item['path'] . " (" . $item['size'] . " bytes)\n";
}
