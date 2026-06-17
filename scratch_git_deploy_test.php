<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT CONFIG ===\n";
echo shell_exec("git config user.email \"muaazkori-dev@users.noreply.github.com\" 2>&1") . "\n";
echo shell_exec("git config user.name \"muaazkori-dev\" 2>&1") . "\n";

echo "=== GIT ADD ===\n";
echo shell_exec("git add scratch_test_gd.php 2>&1") . "\n";

echo "=== GIT COMMIT ===\n";
echo shell_exec("git commit -m \"Add GD diagnostic test script\" 2>&1") . "\n";

echo "=== GIT PULL ===\n";
echo shell_exec("git pull origin main --no-edit 2>&1") . "\n";

echo "=== GIT PUSH ===\n";
echo shell_exec("git push origin main 2>&1") . "\n";
?>
