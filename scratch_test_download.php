<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$remote_url = "https://thehrtraders.com/assets/images/products/prod_6a4a137a753009.02157262.png";
$local_path = __DIR__ . "/next-store/public/assets/images/products/test.png";

echo "Testing download of: $remote_url\n";

$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);

$img_data = file_get_contents($remote_url, false, stream_context_create($arrContextOptions));
if ($img_data !== false) {
    echo "Successfully fetched " . strlen($img_data) . " bytes.\n";
    $dir = dirname($local_path);
    if (!is_dir($dir)) {
        echo "Creating dir: $dir\n";
        mkdir($dir, 0777, true);
    }
    if (file_put_contents($local_path, $img_data)) {
        echo "Saved to $local_path\n";
    } else {
        echo "FAILED to save to $local_path\n";
    }
} else {
    echo "FAILED to fetch image.\n";
    print_r(error_get_last());
}
