<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== COMMITTING OPTIMIZED IMAGES ===\n";

echo "Staging optimized files...\n";
echo shell_exec("git add assets/images/logo.png assets/images/favicon.png assets/images/categories/grocery.png git_push.bat 2>&1") . "\n";

echo "Git Status:\n";
echo shell_exec("git status 2>&1") . "\n";

echo "Committing changes...\n";
echo shell_exec("git commit -m \"Optimize and compress brand assets and grocery category icon (true PNG conversion)\" 2>&1") . "\n";

echo "Pushing to live server...\n";
echo shell_exec("git push origin main 2>&1") . "\n";

echo "Done.\n";
?>
