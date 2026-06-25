<?php
// Scan the workspace and parent folder to find files modified recently (last 24 hours)
header('Content-Type: text/plain; charset=utf-8');

$rootDirs = [
    __DIR__,
    dirname(__DIR__),
    'C:\Users\Administrator\.gemini\antigravity',
    'C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89'
];

$recentFiles = [];
$now = time();

foreach ($rootDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile()) {
            $mtime = $fileInfo->getMTime();
            // Check if modified in last 24 hours
            if ($now - $mtime < 86400 * 2) { // 2 days limit
                $recentFiles[] = [
                    'path' => $fileInfo->getRealPath(),
                    'mtime' => $mtime,
                    'size' => $fileInfo->getSize()
                ];
            }
        }
    }
}

// Sort by modification time descending
usort($recentFiles, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});

$out = "=== RECENTLY MODIFIED FILES ===\n";
foreach (array_slice($recentFiles, 0, 50) as $f) {
    $date = date('Y-m-d H:i:s', $f['mtime']);
    $out .= sprintf("[%s] Size: %s bytes | %s\n", $date, number_format($f['size']), $f['path']);
}

file_put_contents(__DIR__ . '/recent_files_log.txt', $out);
echo $out;
?>
