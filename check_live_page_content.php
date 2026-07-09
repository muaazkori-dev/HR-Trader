<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "https://thehrtraders.com/?nocache=" . time();
echo "Downloading HTML from: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo "Download Success! Length: " . strlen($html) . " bytes\n\n";
    
    // Check for Branch 2 (Gulshan-e-Sardar)
    if (strpos($html, 'Gulshan-e-Sardar') !== false) {
        echo "FOUND: 'Gulshan-e-Sardar' is present in the live HTML!\n";
    } else {
        echo "NOT FOUND: 'Gulshan-e-Sardar' is missing from the live HTML.\n";
    }
    
    // Check for the new slider elements
    if (strpos($html, 'slider-track') !== false) {
        echo "FOUND: 'slider-track' is present in the live HTML!\n";
    } else {
        echo "NOT FOUND: 'slider-track' is missing from the live HTML.\n";
    }
} else {
    echo "ERROR: Fetch failed with HTTP $http_code\n";
}
