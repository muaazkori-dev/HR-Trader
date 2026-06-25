<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== VIEWING DEPLOYMENT LOGS ===\n\n";

$logDir = '/home/u622906513/domains/thehrtraders.com/.builds/logs/git';
if (!is_dir($logDir)) {
    echo "Logs directory does not exist: $logDir\n";
    exit;
}

// Find the latest subdirectory or deploy.log
$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($logDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile() && basename($fileInfo->getPathname()) === 'deploy.log') {
        $files[] = [
            'path' => $fileInfo->getRealPath(),
            'mtime' => $fileInfo->getMTime()
        ];
    }
}

// Sort by date modified descending
usort($files, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});

if (empty($files)) {
    echo "No deploy.log files found!\n";
    exit;
}

$latestLog = $files[0]['path'];
echo "Reading latest deployment log: $latestLog (" . date('Y-m-d H:i:s', $files[0]['mtime']) . ")\n";
echo "--------------------------------------------------\n\n";
echo file_get_contents($latestLog);
?>
