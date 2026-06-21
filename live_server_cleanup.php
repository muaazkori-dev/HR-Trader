<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);

$files_to_delete = [
    'scratch_git_add_test.php',
    'scratch_git_deploy_all.php',
    'scratch_git_deploy_helper.php',
    'scratch_git_deploy_sig.php',
    'scratch_git_deploy_simple.php',
    'scratch_git_deploy_test.php',
    'scratch_git_deploy_update.php',
    'scratch_git_history.php',
    'scratch_git_path.php',
    'scratch_git_push_dir.php',
    'scratch_git_run.php',
    'scratch_list_temp.php',
    'scratch_search.php',
    'scratch_test_gd.php',
    'scratch_test_simple.php',
    'scratch_server_cleanup.php',
    'check_db_categories.php',
    'check_live_site_state.php',
    'check_products.php',
    'check_permissions.php',
    'copy_assets_to_artifacts.php',
    'db_debug.php',
    'db_diagnostic_antigravity.php',
    'db_diagnostic_plain.php',
    'db_test.php',
    'scratch_run_all_git.php',
    'scratch/run_cmd.php',
    'live_server_cleanup.php' // Self-delete
];

echo "=== CLEANING UP TEMPORARY FILES ON PRODUCTION SERVER ===\n";
foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        if (@unlink($file)) {
            echo "SUCCESS: Deleted {$file}\n";
        } else {
            echo "FAILED: Could not delete {$file}\n";
        }
    } else {
        echo "INFO: {$file} does not exist on server\n";
    }
}
?>
