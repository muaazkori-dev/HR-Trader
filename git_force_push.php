<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== STAGING ALL CHANGES ===\n";
echo shell_exec("git add -A 2>&1");
echo "\n=== COMMITTING ===\n";
echo shell_exec("git commit -m \"Clean up temporary developer diagnostic scripts\" 2>&1");
echo "\n=== PUSHING ===\n";
echo shell_exec("git push origin main 2>&1");
?>
