<?php
header('Content-Type: text/plain; charset=utf-8');
echo "Git history check:\n\n";

$output = [];
$return_var = 0;
exec("git status", $output, $return_var);

echo "Git Status:\n";
echo implode("\n", $output) . "\n\n";

$output_log = [];
exec("git log -n 10 --name-status", $output_log, $return_var);
echo "Git Log (last 10 commits):\n";
echo implode("\n", $output_log) . "\n";
