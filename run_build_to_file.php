<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== INITIATING BUILD VIA BATCH FILE ===\n";

$bat = 'D:\\xampp orginal\\htdocs\\HR Traders\\build_run.bat';
pclose(popen("start /B cmd /c \"\"$bat\"\"", "r"));

echo "Batch file started. Logs will be written to build_log.txt\n";
