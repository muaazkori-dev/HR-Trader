<?php
header('Content-Type: text/plain; charset=utf-8');
chdir(__DIR__);
$cmd = $_GET['cmd'] ?? 'status';

// Basic safety: only allow git commands
if (strpos($cmd, 'git ') !== 0) {
    $cmd = 'git ' . $cmd;
}

echo "Executing: $cmd\n";
echo "Directory: " . getcwd() . "\n";
echo "----------------------------------------\n";
echo shell_exec($cmd . " 2>&1");
?>
