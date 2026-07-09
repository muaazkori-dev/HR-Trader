<?php
set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSING NEXT.JS BUILD STATUS ===\n\n";

// 1. Kill hung Node processes on Windows
echo "Killing any hung Node.exe processes...\n";
$kill_output = shell_exec('taskkill /F /IM node.exe 2>&1');
echo $kill_output . "\n";

// Wait 2 seconds
sleep(2);

// 2. Run clean Next.js build
chdir(__DIR__ . '/next-store');
echo "Running clean Next.js build...\n";
$build_output = shell_exec('npm run build 2>&1');
echo $build_output;
