<?php
// Temporary script to search logs
$log_path = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/.system_generated/logs/transcript.jsonl';
if (!file_exists($log_path)) {
    die("Log file not found at " . $log_path);
}
$search_term = 'compress_site_assets.php';
$f = fopen($log_path, 'r');
$found = 0;
while (($line = fgets($f)) !== false) {
    if (strpos($line, $search_term) !== false && (strpos($line, '<?php') !== false || strpos($line, 'imagecreate') !== false || strpos($line, 'imagepng') !== false)) {
        echo "=== FOUND IN LOG ===\n";
        echo $line . "\n\n";
        $found++;
        if ($found >= 5) break;
    }
}
fclose($f);
