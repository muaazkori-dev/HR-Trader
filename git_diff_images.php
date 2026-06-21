<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT DIFF IMAGES ===\n";
echo "git diff --stat:\n";
echo shell_exec("git diff 2>&1") . "\n";
echo "git diff assets/images/logo.png:\n";
echo shell_exec("git diff assets/images/logo.png 2>&1") . "\n";
?>
