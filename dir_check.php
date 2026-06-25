<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== DIRECTORY DIAGNOSTICS ===\n";

$target = __DIR__ . '/assets/images/products';
echo "Target Path: $target\n";
if (file_exists($target)) {
    echo "Exists: Yes\n";
    if (is_link($target)) {
        echo "Is Symlink: Yes\n";
        $link_target = @readlink($target);
        echo "Link Target: " . ($link_target === false ? "FAILED TO READ LINK" : $link_target) . "\n";
    } else {
        echo "Is Symlink: No (It is a normal directory)\n";
    }
    
    echo "\n=== LIST FILES IN TARGET ===\n";
    $files = @scandir($target);
    if ($files === false) {
        echo "scandir failed on target.\n";
    } else {
        $count = 0;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $count++;
            if ($count > 15) {
                echo " - ... and more files ...\n";
                break;
            }
            $filePath = $target . '/' . $file;
            echo " - $file (" . (is_dir($filePath) ? 'dir' : @filesize($filePath) . ' bytes') . ")\n";
        }
    }
} else {
    echo "Exists: No\n";
}

echo "\n=== SOURCE DIRECTORY ===\n";
$source = '/home/u622906513/domains/thehrtraders.com/product_uploads';
echo "Source Path: $source\n";
if (is_dir($source)) {
    echo "Source Exists: Yes\n";
    $files = @scandir($source);
    if ($files === false) {
        echo "scandir failed on source.\n";
    } else {
        $count = 0;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $count++;
            if ($count > 15) {
                echo " - ... and more files ...\n";
                break;
            }
            $filePath = $source . '/' . $file;
            echo " - $file (" . (is_dir($filePath) ? 'dir' : @filesize($filePath) . ' bytes') . ")\n";
        }
    }
} else {
    echo "Source Exists: No or Not Readable\n";
}
?>
