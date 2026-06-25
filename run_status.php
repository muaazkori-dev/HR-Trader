<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT STATUS ===\n";
echo shell_exec("git status -u 2>&1");
echo "\n=== GIT DIFF ===\n";
echo shell_exec("git diff 2>&1");
?>
