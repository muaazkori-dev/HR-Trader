<?php
header('Content-Type: text/plain; charset=utf-8');

$files = [
    'check_all_products.php',
    'check_backup.php',
    'check_db_categories.php',
    'check_live_categories.php',
    'check_live_images.php',
    'check_live_site_state.php',
    'check_order_status.php',
    'check_permissions.php',
    'check_products.php',
    'check_upload_limits.php',
    'check_users.php',
    'cleanup_duplicates.php',
    'compress_on_live.php',
    'copy_assets.php',
    'copy_assets_to_artifacts.php',
    'copy_new_categories.php',
    'db_debug.php',
    'db_diagnostic_antigravity.php',
    'db_diagnostic_plain.php',
    'db_fix_categories.php',
    'db_test.php',
    'download_live_assets.php',
    'git_check_tracking.php',
    'git_commit_final.php',
    'git_commit_images.php',
    'git_debug.php',
    'git_diff_images.php',
    'git_do_commit.php',
    'git_force_push_all.php',
    'git_status.php',
    'list_recent.php',
    'live_server_cleanup.php',
    'read_deploy_log.php',
    'run_git_diagnostics.php',
    'run_git_push.php',
    'scan_backups.php',
    'test_read_backup.php'
];

$content = "<?php\nhttp_response_code(404);\necho \"404 Not Found\";\n?>\n";

echo "=== DISABLING UTILITY FILES ===\n";

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $written = file_put_contents($path, $content);
        if ($written !== false) {
            echo "Disabled: $file\n";
        } else {
            echo "Failed to disable: $file\n";
        }
    } else {
        echo "File does not exist (skipping): $file\n";
    }
}

echo "Done.\n";
?>
