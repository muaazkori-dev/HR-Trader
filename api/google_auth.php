<?php
// HR Traders E-commerce Google Authentication Handler API
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get POST dynamic parameters
$credential = isset($_POST['credential']) ? trim($_POST['credential']) : '';

if (empty($credential)) {
    echo json_encode(['success' => false, 'message' => 'Google credentials token is missing.']);
    exit();
}

// Fetch configured Google Client ID
$google_client_id = get_setting('google_client_id', '');
$google_auth_enabled = get_setting('google_auth_enabled', '0');

if ($google_auth_enabled !== '1' || empty($google_client_id)) {
    echo json_encode(['success' => false, 'message' => 'Google Sign-In is currently disabled.']);
    exit();
}

// Verify Google ID Token using official Google OAuth Tokeninfo API via cURL
$verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($credential);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verify_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response_data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response_data) {
    echo json_encode(['success' => false, 'message' => 'Failed to verify Google token with authentication servers.']);
    exit();
}

$payload = json_decode($response_data, true);

if (!$payload || isset($payload['error'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Google ID token payload.']);
    exit();
}

// Security validations: Check client audience matches our configured Google Client ID
$audience = isset($payload['aud']) ? $payload['aud'] : '';
if ($audience !== $google_client_id) {
    echo json_encode(['success' => false, 'message' => 'Security check failed: Client ID audience mismatch.']);
    exit();
}

// Ensure email is verified
$email_verified = isset($payload['email_verified']) ? $payload['email_verified'] : false;
if (!$email_verified) {
    echo json_encode(['success' => false, 'message' => 'Google account email is not verified.']);
    exit();
}

$google_id = trim($payload['sub']);
$email = trim($payload['email']);
$name = trim($payload['name'] ?? '');

if (empty($google_id) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Profile details are incomplete from Google auth response.']);
    exit();
}

try {
    // 1. Search for existing user with this google_id
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = :google_id LIMIT 1");
    $stmt->execute(['google_id' => $google_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // 2. Search for existing user matching email but no google_id yet
        $stmt_email = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt_email->execute(['email' => $email]);
        $user = $stmt_email->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Bind google_id to existing account
            $stmt_bind = $pdo->prepare("UPDATE users SET google_id = :google_id WHERE id = :id");
            $stmt_bind->execute(['google_id' => $google_id, 'id' => $user['id']]);
            // refresh data
            $user['google_id'] = $google_id;
        } else {
            // 3. Create a new user account
            $username = 'google_' . substr($google_id, 0, 10);
            
            // Check for username collision (extremely rare with Google sub ID, but safe)
            $stmt_chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt_chk->execute(['username' => $username]);
            if ($stmt_chk->fetchColumn() > 0) {
                $username .= '_' . rand(100, 999);
            }

            $stmt_ins = $pdo->prepare("INSERT INTO users (username, password, google_id, email, name, role) 
                                       VALUES (:username, NULL, :google_id, :email, :name, 'customer')");
            $stmt_ins->execute([
                'username' => $username,
                'google_id' => $google_id,
                'email' => $email,
                'name' => $name
            ]);

            $new_user_id = $pdo->lastInsertId();

            // Fetch newly inserted user details
            $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt_user->execute(['id' => $new_user_id]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        }
    }

    // Register Session Variables
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    echo json_encode([
        'success' => true,
        'message' => 'Logged in successfully via Google.',
        'user' => [
            'name' => $user['name'],
            'email' => $user['email']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error during authentication: ' . $e->getMessage()]);
}
exit();
