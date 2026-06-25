<?php
header('Content-Type: text/plain; charset=utf-8');
while (ob_get_level()) {
    ob_end_flush();
}
ob_implicit_flush(true);

function log_msg($msg) {
    echo $msg . "\n";
    flush();
}

log_msg("=== SIMULATING SELF-HEALING BACKUP-RESTORE (STEP-BY-STEP) ===");

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

log_msg("Calculated Backup Path: $backup_dir");

log_msg("1. Writing test file to target...");
$test_file = 'test_sync_' . time() . '.txt';
$target_path = $target_dir . '/' . $test_file;
$backup_path = $backup_dir . '/' . $test_file;

if (!@is_dir($target_dir)) {
    @mkdir($target_dir, 0777, true);
}
if (!@is_dir($backup_dir)) {
    @mkdir($backup_dir, 0777, true);
}

$written = @file_put_contents($target_path, "Sync Test Content");
if ($written !== false) {
    log_msg(" - Success: Wrote $written bytes to target ($test_file)");
} else {
    log_msg(" - Failed to write to target");
}

log_msg("2. Copying file to backup folder (simulating upload dual-write)...");
$copied = @copy($target_path, $backup_path);
if ($copied) {
    log_msg(" - Success: Copied file to backup folder");
} else {
    log_msg(" - Failed to copy to backup folder");
}

log_msg("3. Deleting file from target (simulating Git deploy wipe)...");
@unlink($target_path);
if (!@file_exists($target_path)) {
    log_msg(" - Success: File is now missing from target");
} else {
    log_msg(" - Failed: File still exists in target");
}

log_msg("4. Running self-healing sync logic...");
$sync_count = 0;
if (@is_dir($backup_dir) && @is_dir($target_dir)) {
    $files = @scandir($backup_dir);
    if ($files !== false) {
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $t_file = $target_dir . '/' . $file;
            $b_file = $backup_dir . '/' . $file;
            log_msg(" - Checking backup file: $file");
            if (!@file_exists($t_file) && @is_file($b_file)) {
                log_msg("   * File is missing in target. Restoring...");
                $restored = @copy($b_file, $t_file);
                if ($restored) {
                    log_msg("   * Restored successfully");
                    $sync_count++;
                } else {
                    log_msg("   * Restore failed");
                }
            } else {
                log_msg("   * File already exists or is not a file");
            }
        }
    } else {
        log_msg(" - Failed to scandir backup directory");
    }
} else {
    log_msg(" - Backup or target directory not accessible");
}
log_msg(" - Sync completed. Restored $sync_count files.");

log_msg("5. Verifying file in target...");
if (@file_exists($target_path)) {
    log_msg(" - Success: File was successfully restored! Content: " . @file_get_contents($target_path));
    // Cleanup test files
    @unlink($target_path);
    @unlink($backup_path);
    log_msg(" - Cleanup completed.");
} else {
    log_msg(" - Failed: File was not restored");
}

log_msg("=== SIMULATION COMPLETED ===");
?>
