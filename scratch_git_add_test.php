<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== ADD TEST ===\n";
echo shell_exec("git add scratch_git_path.php 2>&1") . "\n";
echo "=== STATUS ===\n";
echo shell_exec("git status 2>&1") . "\n";
?>
