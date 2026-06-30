<?php
header('Content-Type: text/plain');
chdir("d:/xampp orginal/htdocs/HR Traders");
echo "1. Staging all files...\n";
echo shell_exec("git add --all 2>&1");
echo "2. Committing changes...\n";
echo shell_exec("git commit -m \"Deployment: Added permissions check and path fixes\" 2>&1");
echo "3. Pushing changes...\n";
echo shell_exec("git push origin main 2>&1");
?>
