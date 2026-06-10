<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Allow owner to run
require_role(['owner']);

ob_start();

echo "=== HR Traders DB Settings Diagnostic ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    echo "1. Checking PDO Connection...\n";
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "   PDO connection active.\n";
    } else {
        echo "   PDO connection is null or not initialized!\n";
    }

    echo "\n2. Testing get_setting('active_theme')...\n";
    $theme = get_setting('active_theme', 'NOT_SET');
    echo "   Current active_theme: '{$theme}'\n";

    echo "\n3. Testing update_setting('active_theme', 'midnight_indigo')...\n";
    $update_res = update_setting('active_theme', 'midnight_indigo');
    if ($update_res) {
        echo "   update_setting() returned TRUE.\n";
        $new_val = get_setting('active_theme', 'ERROR_FETCHING');
        echo "   Value in DB now: '{$new_val}'\n";
    } else {
        echo "   update_setting() returned FALSE!\n";
        // Check PDO error
        if (isset($pdo)) {
            echo "   PDO Error Code: " . $pdo->errorCode() . "\n";
            echo "   PDO Error Info: " . json_encode($pdo->errorInfo()) . "\n";
        }
    }

    echo "\n4. Reverting back to original theme '{$theme}'...\n";
    $revert_res = update_setting('active_theme', $theme);
    echo "   Revert result: " . ($revert_res ? "SUCCESS" : "FAILED") . "\n";
    echo "   Reverted value in DB: '" . get_setting('active_theme') . "'\n";

    echo "\n5. Checking settings table contents:\n";
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch()) {
        echo "   Key: " . str_pad($row['key_name'], 30) . " Value: " . substr($row['val_value'], 0, 50) . "...\n";
    }

} catch (Exception $e) {
    echo "Error encountered: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

// Write output to workspace log file
file_put_contents(__DIR__ . '/test_theme_log.txt', $output);

// Also display in browser
header('Content-Type: text/plain');
echo $output;
