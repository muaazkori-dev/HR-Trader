<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);
echo "=== GIT STATUS CHECK ===\n\n";

echo "Last Commit:\n";
echo shell_exec('git log -n 1 --oneline 2>&1') . "\n";

echo "Git Status:\n";
echo shell_exec('git status 2>&1') . "\n";
