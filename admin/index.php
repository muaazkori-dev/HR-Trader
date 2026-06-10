<?php
// HR Traders Admin Routing Index
// Prevents 403 Forbidden directory listing blocks by redirecting users appropriately

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$secret_key = get_setting('admin_secret_key', 'hr_secure_desk_99');

// Validate key parameter
if (isset($_GET['secret']) && $_GET['secret'] === $secret_key) {
    $_SESSION['admin_authorized'] = true;
}

// Block if not logged in AND not authorized via secret key
if (!is_logged_in() && (!isset($_SESSION['admin_authorized']) || $_SESSION['admin_authorized'] !== true)) {
    header("HTTP/1.1 404 Not Found");
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style="font-family:sans-serif;text-align:center;padding:100px 20px;color:#333;"><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p><hr style="max-width:500px;margin:20px auto;"><address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address></body></html>';
    exit();
}

if (is_logged_in()) {
    header("Location: dashboard.php");
} else {
    // Forward the secret parameter to login.php so it remains authorized
    $redirect_url = "login.php";
    if (isset($_GET['secret'])) {
        $redirect_url .= "?secret=" . urlencode($_GET['secret']);
    }
    header("Location: " . $redirect_url);
}
exit();
