<?php
set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

echo "=== RUNNING LOCAL NEXT.JS BUILD TEST ===\n\n";

chdir(__DIR__ . '/next-store');
echo "Current directory: " . getcwd() . "\n";

// Run next build
$output = shell_exec('npm run build 2>&1');
echo $output;
