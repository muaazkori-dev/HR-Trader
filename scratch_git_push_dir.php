<?php
header('Content-Type: text/plain; charset=utf-8');
// Change directory to the root of the project
chdir(__DIR__);
echo "Current directory: " . getcwd() . "\n\n";

echo "=== GIT CONFIG ===\n";
echo shell_exec("git config user.email \"muaazkori-dev@users.noreply.github.com\" 2>&1") . "\n";
echo shell_exec("git config user.name \"muaazkori-dev\" 2>&1") . "\n";

echo "=== GIT STATUS ===\n";
echo shell_exec("git status 2>&1") . "\n";

echo "=== GIT ADD ALL ===\n";
echo shell_exec("git add -A 2>&1") . "\n";

echo "=== GIT COMMIT ===\n";
echo shell_exec("git commit -m \"Deploy latest scripts including show_file and optimize_images\" 2>&1") . "\n";

echo "=== GIT PUSH ===\n";
echo shell_exec("git push origin main 2>&1") . "\n";
?>
