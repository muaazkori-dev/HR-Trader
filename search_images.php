<?php
// PHP Script to search for product images recursively
header('Content-Type: text/plain; charset=utf-8');

echo "Searching for files starting with 'prod_' in: " . __DIR__ . "\n\n";

function search_dir($dir, &$results = array()) {
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = $dir . DIRECTORY_SEPARATOR . $value;
        if (!is_dir($path)) {
            if (strpos($value, 'prod_') === 0) {
                $results[] = $path;
            }
        } else if ($value != "." && $value != ".." && $value != "node_modules" && $value != ".next" && $value != ".git") {
            search_dir($path, $results);
        }
    }
    return $results;
}

$found_files = search_dir(__DIR__);

echo "Total files found: " . count($found_files) . "\n\n";
foreach ($found_files as $index => $file) {
    echo ($index + 1) . ". " . str_replace(__DIR__, '', $file) . " (" . filesize($file) . " bytes)\n";
}
