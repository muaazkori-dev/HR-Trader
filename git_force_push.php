<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
echo "=== STAGING ALL CHANGES ===\n";
echo shell_exec("git add -A 2>&1");
echo "\n=== COMMITTING ===\n";
echo shell_exec("git commit -m \"Fix unexpected PHP tag syntax error in index.php\" 2>&1");
echo "\n=== PUSHING ===\n";
echo shell_exec("git push origin main 2>&1");
?>
