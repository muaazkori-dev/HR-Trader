<?php
header('Content-Type: text/plain; charset=utf-8');

$dir = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89';
echo "Scanning directory: $dir\n\n";

if (!is_dir($dir)) {
    echo "Directory does not exist!\n";
    exit;
}

$files = scandir($dir);
$file_list = [];

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $path = $dir . '/' . $file;
    $file_list[$file] = filemtime($path);
}

// Sort by modification time descending (newest first)
arsort($file_list);

echo "Newest uploaded files:\n";
reset($file_list);
for ($i = 0; $i < 5; $i++) {
    $file = key($file_list);
    if ($file === null) break;
    $time = $file_list[$file];
    echo "- $file (" . date('Y-m-d H:i:s', $time) . ") - Size: " . filesize($dir . '/' . $file) . " bytes\n";
    next($file_list);
}
