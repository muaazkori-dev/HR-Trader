<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== RUNNING GIT PUSH FOR BANNER LAYOUT ===\n";
$output = shell_exec("cmd /c \"d:\\xampp orginal\\htdocs\\HR Traders\\git_push.bat\" 2>&1");
echo $output;
?>
