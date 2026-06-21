<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== GIT TRACKING CHECK ===\n";
echo "ls-files:\n";
echo shell_exec("git ls-files -s assets/images/logo.png assets/images/favicon.png assets/images/categories/grocery.png 2>&1") . "\n";
echo "git status on images:\n";
echo shell_exec("git status assets/images/logo.png assets/images/favicon.png assets/images/categories/grocery.png 2>&1") . "\n";
?>
