<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== INITIATING BUILD V2 ===\n";

$bat = 'D:\\xampp orginal\\htdocs\\HR Traders\\build_run_v2.bat';
pclose(popen("start /B cmd /c \"\"$bat\"\"", "r"));

echo "Started v2!";
