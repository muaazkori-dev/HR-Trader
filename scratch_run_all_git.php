<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);

echo "=== RUNNING GIT ADD ===\n";
$output_add = shell_exec("git add -A 2>&1");
echo $output_add ? $output_add : "SUCCESS\n";

echo "\n=== RUNNING GIT COMMIT ===\n";
$output_commit = shell_exec('git commit -m "Update check_permissions script with folder creation test" 2>&1');
echo $output_commit ? $output_commit : "SUCCESS\n";

echo "\n=== RUNNING GIT PUSH ===\n";
$output_push = shell_exec("git push origin main 2>&1");
echo $output_push ? $output_push : "SUCCESS\n";
?>
