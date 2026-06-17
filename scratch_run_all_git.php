<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);

echo "=== GIT STATUS ===\n";
echo shell_exec("git status 2>&1");

echo "\n=== RUNNING GIT ADD ===\n";
echo shell_exec("git add -A 2>&1");

echo "\n=== RUNNING GIT COMMIT ===\n";
echo shell_exec('git commit -m "Deploy latest check_permissions script and fix categories" 2>&1');

echo "\n=== RUNNING GIT PUSH ===\n";
echo shell_exec("git push origin main 2>&1");
?>
