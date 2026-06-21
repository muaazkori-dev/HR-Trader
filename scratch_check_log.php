<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== LAST 3 COMMITS ===\n";
echo shell_exec("git log -n 3 2>&1") . "\n";
echo "=== SHOW HEAD CONTENTS ===\n";
echo shell_exec("git show --name-status HEAD 2>&1") . "\n";
?>
