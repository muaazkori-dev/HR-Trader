<?php
// HR Traders Admin Staff Login Portal
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

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

$error_message = isset($_GET['error']) ? sanitize($_GET['error']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                $stored_hash = trim($user['password']);
                if (password_verify($password, $stored_hash)) {
                    // Ensure only staff (owner/manager) logs in here
                    if (in_array($user['role'], ['owner', 'manager'])) {
                        login_user($user);
                        
                        // Redirect both roles to dashboard
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error_message = "Access Denied: Standard customers cannot log in to the staff portal.";
                    }
                } else {
                    // Diagnostic check for truncated or invalid hashes
                    $hash_len = strlen($stored_hash);
                    $is_bcrypt = (substr($stored_hash, 0, 4) === '$2y$' || substr($stored_hash, 0, 4) === '$2a$');
                    if ($is_bcrypt && $hash_len < 60) {
                        $error_message = "Invalid password. (Diagnostic: Stored hash is truncated to " . $hash_len . " chars. BCRYPT hashes must be 60 chars. Please run check_users.php to reset your password).";
                    } elseif (empty($stored_hash)) {
                        $error_message = "Invalid password. (Diagnostic: Stored hash is empty. Please run check_users.php to reset).";
                    } else {
                        $error_message = "Invalid username or password credentials.";
                    }
                }
            } else {
                $error_message = "Invalid username or password credentials.";
            }
        } catch (PDOException $e) {
            $error_message = "Authentication error, please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Traders - Staff Portal Login</title>
    <!-- Local offline Tailwind library -->
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--theme-primary)',
                        emerald: {
                            50: 'var(--theme-emerald-50, #f0fdf4)',
                            100: 'var(--theme-emerald-100, #dcfce7)',
                            200: 'var(--theme-emerald-200, #bbf7d0)',
                            500: 'var(--theme-primary-hover)',
                            600: 'var(--theme-primary)',
                            700: 'var(--theme-primary-hover)',
                            800: 'var(--theme-primary-hover)',
                        },
                        darkbg: '#080c14',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="theme-<?php echo get_setting('active_theme', 'emerald_green'); ?> bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">

<div class="max-w-md w-full glass-panel border border-slate-200 rounded-3xl p-8 shadow-2xl relative overflow-hidden space-y-6">
    
    <!-- Decorative Glow Element -->
    <div class="absolute -top-12 -left-12 w-24 h-24 bg-emerald-500/5 rounded-full blur-xl pointer-events-none"></div>

    <div class="text-center space-y-2">
        <a href="<?php echo BASE_URL; ?>" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">&larr; Back to Shop Front</a>
        <h2 class="text-2xl font-black text-slate-900 uppercase tracking-wider mt-2">HR Traders</h2>
        <p class="text-xs text-slate-500">Authorized Staff Portal (Owner &amp; Manager Logins)</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-600 text-xs flex items-center gap-2">
            <i class="fas fa-triangle-exclamation"></i>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="space-y-4">
        <div>
            <label for="username" class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Username</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                    <i class="fas fa-user-tie"></i>
                </span>
                <input type="text" id="username" name="username" required autocomplete="off"
                       class="w-full pl-10 pr-3 py-2 bg-white border border-slate-350 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-slate-50/50 text-sm text-slate-900 placeholder-slate-400"
                       placeholder="e.g. owner or manager">
            </div>
        </div>

        <div>
            <label for="password" class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                    <i class="fas fa-lock"></i>
                </span>
                <input type="password" id="password" name="password" required
                       class="w-full pl-10 pr-10 py-2 bg-white border border-slate-355 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-slate-50/50 text-sm text-slate-900 placeholder-slate-400"
                       placeholder="••••••••">
                <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                    <i class="fas fa-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" 
                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-505 active:scale-98 text-white font-black rounded-xl text-sm transition-all uppercase tracking-widest pt-3 shadow-lg shadow-emerald-600/10">
            Log In Portal
        </button>
    </form>

    <div class="text-center pt-2">
        <p class="text-[10px] text-slate-500">Notice: Sessions are fully encrypted and tracked. Unauthorized attempts will be logged.</p>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

</body>
</html>
