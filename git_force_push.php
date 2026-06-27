<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT STATUS BEFORE ADD ===\n";
echo shell_exec("git status 2>&1");

echo "\n=== STAGING ALL CHANGES ===\n";
echo shell_exec("git add -A 2>&1");

echo "\n=== GIT STATUS AFTER ADD ===\n";
echo shell_exec("git status 2>&1");

echo "\n=== COMMITTING ===\n";
echo shell_exec("git commit -m \"Implement homepage auto-scrolling discount cards marquee and admin settings editor\" 2>&1");

echo "\n=== PUSHING ===\n";
echo shell_exec("git push origin main 2>&1");
?>
