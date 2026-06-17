<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);

echo "=== GIT STATUS BEFORE ===\n";
echo shell_exec("git status 2>&1");

echo "\n=== RUNNING GIT ADD ===\n";
echo shell_exec("git add -v -A 2>&1");

echo "\n=== RUNNING GIT COMMIT ===\n";
echo shell_exec('git commit -m "Optimize font loading to be non-blocking and add cache control headers to prevent dynamic page caching" 2>&1');

echo "\n=== RUNNING GIT PUSH ===\n";
echo shell_exec("git push origin main 2>&1");
?>
