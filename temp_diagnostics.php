<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

echo "=== DB PRODUCTS IMAGES (LAST 10) ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, image, stock_quantity FROM products ORDER BY id DESC LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Image Path: '{$row['image']}' | Stock: {$row['stock_quantity']}\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "\n=== DIRECTORY DIAGNOSTICS ===\n";
$target = __DIR__ . '/assets/images/products';
echo "Target Path: $target\n";
try {
    if (file_exists($target)) {
        echo "Exists: Yes\n";
        if (is_link($target)) {
            echo "Is Symlink: Yes\n";
            $link_target = @readlink($target);
            echo "Link Target: " . ($link_target === false ? "FAILED TO READ LINK" : $link_target) . "\n";
        } else {
            echo "Is Symlink: No (It is a normal directory)\n";
        }
        
        echo "\n=== LIST FILES IN TARGET (MAX 10) ===\n";
        $files = @scandir($target);
        if ($files === false) {
            echo "scandir failed on target.\n";
        } else {
            $count = 0;
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $count++;
                if ($count > 10) {
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
} catch (Throwable $t) {
    echo "Target check failed: " . $t->getMessage() . "\n";
}

echo "\n=== LIST FILES IN SOURCE (IF ACCESSIBLE - MAX 10) ===\n";
$source = '/home/u622906513/domains/thehrtraders.com/product_uploads';
echo "Source Path: $source\n";
try {
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
                if ($count > 10) {
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
} catch (Throwable $t) {
    echo "Source check failed: " . $t->getMessage() . "\n";
}
?>
