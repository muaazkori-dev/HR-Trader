<?php
header('Content-Type: text/plain; charset=utf-8');

$artifacts_dir = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89';
$logo_src = __DIR__ . '/assets/images/logo.png';
$logo_dest = $artifacts_dir . '/hr_traders_logo.png';

echo "=== COPYING LOGO ===\n";
if (file_exists($logo_src)) {
    if (copy($logo_src, $logo_dest)) {
        echo "Successfully copied logo to: {$logo_dest}\n";
    } else {
        echo "Failed to copy logo.\n";
    }
} else {
    echo "Logo src file does not exist at: {$logo_src}\n";
}

echo "\n=== GENERATING QR CODE ===\n";
$qr_data = 'https://thehrtraders.com/';
$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode($qr_data);
$qr_local_dest = __DIR__ . '/assets/images/qr_code.png';
$qr_artifacts_dest = $artifacts_dir . '/qr_code.png';

$img_data = @file_get_contents($qr_url);
if ($img_data) {
    file_put_contents($qr_local_dest, $img_data);
    file_put_contents($qr_artifacts_dest, $img_data);
    echo "Successfully generated QR Code at:\n";
    echo "  - {$qr_local_dest}\n";
    echo "  - {$qr_artifacts_dest}\n";
} else {
    echo "Failed to download QR code from API.\n";
}
?>
