<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== Listing scratch_ files ===\n";
foreach (glob(__DIR__ . '/scratch_*') as $file) {
    echo basename($file) . " (" . filesize($file) . " bytes)\n";
}
?>
