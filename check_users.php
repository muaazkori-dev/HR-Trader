<?php
// HR Traders - Temporary Database User Inspector & Password Reset Utility
require_once __DIR__ . '/config/db.php';

$message = "";
$status = "";

// Handle Password Reset Request
if (isset($_POST['reset_passwords'])) {
    try {
        $hashed_owner = password_hash('Owner124421', PASSWORD_DEFAULT);
        $hashed_manager = password_hash('Manager124421', PASSWORD_DEFAULT);
        
        // Check if owner exists, update or insert
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'owner'");
        $stmt->execute();
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE users SET password = :pass, role = 'owner', name = 'Owner Admin' WHERE username = 'owner'");
            $stmt->execute(['pass' => $hashed_owner]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, phone, address) VALUES ('owner', :pass, 'owner', 'Owner Admin', '03033943814', 'Tando Adam')");
            $stmt->execute(['pass' => $hashed_owner]);
        }

        // Check if manager exists, update or insert
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'manager'");
        $stmt->execute();
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE users SET password = :pass, role = 'manager', name = 'Store Manager' WHERE username = 'manager'");
            $stmt->execute(['pass' => $hashed_manager]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, phone, address) VALUES ('manager', :pass, 'manager', 'Store Manager', '03217654321', 'Lahore')");
            $stmt->execute(['pass' => $hashed_manager]);
        }

        // Reset secret key to default
        $stmt_secret = $pdo->prepare("INSERT INTO settings (key_name, val_value) VALUES ('admin_secret_key', 'hr_secure_desk_99') 
                                       ON DUPLICATE KEY UPDATE val_value = VALUES(val_value)");
        $stmt_secret->execute();

        $status = "success";
        $message = "Passwords successfully updated: owner is set to <strong>Owner124421</strong> and manager is set to <strong>Manager124421</strong>. Admin Secret Key reset to: <strong>hr_secure_desk_99</strong>";
    } catch (PDOException $e) {
        $status = "error";
        $message = "Failed to reset passwords: " . $e->getMessage();
    }
}

// Fetch current users list
$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, role, name, phone, address FROM users ORDER BY role ASC, username ASC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $status = "error";
    $message = "Failed to fetch users list. Has the database been installed? " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Traders - DB User Inspector</title>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10b981',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: radial-gradient(circle at top, #0f172a 0%, #020617 100%);
        }
    </style>
</head>
<body class="text-slate-100 min-h-screen flex items-center justify-center p-6">

<div class="max-w-2xl w-full bg-slate-900/60 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6">
    
    <div class="text-center">
        <h1 class="text-2xl font-black tracking-wider text-emerald-500 uppercase">HR Traders</h1>
        <p class="text-xs text-slate-400 mt-1">Database User Accounts Inspector &amp; Passwords Utility</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="p-4 rounded-2xl border text-sm <?php echo $status === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-450'; ?>">
            <i class="fas <?php echo $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mr-2"></i>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <!-- ADMIN SECRET KEY STATUS -->
    <?php
    $current_secret = 'hr_secure_desk_99';
    try {
        $stmt_sec = $pdo->prepare("SELECT val_value FROM settings WHERE key_name = 'admin_secret_key' LIMIT 1");
        $stmt_sec->execute();
        $row_sec = $stmt_sec->fetch();
        if ($row_sec) {
            $current_secret = $row_sec['val_value'];
        }
    } catch (PDOException $e) {}
    ?>
    <div class="bg-slate-950/20 border border-slate-800 rounded-2xl p-4 text-xs space-y-1.5">
        <span class="text-slate-400 font-bold uppercase tracking-wider block">Admin Security Status</span>
        <div class="flex justify-between items-center">
            <span class="text-slate-300">Secret URL Key:</span>
            <strong class="font-mono text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded border border-emerald-500/20"><?php echo htmlspecialchars($current_secret); ?></strong>
        </div>
        <div class="pt-1 text-[10px] text-slate-500">
            Access Link: <a href="<?php echo BASE_URL; ?>admin/?secret=<?php echo urlencode($current_secret); ?>" target="_blank" class="underline text-emerald-500 font-mono"><?php echo BASE_URL; ?>admin/?secret=<?php echo htmlspecialchars($current_secret); ?></a>
        </div>
    </div>

    <!-- USERS LIST -->
    <div class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Registered Accounts in Database</h3>
        
        <?php if (empty($users)): ?>
            <div class="p-6 text-center text-slate-500 border border-slate-800 rounded-2xl bg-slate-950/40">
                <i class="fas fa-users-slash text-3xl mb-2 block opacity-40"></i>
                No users found. Please run <a href="install.php" class="text-emerald-400 underline">install.php</a> first to seed default users.
            </div>
        <?php else: ?>
            <div class="overflow-hidden border border-slate-800 rounded-2xl bg-slate-950/30 divide-y divide-slate-850">
                <?php foreach ($users as $u): ?>
                    <div class="p-4 flex items-center justify-between hover:bg-slate-900/40 transition-colors">
                        <div>
                            <span class="font-bold text-sm text-slate-200"><?php echo htmlspecialchars($u['name']); ?></span>
                            <span class="text-xs text-slate-500 block">Username: <strong class="font-mono text-emerald-450"><?php echo htmlspecialchars($u['username']); ?></strong></span>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php
                                switch($u['role']) {
                                    case 'owner': echo 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'; break;
                                    case 'manager': echo 'bg-blue-500/10 text-blue-400 border-blue-500/20'; break;
                                    default: echo 'bg-slate-500/10 text-slate-400 border-slate-500/20'; break;
                                }
                            ?>">
                                <?php echo htmlspecialchars($u['role']); ?>
                            </span>
                            <span class="block text-[10px] text-slate-500 mt-1">ID: #<?php echo $u['id']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ACTION CONTROL -->
    <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row gap-3">
        <form action="" method="POST" class="flex-1">
            <button type="submit" name="reset_passwords" 
                    class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-bold text-xs rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/20">
                <i class="fas fa-key"></i> Set Owner &amp; Manager Passwords
            </button>
        </form>
        <a href="<?php echo BASE_URL; ?>admin/login.php" 
           class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 active:scale-98 text-slate-300 font-bold text-xs rounded-xl uppercase tracking-wider text-center transition-all flex items-center justify-center">
            Go to Login
        </a>
    </div>

</div>

</body>
</html>
