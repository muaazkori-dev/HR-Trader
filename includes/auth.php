<?php
// HR Traders Authentication and Security Manager
// Handles session validation and role-based access control (RBAC)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is authenticated
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

/**
 * Redirect immediately if the user is not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        $login_url = (defined('BASE_URL') ? BASE_URL : '/') . 'admin/login.php';
        header("Location: " . $login_url . "?error=" . urlencode("Please log in to access this page."));
        exit();
    }
}

/**
 * Restrict access to specific roles. Redirects if unauthorized.
 * @param array $allowed_roles
 */
function require_role($allowed_roles) {
    require_login();
    
    $user_role = $_SESSION['role'];
    if (!in_array($user_role, $allowed_roles)) {
        // Log unauthorized attempt if necessary
        $login_url = (defined('BASE_URL') ? BASE_URL : '/') . 'admin/login.php';
        header("Location: " . $login_url . "?error=" . urlencode("Access Denied: You do not have the required permissions."));
        exit();
    }
}

/**
 * Check if current user is Owner
 * @return bool
 */
function is_owner() {
    return is_logged_in() && $_SESSION['role'] === 'owner';
}

/**
 * Check if current user is Manager
 * @return bool
 */
function is_manager() {
    return is_logged_in() && $_SESSION['role'] === 'manager';
}

/**
 * Register a user session on successful login
 * @param array $user Data row from users table
 */
function login_user($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
}

/**
 * Destroys session and logs out
 */
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
