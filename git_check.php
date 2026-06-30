<?php
header('Content-Type: text/plain');
$paths = [
    'assets/images/categories',
    '../product_uploads/categories'
];
foreach ($paths as $p) {
    $full = dirname(__DIR__) . '/' . str_replace('../', '', $p);
    echo "$p path: $full\n";
    echo "$p exists: " . (is_dir($full) ? 'yes' : 'no') . "\n";
    echo "$p writeable: " . (is_writable($full) ? 'yes' : 'no') . "\n\n";
}
?>
