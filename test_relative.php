<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== RELATIVE PATH TESTS ===\n";

$tests = [
    '../test_outside.txt' => 'Test outside public_html',
    './test_inside.txt' => 'Test inside public_html',
];

foreach ($tests as $path => $content) {
    echo "Testing path: $path\n";
    try {
        $written = @file_put_contents($path, $content);
        if ($written !== false) {
            echo " - Write: SUCCESS ($written bytes written)\n";
            $read = @file_get_contents($path);
            echo " - Read: " . ($read === $content ? "SUCCESS" : "FAILED") . "\n";
            @unlink($path);
        } else {
            echo " - Write: FAILED\n";
        }
    } catch (Throwable $e) {
        echo " - Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n=== CHECK SYMLINK SUPPORT ===\n";
echo "symlink function exists: " . (function_exists('symlink') ? "Yes" : "No") . "\n";
?>
