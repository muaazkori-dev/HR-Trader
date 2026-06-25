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

log_msg("=== SIMULATING SELF-HEALING BACKUP-RESTORE (DATABASE-DRIVEN STYLE) ===");

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
$test_file = 'test_sync_db_driven.txt';
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

log_msg("4. Running database-driven self-healing sync logic...");
// Simulate list of product images fetched from DB
$db_product_images = [
    'test_sync_db_driven.txt'
];

$sync_count = 0;
foreach ($db_product_images as $image_name) {
    $t_file = $target_dir . '/' . $image_name;
    $b_file = $backup_dir . '/' . $image_name;
    
    log_msg(" - Checking if image is missing: $image_name");
    if (!@file_exists($t_file)) {
        log_msg("   * Missing in target. Checking in backup folder...");
        if (@file_exists($b_file) && @is_file($b_file)) {
            log_msg("   * Found in backup. Restoring...");
            $restored = @copy($b_file, $t_file);
            if ($restored) {
                log_msg("   * Restored successfully");
                $sync_count++;
            } else {
                log_msg("   * Restore failed");
            }
        } else {
            log_msg("   * Not found in backup");
        }
    } else {
        log_msg("   * Already exists in target");
    }
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
