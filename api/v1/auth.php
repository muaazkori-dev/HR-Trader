<?php
// HR Traders Mobile Authentication Endpoint (v1)
// Handles Registration, Login, Profile, and FCM Push Token saving

require_once __DIR__ . '/helpers.php';

$data = get_request_data();
$action = $data['action'] ?? ($_GET['action'] ?? 'login');

switch ($action) {
    case 'register':
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $password = trim($data['password'] ?? '');
        $fcm_token = trim($data['fcm_token'] ?? '');

        if (empty($name) || empty($phone) || empty($password)) {
            send_error('Name, phone number, and password are required.');
        }

        // Clean phone number
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean_phone) < 10) {
            send_error('Please enter a valid Pakistani mobile number (e.g. 03033943814).');
        }

        // Check if phone/username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone OR username = :username LIMIT 1");
        $stmt->execute(['phone' => $phone, 'username' => $clean_phone]);
        if ($stmt->fetch()) {
            send_error('An account with this phone number already exists. Please login.');
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, phone, address, fcm_token) 
                                   VALUES (:username, :password, 'customer', :name, :phone, :address, :fcm_token)");
            $stmt->execute([
                'username' => $clean_phone,
                'password' => $password_hash,
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
                'fcm_token' => !empty($fcm_token) ? $fcm_token : null
            ]);
            $user_id = (int)$pdo->lastInsertId();

            $token = generate_mobile_token($user_id, 'customer');

            send_success([
                'token' => $token,
                'user' => [
                    'id' => $user_id,
                    'name' => $name,
                    'phone' => $phone,
                    'role' => 'customer',
                    'address' => $address
                ]
            ], 'Account created successfully.');
        } catch (PDOException $e) {
            send_error('Registration failed: ' . $e->getMessage(), 500);
        }
        break;

    case 'login':
        $identifier = trim($data['identifier'] ?? ($data['phone'] ?? ($data['username'] ?? '')));
        $password = trim($data['password'] ?? '');
        $fcm_token = trim($data['fcm_token'] ?? '');

        if (empty($identifier) || empty($password)) {
            send_error('Phone number/username and password are required.');
        }

        $clean_ident = preg_replace('/[^0-9]/', '', $identifier);

        // Find user by username or phone
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :ident OR phone = :ident OR username = :clean OR phone = :clean LIMIT 1");
        $stmt->execute(['ident' => $identifier, 'clean' => $clean_ident]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            send_error('Invalid phone number or password.', 401);
        }

        // Update FCM token if provided
        if (!empty($fcm_token)) {
            try {
                $upd = $pdo->prepare("UPDATE users SET fcm_token = :fcm WHERE id = :id");
                $upd->execute(['fcm' => $fcm_token, 'id' => $user['id']]);
            } catch (PDOException $e) {
                // Non-fatal
            }
        }

        $token = generate_mobile_token((int)$user['id'], $user['role']);

        send_success([
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'address' => $user['address'] ?? ''
            ]
        ], 'Logged in successfully.');
        break;

    case 'profile':
        $auth = require_auth();
        $stmt = $pdo->prepare("SELECT id, username, role, name, phone, address, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $auth['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            send_error('User not found.', 404);
        }

        send_success($user);
        break;

    case 'update_profile':
        $auth = require_auth();
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $fcm_token = trim($data['fcm_token'] ?? '');

        if (empty($name) || empty($phone)) {
            send_error('Name and phone are required.');
        }

        $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, address = :address, fcm_token = COALESCE(:fcm, fcm_token) WHERE id = :id");
        $stmt->execute([
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'fcm' => !empty($fcm_token) ? $fcm_token : null,
            'id' => $auth['user_id']
        ]);

        send_success([
            'id' => $auth['user_id'],
            'name' => $name,
            'phone' => $phone,
            'address' => $address
        ], 'Profile updated successfully.');
        break;

    default:
        send_error('Invalid action requested.');
}
