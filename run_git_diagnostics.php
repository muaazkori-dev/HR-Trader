<?php
$output = "=== GIT STATUS ===\n";
$output .= shell_exec("git status 2>&1");

$output .= "\n=== GIT LOG (LAST 3 COMMITS) ===\n";
$output .= shell_exec("git log -n 3 2>&1");

$output .= "\n=== GIT REMOTE ===\n";
$output .= shell_exec("git remote -v 2>&1");

$output .= "\n=== SYSTEM PATH ===\n";
$output .= shell_exec("where git 2>&1");

file_put_contents(__DIR__ . '/git_output.txt', $output);
echo "Diagnostics written to git_output.txt";
?>
