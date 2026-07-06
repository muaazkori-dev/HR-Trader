<?php
header('Content-Type: text/plain');
chdir("d:/xampp orginal/htdocs/HR Traders");
echo "1. Staging files...\n";
echo shell_exec("git add --all 2>&1");
echo "2. Committing...\n";
echo shell_exec("git commit -m \"Fix: Added live autocomplete search and barcode matching to Admin Inventory Desk\" 2>&1");
echo "3. Pushing...\n";
echo shell_exec("git push origin main 2>&1");
?>
