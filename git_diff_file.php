<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT LOG STAT ===\n";
echo shell_exec("git log -n 1 --stat 2>&1");
?>
