<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);

$files_to_delete = [
    'scratch_download_fontawesome.php',
    'scratch_git_run.php',
    'check_permissions.php',
    'check_products.php',
    'db_debug.php',
    'db_diagnostic_antigravity.php',
    'db_diagnostic_plain.php',
    'scratch_run_all_git.php',
    'scratch_search_results.md',
    'scratch_search_results.txt',
    'check_live_site_state.php',
    'scratch_server_cleanup.php' // Will delete itself at the end
];

echo "=== DELETING TEMPORARY SCRIPTS ON SERVER ===\n";
foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        if (@unlink($file)) {
            echo "Successfully deleted: {$file}\n";
        } else {
            echo "Failed to delete local/server file: {$file}\n";
        }
    } else {
        echo "File does not exist: {$file}\n";
    }
}
?>
