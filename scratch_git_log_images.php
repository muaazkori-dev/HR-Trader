<?php
header('Content-Type: text/plain; charset=utf-8');
echo "Checking Git history for product images...\n\n";

$output = [];
$return_var = 0;
// Search all commit logs for any mention of prod_
exec("git log --all --name-status", $output, $return_var);

$mentions = [];
$current_commit = "";
foreach ($output as $line) {
    if (strpos($line, 'commit ') === 0) {
        $current_commit = $line;
    }
    if (strpos($line, 'prod_') !== false) {
        $mentions[] = $current_commit . " -> " . trim($line);
    }
}

echo "Found " . count($mentions) . " historical file actions:\n";
echo implode("\n", array_slice($mentions, 0, 50)) . "\n";
