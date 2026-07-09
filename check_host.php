<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "https://thehrtraders.com/";
echo "Checking response headers for: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Get headers only
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$headers = curl_exec($ch);
curl_close($ch);

echo $headers;
