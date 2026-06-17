<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT STATUS ===\n";
echo shell_exec("git status 2>&1") . "\n";
echo "=== GIT ADD ALL ===\n";
echo shell_exec("git add -A 2>&1") . "\n";
echo "=== GIT COMMIT ===\n";
echo shell_exec("git commit -m \"Deploy all diagnostic and optimization scripts\" 2>&1") . "\n";
echo "=== GIT PUSH ===\n";
echo shell_exec("git push origin main 2>&1") . "\n";
?>
