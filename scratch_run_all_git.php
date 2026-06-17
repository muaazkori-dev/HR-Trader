<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);

echo "=== DELETING SCRATCH AND DIAGNOSTIC FILES FROM GIT ===\n";

$files_to_delete = [
    'check_permissions.php',
    'scratch_search_results.md',
    'scratch_search_results.txt',
];

foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        @unlink($file);
        echo "Deleted local file: {$file}\n";
    }
    echo shell_exec("git rm {$file} 2>&1") . "\n";
}

// Clean up scratch/ directory
if (file_exists('scratch/run_cmd.php')) {
    @unlink('scratch/run_cmd.php');
}
if (file_exists('scratch/search_logs.py')) {
    @unlink('scratch/search_logs.py');
}
if (is_dir('scratch')) {
    @rmdir('scratch');
}

echo "\n=== COMMITTING CLEANUP ===\n";
echo shell_exec("git add -A 2>&1") . "\n";
echo shell_exec('git commit -m "Clean up diagnostic and scratch files" 2>&1') . "\n";

echo "\n=== DELETING HELPER SCRIPT ITSELF FROM GIT ===\n";
echo shell_exec('git rm scratch_run_all_git.php 2>&1') . "\n";
echo shell_exec('git commit -m "Remove git runner helper script" 2>&1') . "\n";

echo "\n=== PUSHING CLEANUP TO REMOTE ===\n";
echo shell_exec("git push origin main 2>&1") . "\n";

echo "\n=== SELF DELETING ===\n";
@unlink(__FILE__);
echo "Helper script self-deleted successfully.\n";
?>
