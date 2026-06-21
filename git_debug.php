<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT DEBUG FOR SCRIPTS ===\n";
echo "Working directory: " . getcwd() . "\n";
echo "File exists: " . (file_exists('compress_on_live.php') ? "YES" : "NO") . "\n";
echo "File size: " . filesize('compress_on_live.php') . " bytes\n";
echo "Git status on compress_on_live.php:\n";
echo shell_exec("git status compress_on_live.php 2>&1") . "\n";
echo "Git diff on compress_on_live.php:\n";
echo shell_exec("git diff HEAD -- compress_on_live.php 2>&1") . "\n";
echo "Git add output:\n";
echo shell_exec("git add compress_on_live.php 2>&1") . "\n";
echo "Git status after add:\n";
echo shell_exec("git status 2>&1") . "\n";
?>
