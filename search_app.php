<?php
header('Content-Type: text/plain; charset=utf-8');

$log_file = 'C:/Users/Administrator/.gemini/antigravity/brain/1419d0d6-16b6-426a-9bf0-925d8b5f8b89/.system_generated/logs/transcript.jsonl';

if (!file_exists($log_file)) {
    echo "Transcript file not found at " . $log_file . "\n";
    // Let's also check parent directories or list directory
    $dir = dirname($log_file);
    if (is_dir($dir)) {
        echo "Directory exists: " . $dir . "\n";
        print_r(scandir($dir));
    } else {
        echo "Directory does not exist: " . $dir . "\n";
    }
    exit;
}

$keywords = ['app', 'apk', 'mobile', 'android', 'ios', 'webview', 'phonegap', 'cordova', 'flutter', 'react native', 'play store', 'application'];

$handle = fopen($log_file, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        if ($data && isset($data['content'])) {
            $content = $data['content'];
            $found = false;
            foreach ($keywords as $kw) {
                if (stripos($content, $kw) !== false) {
                    $found = true;
                    break;
                }
            }
            if ($found && $data['source'] === 'USER_EXPLICIT') {
                echo "Step " . $data['step_index'] . " (" . $data['source'] . " - " . $data['type'] . "):\n";
                echo $content . "\n";
                echo "=====================================\n";
            }
        }
    }
    fclose($handle);
} else {
    echo "Could not open transcript file.\n";
}
?>
