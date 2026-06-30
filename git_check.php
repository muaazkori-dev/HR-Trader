<?php
header('Content-Type: text/plain');
chdir("d:/xampp orginal/htdocs/HR Traders");
echo "Current Directory: " . getcwd() . "\n\n";

echo "1. Git Status (Before):\n";
echo shell_exec("git status 2>&1");

echo "\n2. Git Add All:\n";
echo shell_exec("git add -A 2>&1");

echo "\n3. Git Commit:\n";
echo shell_exec("git commit -m \"Fix: Added stylesheet cache buster and dark theme footer styles\" 2>&1");

echo "\n4. Git Push:\n";
echo shell_exec("git push origin main 2>&1");
?>
