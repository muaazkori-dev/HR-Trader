<?php
// HR Traders Central REST API Helpers (v1)
// Common utilities for CORS, JSON responses, Auth Tokens, and Absolute Image URLs

require_once __DIR__ . '/../../config/db.php';

// Enable CORS for mobile apps, emulators, and web preview
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
} else {
    header('Access-Control-Allow-Origin: *');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

/**
 * Send standard JSON response and terminate
 */
function send_json($data, int $status_code = 200): void {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send error JSON response
 */
function send_error(string $message, int $status_code = 400, $errors = null): void {
    $payload = [
        'success' => false,
        'message' => $message
    ];
    if ($errors !== null) {
        $payload['errors'] = $errors;
    }
    send_json($payload, $status_code);
}

/**
 * Send success JSON response
 */
function send_success($data = null, string $message = 'Success'): void {
    $payload = [
        'success' => true,
        'message' => $message
    ];
    if ($data !== null) {
        $payload['data'] = $data;
    }
    send_json($payload, 200);
}

/**
 * Get JSON input body or fallback to $_POST / $_GET
 */
function get_request_data(): array {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return array_merge($_REQUEST, $json);
        }
    }
    return $_REQUEST;
}

/**
 * Generate full URL for product and category images
 */
function get_full_asset_url(?string $path): string {
    if (empty($path)) {
        return 'https://thehrtraders.com/assets/images/placeholder.svg';
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    $clean_path = ltrim($path, '/');
    return 'https://thehrtraders.com/' . $clean_path;
}

/**
 * Simple Token Generation for Mobile Sessions
 */
function generate_mobile_token(int $user_id, string $role): string {
    $secret = 'hrt_mobile_secret_token_key_2026';
    $payload = $user_id . ':' . $role . ':' . (time() + (86400 * 90)); // 90 days validity
    $sig = hash_hmac('sha256', $payload, $secret);
    return base64_encode($payload . ':' . $sig);
}

/**
 * Verify Mobile Bearer Token
 * Returns array with ['user_id', 'role'] or null
 */
function verify_mobile_token(?string $token = null): ?array {
    if (!$token) {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        return null;
    }

    $decoded = base64_decode($token);
    if (!$decoded) return null;

    $parts = explode(':', $decoded);
    if (count($parts) !== 4) return null;

    list($user_id, $role, $expires, $sig) = $parts;

    if (time() > (int)$expires) {
        return null; // Expired
    }

    $secret = 'hrt_mobile_secret_token_key_2026';
    $expected_payload = $user_id . ':' . $role . ':' . $expires;
    $expected_sig = hash_hmac('sha256', $expected_payload, $secret);

    if (hash_equals($expected_sig, $sig)) {
        return [
            'user_id' => (int)$user_id,
            'role' => $role
        ];
    }

    return null;
}

/**
 * Require Authenticated User (Optionally with specific role)
 */
function require_auth(?string $required_role = null): array {
    $user = verify_mobile_token();
    if (!$user) {
        send_error('Unauthorized. Please login.', 401);
    }
    if ($required_role && $user['role'] !== $required_role && $user['role'] !== 'owner') {
        send_error('Forbidden. Insufficient permissions.', 403);
    }
    return $user;
}
