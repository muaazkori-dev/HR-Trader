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

    $clean_email = strtolower(trim($email));

    // Normalize phone to last 10 digits
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean_phone) < 10) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number (at least 10 digits).']);
        exit();
    }
    $last_10_phone = substr($clean_phone, -10);

    try {
        // Check if email already exists case-insensitively
        $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(email)) = :email LIMIT 1");
        $stmt->execute(['email' => $clean_email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please Sign In.']);
            exit();
        }

        // Check if phone already exists by matching last 10 digits
        $stmt = $pdo->prepare("SELECT id, phone FROM users WHERE phone LIKE :phone_pattern");
        $stmt->execute(['phone_pattern' => '%' . $last_10_phone]);
        $matching_phones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matching_phones as $row) {
            $db_phone_clean = preg_replace('/[^0-9]/', '', $row['phone']);
            if (strlen($db_phone_clean) >= 10 && substr($db_phone_clean, -10) === $last_10_phone) {
                echo json_encode(['success' => false, 'message' => 'An account with this phone number already exists. Please Sign In.']);
                exit();
            }
        }

        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $username = $clean_email; // use email as unique username

        // Insert new user
        $stmt_ins = $pdo->prepare("INSERT INTO users (username, password, email, name, phone, role) 
                                   VALUES (:username, :password, :email, :name, :phone, 'customer')");
        $stmt_ins->execute([
            'username' => $username,
            'password' => $hashed_password,
            'email' => $clean_email,
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
        $user = null;
        $identity_clean = trim($identity);

        if (strpos($identity_clean, '@') !== false) {
            // It's an email
            $email_lookup = strtolower($identity_clean);
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(TRIM(email)) = :email OR LOWER(TRIM(username)) = :email LIMIT 1");
            $stmt->execute(['email' => $email_lookup]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // It's a phone number
            $phone_digits = preg_replace('/[^0-9]/', '', $identity_clean);
            if (strlen($phone_digits) >= 10) {
                $last10 = substr($phone_digits, -10);
                $stmt = $pdo->prepare("SELECT * FROM users WHERE phone LIKE :phone_pattern");
                $stmt->execute(['phone_pattern' => '%' . $last10]);
                $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($candidates as $candidate) {
                    $cand_phone_digits = preg_replace('/[^0-9]/', '', $candidate['phone']);
                    if (strlen($cand_phone_digits) >= 10 && substr($cand_phone_digits, -10) === $last10) {
                        $user = $candidate;
                        break;
                    }
                }
            }
        }

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
