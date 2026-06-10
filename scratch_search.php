<?php
// Temporary script to search logs
$log_path = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/.system_generated/logs/transcript.jsonl';
if (!file_exists($log_path)) {
    die("Log file not found at " . $log_path);
}
$search_term = 'u622906513';
$f = fopen($log_path, 'r');
while (($line = fgets($f)) !== false) {
    if (strpos($line, $search_term) !== false) {
        echo $line . "\n\n";
    }
}
fclose($f);
