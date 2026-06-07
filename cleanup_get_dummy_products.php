<?php
header('Content-Type: text/plain');

$file = __DIR__ . '/get_dummy_products.php';
if (file_exists($file)) {
    unlink($file);
    echo "Deleted get_dummy_products.php\n";
} else {
    echo "File not found\n";
}

unlink(__FILE__);
?>
