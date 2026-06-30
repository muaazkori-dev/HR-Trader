<?php
header('Content-Type: text/plain');
chdir("d:/xampp orginal/htdocs/HR Traders");
echo "1. Staging files...\n";
echo shell_exec("git add git_check.php git_push_helper.php 2>&1");
echo "2. Committing...\n";
echo shell_exec("git commit -m \"Fix: Add permissions diagnostics check script\" 2>&1");
echo "3. Pushing...\n";
echo shell_exec("git push origin main 2>&1");
?>
