<?php
// HR Traders API - Update Customer Profile Info
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required to update profile.']);
    exit();
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($name) || empty($phone) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'All profile fields are required.']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, address = :address WHERE id = :id");
    $result = $stmt->execute([
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'id' => $user_id
    ]);

    if ($result) {
        // Refresh session
        $_SESSION['name'] = $name;
        echo json_encode(['success' => true, 'message' => 'Profile settings updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save changes to profile.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error while saving profile: ' . $e->getMessage()]);
}
exit();
