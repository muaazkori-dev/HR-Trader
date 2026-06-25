<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== SERVER ENVIRONMENT CHECK ===\n";
echo "open_basedir: " . ini_get('open_basedir') . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "__DIR__: " . __DIR__ . "\n";

$test_paths = [
    '/home/u622906513/domains/thehrtraders.com/product_uploads',
    '/home/u622906513/product_uploads',
    '/home/u622906513/domains/thehrtraders.com/public_html/product_uploads',
    __DIR__ . '/../product_uploads',
];

echo "\n=== PATH ACCESSIBILITY ===\n";
foreach ($test_paths as $path) {
    echo "Path: $path\n";
    try {
        $exists = @file_exists($path);
        echo " - Exists: " . ($exists ? "Yes" : "No") . "\n";
        if (!$exists) {
            $created = @mkdir($path, 0777, true);
            echo " - Created: " . ($created ? "Yes" : "No") . "\n";
            if ($created) {
                @rmdir($path);
            }
        } else {
            echo " - Is Dir: " . (is_dir($path) ? "Yes" : "No") . "\n";
        }
    } catch (Throwable $e) {
        echo " - Exception: " . $e->getMessage() . "\n";
    }
}
?>
