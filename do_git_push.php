<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== RUNNING GIT DEPLOYMENT PROCESS ===\n";

chdir(__DIR__);
echo "Current directory: " . getcwd() . "\n";

echo "1. Git config author...\n";
echo shell_exec('git config user.email "muaazkori-dev@users.noreply.github.com" 2>&1');
echo shell_exec('git config user.name "muaazkori-dev" 2>&1');

echo "2. Git add all files...\n";
echo shell_exec('git add . 2>&1');

echo "3. Git commit...\n";
echo shell_exec('git commit -m "Trigger rebuild for branch selector and full-width slider layout" 2>&1');

echo "4. Git push origin main...\n";
$output = shell_exec('git push origin main 2>&1');
echo $output;

if (strpos($output, 'Rejected') !== false || strpos($output, 'failed') !== false || strpos($output, 'error') !== false) {
    echo "\nTrying force push...\n";
    echo shell_exec('git push -f origin main 2>&1');
}

echo "\nDone!\n";
?>
