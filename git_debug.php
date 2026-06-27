<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT STATUS ===\n";
passthru("git status -u 2>&1");
echo "\n=== GIT DIFF ===\n";
passthru("git diff 2>&1");
?>
