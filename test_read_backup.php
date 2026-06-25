<?php
header('Content-Type: text/plain; charset=utf-8');
// Disable output buffering
while (ob_get_level()) {
    ob_end_flush();
}
ob_implicit_flush(true);

function log_msg($msg) {
    echo $msg . "\n";
    flush();
}

log_msg("=== START STEP-BY-STEP DIAGNOSTICS ===");

$backup_dir = '../product_uploads';
$test_file = $backup_dir . '/diagnostic_test.txt';

log_msg("1. Checking if backup directory exists...");
$exists = @is_dir($backup_dir);
log_msg(" - Result: " . ($exists ? "Yes" : "No"));

log_msg("2. Writing test file to backup directory...");
$written = @file_put_contents($test_file, "Hello World");
if ($written !== false) {
    log_msg(" - Result: SUCCESS ($written bytes written)");
} else {
    log_msg(" - Result: FAILED");
}

log_msg("3. Checking file_exists on backup file...");
$file_exists = @file_exists($test_file);
log_msg(" - Result: " . ($file_exists ? "Yes" : "No"));

log_msg("4. Checking is_file on backup file...");
$is_file = @is_file($test_file);
log_msg(" - Result: " . ($is_file ? "Yes" : "No"));

log_msg("5. Reading content of backup file...");
$content = @file_get_contents($test_file);
log_msg(" - Result: Content = '$content'");

log_msg("6. Copying backup file back to public root...");
$dest = './diagnostic_restore.txt';
$copied = @copy($test_file, $dest);
log_msg(" - Result: " . ($copied ? "SUCCESS" : "FAILED"));

if (file_exists($dest)) {
    log_msg("7. Verifying restored file exists...");
    @unlink($dest);
    log_msg(" - Result: Restored file verified and cleaned up.");
}

log_msg("8. Cleaning up backup test file...");
@unlink($test_file);
log_msg(" - Result: Backup test file cleaned up.");

log_msg("=== DIAGNOSTICS COMPLETED SUCCESSFULLY ===");
?>
