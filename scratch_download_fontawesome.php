<?php
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

$css_url = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css';
$css_dest = __DIR__ . '/assets/css/all.min.css';

$webfonts_dir = __DIR__ . '/assets/webfonts/';
if (!is_dir($webfonts_dir)) {
    mkdir($webfonts_dir, 0777, true);
}

echo "=== Downloading FontAwesome CSS ===\n";
echo "Fetching: $css_url\n";
$css_content = @file_get_contents($css_url);
if ($css_content === false) {
    die("FAILED to download FontAwesome CSS!");
}

if (file_put_contents($css_dest, $css_content) !== false) {
    echo "Saved CSS to $css_dest (" . number_format(filesize($css_dest) / 1024, 1) . " KB)\n";
} else {
    die("FAILED to write CSS file!");
}

// Font files to download
$font_files = [
    'fa-solid-900.woff2',
    'fa-solid-900.ttf',
    'fa-regular-400.woff2',
    'fa-regular-400.ttf',
    'fa-brands-400.woff2',
    'fa-brands-400.ttf',
    'fa-v4compat.woff2',
    'fa-v4compat.ttf'
];

$font_url_base = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/webfonts/';

echo "\n=== Downloading FontAwesome Font Files ===\n";
foreach ($font_files as $font_file) {
    $url = $font_url_base . $font_file;
    $dest = $webfonts_dir . $font_file;
    
    echo "Downloading $font_file... ";
    
    $data = @file_get_contents($url);
    if ($data === false) {
        echo "FAILED to download from $url (Skipping)\n";
        continue;
    }
    
    if (file_put_contents($dest, $data) !== false) {
        echo "SUCCESS! Saved to $dest (" . number_format(filesize($dest) / 1024, 1) . " KB)\n";
    } else {
        echo "FAILED to write to $dest\n";
    }
}

echo "\nFontAwesome local packaging complete!\n";
?>
