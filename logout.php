<?php
// HR Traders Logout Handler (Staff & Customers)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$is_customer = isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

logout_user();

if ($is_customer) {
    header("Location: " . BASE_URL);
} else {
    header("Location: " . BASE_URL . "admin/login.php");
}
exit();
