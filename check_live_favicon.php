<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "https://thehrtraders.com/favicon.ico";
echo "Checking live favicon size at: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $size = strlen($content);
    echo "Live Favicon Size: $size bytes\n";
    echo "Local New Favicon Size: " . filesize(__DIR__ . '/next-store/src/app/favicon.ico') . " bytes\n";
    if (abs($size - filesize(__DIR__ . '/next-store/src/app/favicon.ico')) < 10) {
        echo "MATCH: Vercel has successfully deployed the new brand favicon!\n";
    } else {
        echo "MISMATCH: Vercel has NOT deployed the new favicon yet.\n";
    }
} else {
    echo "ERROR: Live favicon returned HTTP Code $http_code\n";
}
