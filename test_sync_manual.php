<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== SIMULATING SELF-HEALING BACKUP-RESTORE (RELATIVE PATH) ===\n";

function getRelativeBackupPath() {
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
    
    // Remove document root from script path to get the relative path
    $rel = str_replace($doc_root, '', $script);
    $rel = trim($rel, '/');
    
    // Count the number of subdirectories
    $depth = 0;
    if (!empty($rel)) {
        $parts = explode('/', $rel);
        $depth = count($parts) - 1;
    }
    
    // Build the relative path to parent directory of public_html
    $path = '../';
    for ($i = 0; $i < $depth; $i++) {
        $path .= '../';
    }
    return $path . 'product_uploads';
}

$target_dir = './assets/images/products';
$backup_dir = getRelativeBackupPath();

echo "Calculated Backup Path: $backup_dir\n";

echo "1. Writing test file to target...\n";
$test_file = 'test_sync_' . time() . '.txt';
$target_path = $target_dir . '/' . $test_file;
$backup_path = $backup_dir . '/' . $test_file;

if (!is_dir($target_dir)) {
    @mkdir($target_dir, 0777, true);
}
if (!is_dir($backup_dir)) {
    @mkdir($backup_dir, 0777, true);
}

$written = @file_put_contents($target_path, "Sync Test Content");
if ($written !== false) {
    echo " - Success: Wrote $written bytes to target ($test_file)\n";
} else {
    echo " - Failed to write to target\n";
}

echo "2. Copying file to backup folder (simulating upload dual-write)...\n";
$copied = @copy($target_path, $backup_path);
if ($copied) {
    echo " - Success: Copied file to backup folder\n";
} else {
    echo " - Failed to copy to backup folder\n";
}

echo "3. Deleting file from target (simulating Git deploy wipe)...\n";
@unlink($target_path);
if (!file_exists($target_path)) {
    echo " - Success: File is now missing from target\n";
} else {
    echo " - Failed: File still exists in target\n";
}

echo "4. Running self-healing sync logic...\n";
$sync_count = 0;
if (is_dir($backup_dir) && is_dir($target_dir)) {
    $files = @scandir($backup_dir);
    if ($files !== false) {
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $t_file = $target_dir . '/' . $file;
            $b_file = $backup_dir . '/' . $file;
            if (!file_exists($t_file) && is_file($b_file)) {
                $restored = @copy($b_file, $t_file);
                if ($restored) {
                    echo " - Restored: $file\n";
                    $sync_count++;
                }
            }
        }
    }
}
echo " - Sync completed. Restored $sync_count files.\n";

echo "5. Verifying file in target...\n";
if (file_exists($target_path)) {
    echo " - Success: File was successfully restored! Content: " . file_get_contents($target_path) . "\n";
    // Cleanup test files
    @unlink($target_path);
    @unlink($backup_path);
} else {
    echo " - Failed: File was not restored\n";
}
?>
