<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT STATUS ===\n";
echo shell_exec("git status -u 2>&1");

echo "\n=== GIT STATUS IGNORED ===\n";
echo shell_exec("git status --ignored 2>&1");

echo "\n=== GIT LOG (LAST 3 COMMITS) ===\n";
echo shell_exec("git log -n 3 2>&1");

echo "\n=== GIT REMOTE ===\n";
echo shell_exec("git remote -v 2>&1");

echo "\n=== SYSTEM PATH ===\n";
echo shell_exec("where git 2>&1");
?>
