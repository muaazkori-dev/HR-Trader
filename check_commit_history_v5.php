<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);
echo "=== GIT LOG ===\n";
echo shell_exec('git log -n 5 --oneline 2>&1') . "\n";
echo "=== GIT STATUS ===\n";
echo shell_exec('git status 2>&1') . "\n";
