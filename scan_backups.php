<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== SCANNING SERVER FOR DIRECTORY BACKUPS ===\n\n";

$baseDir = '/home/u622906513';
if (!is_dir($baseDir)) {
    echo "Base directory $baseDir does not exist!\n";
    exit;
}

// Let's scan folders up to 3 levels deep for anything containing 'backup', 'old', 'previous', 'build', 'history'
function scanForBackups($dir, $depth = 0) {
    if ($depth > 3) return;
    
    $files = @scandir($dir);
    if ($files === false) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            $lower = strtolower($file);
            if (strpos($lower, 'backup') !== false || 
                strpos($lower, 'old') !== false || 
                strpos($lower, 'prev') !== false || 
                strpos($lower, 'build') !== false || 
                strpos($lower, 'release') !== false || 
                $file === 'history' || 
                $file === 'tmp') {
                echo "[DIR] $path (Modified: " . date('Y-m-d H:i:s', filemtime($path)) . ")\n";
                
                // If it's a products folder or has products inside, list it!
                $subfiles = @scandir($path);
                if ($subfiles !== false) {
                    foreach ($subfiles as $sf) {
                        if (strpos($sf, 'prod_') !== false) {
                            echo "      -> Found image: $sf (" . filesize($path . '/' . $sf) . " bytes)\n";
                        }
                    }
                }
            }
            scanForBackups($path, $depth + 1);
        } else {
            // Check for large zip or tar files that could be backups
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext === 'zip' || $ext === 'gz' || $ext === 'tar' || $ext === 'rar') {
                $mtime = filemtime($path);
                // If modified in the last 2 days
                if (time() - $mtime < 86400 * 2) {
                    echo "[FILE] $path (" . number_format(filesize($path)) . " bytes) | Modified: " . date('Y-m-d H:i:s', $mtime) . "\n";
                }
            }
        }
    }
}

scanForBackups($baseDir);
echo "\nScan completed.\n";
?>
