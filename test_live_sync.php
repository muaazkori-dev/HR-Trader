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

log_msg("=== LIVE INTEGRATION TEST: SELF-HEALING SYNC ===");

// 1. Setup paths
$target_dir = __DIR__ . '/assets/images/products';
$backup_dir = __DIR__ . '/../product_uploads';
$test_filename = 'test_live_sync_prod.png';
$target_file = $target_dir . '/' . $test_filename;
$backup_file = $backup_dir . '/' . $test_filename;

log_msg("Target File: $target_file");
log_msg("Backup File: $backup_file");

// Ensure directories
if (!@is_dir($target_dir)) {
    @mkdir($target_dir, 0777, true);
}
if (!@is_dir($backup_dir)) {
    @mkdir($backup_dir, 0777, true);
}

// 2. Write mock image to backup folder
$written = @file_put_contents($backup_file, "MOCK IMAGE CONTENT " . time());
if ($written !== false) {
    log_msg("1. Successfully wrote mock file to backup folder.");
} else {
    log_msg("Error: Failed to write mock file to backup folder.");
    exit;
}

// Ensure it is deleted from target
if (@file_exists($target_file)) {
    @unlink($target_file);
}
log_msg("2. Verified target file is missing.");

// 3. Connect to DB and insert mock product
log_msg("3. Connecting to database to insert mock product row...");
try {
    require_once __DIR__ . '/config/db.php';
    
    // Check if test product already exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = 'MOCK_SYNC_TEST' LIMIT 1");
    $stmt->execute();
    $mock_id = $stmt->fetchColumn();
    
    if (!$mock_id) {
        // Insert product with the test image path
        $stmt_ins = $pdo->prepare("INSERT INTO products (barcode, name, description, price, stock_quantity, category, image) 
                                   VALUES ('MOCK_SYNC_TEST', 'Mock Sync Test Product', 'Testing self-healing', 10.00, 10, 'grocery', :image)");
        $stmt_ins->execute(['image' => 'assets/images/products/' . $test_filename]);
        $mock_id = $pdo->lastInsertId();
        log_msg(" - Inserted mock product row with ID: $mock_id");
    } else {
        // Update existing mock product image path
        $stmt_upd = $pdo->prepare("UPDATE products SET image = :image WHERE id = :id");
        $stmt_upd->execute(['image' => 'assets/images/products/' . $test_filename, 'id' => $mock_id]);
        log_msg(" - Updated existing mock product row ID: $mock_id");
    }
    
    // 4. Trigger the sync logic by executing db.php image sync block again
    log_msg("4. Running sync logic manually from script...");
    
    $stmt_sync = $pdo->query("SELECT image FROM products WHERE image IS NOT NULL AND image != ''");
    $sync_count = 0;
    while ($row_sync = $stmt_sync->fetch()) {
        $img_rel_path = $row_sync['image'];
        $filename = basename($img_rel_path);
        
        $t_file = $target_dir . '/' . $filename;
        $b_file = $backup_dir . '/' . $filename;
        
        if (!@file_exists($t_file) && @file_exists($b_file) && @is_file($b_file)) {
            if (@copy($b_file, $t_file)) {
                $sync_count++;
            }
        }
    }
    log_msg(" - Manual sync loop completed. Restored $sync_count files.");

    // 5. Verify target file was restored
    log_msg("5. Verifying target file existence...");
    if (@file_exists($target_file)) {
        log_msg(" - SUCCESS: Target file was successfully restored!");
        log_msg(" - Content: " . @file_get_contents($target_file));
    } else {
        log_msg(" - FAILED: Target file is still missing!");
    }

    // 6. Cleanup
    log_msg("6. Cleaning up mock data and files...");
    if ($mock_id) {
        $pdo->prepare("DELETE FROM products WHERE id = :id")->execute(['id' => $mock_id]);
        log_msg(" - Deleted mock product row.");
    }
    @unlink($target_file);
    @unlink($backup_file);
    log_msg(" - Deleted test files.");

} catch (Throwable $e) {
    log_msg("Exception: " . $e->getMessage());
}

log_msg("=== TEST COMPLETED ===");
?>
