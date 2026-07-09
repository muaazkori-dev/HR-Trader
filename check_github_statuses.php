<?php
header('Content-Type: text/plain; charset=utf-8');

$commit = "6dccc04";
$url = "https://api.github.com/repos/muaazkori-dev/HR-Trader/commits/$commit/status";
echo "Fetching GitHub status for commit $commit from:\n$url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Antigravity-Agent');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
if ($http_code === 200) {
    $data = json_decode($res, true);
    echo "State: " . ($data['state'] ?? 'unknown') . "\n";
    echo "Statuses count: " . count($data['statuses'] ?? []) . "\n\n";
    foreach ($data['statuses'] ?? [] as $status) {
        echo "- Context: " . $status['context'] . "\n";
        echo "  State: " . $status['state'] . "\n";
        echo "  Description: " . $status['description'] . "\n";
        echo "  Target URL: " . $status['target_url'] . "\n\n";
    }
} else {
    echo "Response: $res\n";
}
