<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);

echo "=== GIT LOG BEFORE ===\n";
echo shell_exec("git status 2>&1");

echo "\n=== RUNNING GIT ADD ===\n";
$output_add = shell_exec("git add -A 2>&1");
echo $output_add ? $output_add : "SUCCESS (No output)\n";

echo "\n=== RUNNING GIT COMMIT ===\n";
$output_commit = shell_exec('git commit -m "Transition FontAwesome to local hosting, update categories & diagnostic checks" 2>&1');
echo $output_commit ? $output_commit : "SUCCESS (No output)\n";

echo "\n=== RUNNING GIT PUSH ===\n";
$output_push = shell_exec("git push origin main 2>&1");
echo $output_push ? $output_push : "SUCCESS (No output)\n";

echo "\n=== GIT LOG AFTER ===\n";
echo shell_exec("git status 2>&1");
?>
