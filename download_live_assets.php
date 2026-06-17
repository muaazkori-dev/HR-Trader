<?php
header('Content-Type: text/plain; charset=utf-8');
echo "=== DOWNLOAD OPTIMIZED ASSETS FROM LIVE ===\n";

$assets = [
    'logo.png' => [
        'url' => 'https://thehrtraders.com/assets/images/logo.png',
        'dest' => __DIR__ . '/assets/images/logo.png'
    ],
    'favicon.png' => [
        'url' => 'https://thehrtraders.com/assets/images/favicon.png',
        'dest' => __DIR__ . '/assets/images/favicon.png'
    ]
];

// Add all category icons to sync
$categories = ['anaj', 'bakery', 'cold_drinks', 'cosmetics', 'ice_cream', 'milk', 'sauce', 'snacks', 'grocery'];
foreach ($categories as $cat) {
    $assets["{$cat}.png"] = [
        'url' => "https://thehrtraders.com/assets/images/categories/{$cat}.png",
        'dest' => __DIR__ . "/assets/images/categories/{$cat}.png"
    ];
}

foreach ($assets as $name => $info) {
    echo "Downloading $name...\n";
    $content = @file_get_contents($info['url']);
    if ($content === false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($info['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $content = curl_exec($ch);
            curl_close($ch);
        }
    }

    if ($content !== false && strlen($content) > 0) {
        $dir = dirname($info['dest']);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (file_put_contents($info['dest'], $content)) {
            echo "  Saved to: {$info['dest']}\n";
            echo "  Size: " . strlen($content) . " bytes (" . round(strlen($content) / 1024, 2) . " KB)\n";
        } else {
            echo "  Failed to write file locally.\n";
        }
    } else {
        echo "  Failed to download from {$info['url']}\n";
    }
    echo "----------------------------------------\n";
}
echo "Done.\n";
?>
