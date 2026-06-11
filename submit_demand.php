<?php
// HR Traders E-commerce Submit Product Demand Handler
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$details = trim($_POST['details'] ?? '');

if (empty($name) || empty($phone) || empty($details)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO product_demands (customer_name, customer_phone, demand_details, status) VALUES (:name, :phone, :details, 'pending')");
    $stmt->execute([
        'name' => $name,
        'phone' => $phone,
        'details' => $details
    ]);
    $demand_id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'id' => $demand_id, 'message' => 'Aapki demand kamyabi se darj kar li gayi hai! Hum jald hi is item ka intezam karenge.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
