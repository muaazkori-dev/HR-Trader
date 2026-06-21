<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== HEAD COMMIT IMAGE SIZES ===\n";
echo "logo.png: " . trim(shell_exec("git cat-file -s HEAD:assets/images/logo.png 2>&1")) . " bytes\n";
echo "favicon.png: " . trim(shell_exec("git cat-file -s HEAD:assets/images/favicon.png 2>&1")) . " bytes\n";
echo "grocery.png: " . trim(shell_exec("git cat-file -s HEAD:assets/images/categories/grocery.png 2>&1")) . " bytes\n";
?>
