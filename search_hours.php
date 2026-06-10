<?php
header('Content-Type: text/plain');

$file = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/.system_generated/steps/928/content.md';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Look for times like 9:00, 11:00, 24, etc.
    preg_match_all('/(open|close|hour|time|Monday|Tuesday|Sunday|\d{1,2}:\d{2})/i', $content, $matches);
    print_r(array_unique($matches[0]));
    
    echo "\nSearching for surrounding context of 'Open':\n";
    $pos = 0;
    while (($pos = stripos($content, 'open', $pos)) !== false) {
        echo substr($content, max(0, $pos - 50), 100) . "\n";
        $pos += 4;
    }
} else {
    echo "File not found\n";
}
?>
