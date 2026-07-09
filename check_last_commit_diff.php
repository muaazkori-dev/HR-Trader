<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);
echo "=== LAST COMMIT INFO ===\n";
echo shell_exec('git show 7afc26a --stat 2>&1') . "\n";
