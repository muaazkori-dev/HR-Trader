<?php
$output = "=== GIT PUSH ATTEMPT ===\n";
// Run git push and redirect stderr to stdout
$output .= shell_exec("git push origin main 2>&1");
file_put_contents(__DIR__ . '/git_push_output.txt', $output);
echo "Git push executed. Check git_push_output.txt";
?>
