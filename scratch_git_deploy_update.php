<?php
header('Content-Type: text/plain; charset=utf-8');
echo shell_exec("git add scratch_optimize_images.php 2>&1") . "\n";
echo shell_exec("git commit -m \"Update image optimization helper to be signature-aware\" 2>&1") . "\n";
echo shell_exec("git pull origin main --no-edit 2>&1") . "\n";
echo shell_exec("git push origin main 2>&1") . "\n";
?>
