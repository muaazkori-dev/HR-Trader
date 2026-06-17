<?php
header('Content-Type: text/plain; charset=utf-8');
echo "__DIR__: " . __DIR__ . "\n";
echo "getcwd(): " . getcwd() . "\n";
echo "Git Toplevel: " . trim(shell_exec("git rev-parse --show-toplevel 2>&1")) . "\n";
?>
