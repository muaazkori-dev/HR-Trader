<?php
// HR Traders E-commerce Custom Authentication Handler API
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if ($action === 'signup') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($name) || empty($phone) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields (Name, Phone, Email, Password) are required.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
        exit();
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please Sign In.']);
            exit();
        }

        // Check if phone already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone LIMIT 1");
        $stmt->execute(['phone' => $phone]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'An account with this phone number already exists. Please Sign In.']);
            exit();
        }

        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $username = $email; // use email as unique username

        // Insert new user
        $stmt_ins = $pdo->prepare("INSERT INTO users (username, password, email, name, phone, role) 
                                   VALUES (:username, :password, :email, :name, :phone, 'customer')");
        $stmt_ins->execute([
            'username' => $username,
            'password' => $hashed_password,
            'email' => $email,
            'name' => $name,
            'phone' => $phone
        ]);

        $new_user_id = $pdo->lastInsertId();

        // Fetch user details for session
        $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt_user->execute(['id' => $new_user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        // Set session
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully! Welcome to ' . STORE_NAME,
            'user' => [
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error during registration: ' . $e->getMessage()]);
    }
    exit();

} elseif ($action === 'signin') {
    $identity = isset($_POST['identity']) ? trim($_POST['identity']) : ''; // can be email or phone
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($identity) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Both email/phone and password are required.']);
        exit();
    }

    try {
        // Search user by email or phone
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :identity OR phone = :identity LIMIT 1");
        $stmt->execute(['identity' => $identity]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid email/phone or password. Please try again.']);
            exit();
        }

        // Set session
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'message' => 'Logged in successfully. Welcome back, ' . $user['name'],
            'user' => [
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error during login: ' . $e->getMessage()]);
    }
    exit();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
    exit();
}
