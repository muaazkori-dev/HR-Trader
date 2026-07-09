<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "https://api.github.com/repos/muaazkori-dev/HR-Trader/commits?per_page=5";
echo "Fetching recent commits from:\n$url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Antigravity-Agent');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $commits = json_decode($res, true);
    foreach ($commits as $c) {
        $sha = $c['sha'];
        $msg = $c['commit']['message'];
        $date = $c['commit']['author']['date'];
        echo "Commit: " . substr($sha, 0, 7) . " | Date: $date\n";
        echo "Message: $msg\n";
        
        // Fetch status for this commit
        $status_url = "https://api.github.com/repos/muaazkori-dev/HR-Trader/commits/$sha/status";
        $ch2 = curl_init($status_url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_USERAGENT, 'Antigravity-Agent');
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
        $res2 = curl_exec($ch2);
        curl_close($ch2);
        
        $data2 = json_decode($res2, true);
        echo "State: " . ($data2['state'] ?? 'no status') . "\n";
        foreach ($data2['statuses'] ?? [] as $status) {
            echo "  - " . $status['context'] . ": " . $status['state'] . " (" . ($status['description'] ?? '') . ")\n";
        }
        echo str_repeat("-", 40) . "\n";
    }
} else {
    echo "ERROR: HTTP $http_code\n$res\n";
}
