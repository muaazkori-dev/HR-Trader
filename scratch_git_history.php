<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== RECENT COMMITS WITH FILES ===\n";
echo shell_exec("git log --stat -n 3 2>&1") . "\n";
?>
