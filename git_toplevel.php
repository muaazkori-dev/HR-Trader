<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT TOPLEVEL ===\n";
echo shell_exec("git rev-parse --show-toplevel 2>&1");
echo "\n=== GIT STATUS SHORT ===\n";
echo shell_exec("git status -s 2>&1");
?>
