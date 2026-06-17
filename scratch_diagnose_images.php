<?php
header('Content-Type: text/plain; charset=utf-8');

$images = [
    'assets/images/logo.png',
    'assets/images/favicon.png',
    'assets/images/categories/anaj.png',
    'assets/images/categories/grocery.png',
    'assets/images/categories/ice_cream.png',
    'assets/images/categories/cold_drinks.png',
    'assets/images/categories/milk.png',
    'assets/images/categories/cosmetics.png',
    'assets/images/categories/snacks.png',
    'assets/images/categories/bakery.png',
    'assets/images/categories/sauce.png'
];

foreach ($images as $img) {
    $path = __DIR__ . '/' . $img;
    echo "Image: $img\n";
    if (file_exists($path)) {
        echo "Exists: YES\n";
        echo "Size: " . filesize($path) . " bytes\n";
        $info = @getimagesize($path);
        if ($info) {
            echo "Dimensions: {$info[0]}x{$info[1]}\n";
            echo "Mime: {$info['mime']}\n";
        } else {
            echo "getimagesize failed: Image might be corrupted or invalid format.\n";
        }
    } else {
        echo "Exists: NO\n";
    }
    echo "----------------------------------------\n";
}
?>
