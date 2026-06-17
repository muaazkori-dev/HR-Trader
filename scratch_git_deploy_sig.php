<?php
header('Content-Type: text/plain; charset=utf-8');
echo shell_exec("git add scratch_check_sig.php 2>&1") . "\n";
echo shell_exec("git commit -m \"Update diagnostic signature check script\" 2>&1") . "\n";
echo shell_exec("git pull origin main --no-edit 2>&1") . "\n";
echo shell_exec("git push origin main 2>&1") . "\n";
?>
