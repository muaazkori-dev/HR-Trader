<?php
header('Content-Type: text/plain; charset=utf-8');
$header_path = __DIR__ . '/includes/header.php';
if (file_exists($header_path)) {
    $content = file_get_contents($header_path);
    echo "=== VERIFYING LIVE HEADER ===\n";
    echo "Has min-w-[180px]: " . (strpos($content, 'min-w-[180px]') !== false ? "YES" : "NO") . "\n";
    echo "Cart Drawer contains 'hidden': " . (strpos($content, 'id="cart-drawer" class="fixed right-0 top-0 bottom-0 h-screen max-h-screen w-full sm:w-[400px] bg-white border-l border-slate-200 z-50 translate-x-full transition-transform duration-300 flex flex-col text-slate-800 hidden"') !== false ? "YES" : "NO") . "\n";
} else {
    echo "Error: header.php not found\n";
}
?>
