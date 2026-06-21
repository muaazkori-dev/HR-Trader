<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== FORCE ADD AND PUSH ===\n";

// Force add the required scripts and files
echo "Staging files...\n";
echo shell_exec("git add -f compress_on_live.php 2>&1") . "\n";
echo shell_exec("git add -f check_live_images.php 2>&1") . "\n";
echo shell_exec("git add -f check_live_categories.php 2>&1") . "\n";
echo shell_exec("git add -f push_to_live.php 2>&1") . "\n";
echo shell_exec("git add git_push.bat 2>&1") . "\n";
echo shell_exec("git add copy_assets.php 2>&1") . "\n";

// Check git status
echo "Git Status:\n";
echo shell_exec("git status 2>&1") . "\n";

// Commit
echo "Committing...\n";
echo shell_exec("git commit -m \"Force add live diagnostics and compression helper scripts\" 2>&1") . "\n";

// Push
echo "Pushing...\n";
echo shell_exec("git push origin main 2>&1") . "\n";

echo "Done.\n";
?>
