<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== STAGING AND COMMITTING SCRIPTS ===\n";

echo "Staging files...\n";
echo shell_exec("git add compress_on_live.php download_live_assets.php check_live_categories.php git_push.bat copy_assets.php 2>&1") . "\n";

echo "Git Status:\n";
echo shell_exec("git status 2>&1") . "\n";

echo "Committing...\n";
echo shell_exec("git commit -m \"Update compression and sync scripts for all categories\" 2>&1") . "\n";

echo "Pushing...\n";
echo shell_exec("git push origin main 2>&1") . "\n";

echo "Done.\n";
?>
