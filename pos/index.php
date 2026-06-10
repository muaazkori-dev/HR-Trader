<?php
// HR Traders In-store POS Billing Portal
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Restrict access to staff (owner / manager)
require_role(['owner', 'manager']);

// Fetch some quick product shortcuts for fast tapping on the counter
try {
    $stmt = $pdo->query("SELECT id, name, price, barcode FROM products LIMIT 8");
    $quick_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $quick_products = [];
}
// Set HTML class based on theme type (light or dark)
$current_theme = get_setting('active_theme', 'emerald_green');
$dark_themes = ['midnight_indigo', 'cyberpunk_neon', 'deep_purple', 'forest_dark', 'crimson_dark'];
$html_class = in_array($current_theme, $dark_themes) ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $html_class; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Traders - Point Of Sale Counter</title>
    <!-- Define BASE_URL globally for client-side JS fetches -->
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <!-- Local Tailwind script for offline capability -->
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#10b981', // Emerald
                        darkbg: '#090d16',  // Pure dark background
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="theme-<?php echo get_setting('active_theme', 'emerald_green'); ?> bg-slate-50 text-slate-800 min-h-screen flex flex-col overflow-hidden">

<!-- POS HEADER -->
<header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between z-10 flex-shrink-0">
    <div class="flex items-center gap-4">
        <a href="<?php echo BASE_URL; ?>" class="text-xl font-black text-emerald-600 tracking-wider">HR TRADERS <span class="text-xs text-slate-500 font-bold uppercase">POS Counter</span></a>
        <span class="text-xs px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-700 font-semibold rounded-lg">
            Cashier: <?php echo sanitize($_SESSION['name']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)
        </span>
    </div>
    
    <div class="flex items-center gap-3">
        <!-- Display Current Time -->
        <span id="pos-clock" class="text-xs font-mono text-slate-500 hidden md:inline-block">--:--:--</span>
        
        <a href="<?php echo BASE_URL; ?>" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-750 text-xs rounded-xl font-bold border border-slate-300 transition-colors">
            <i class="fas fa-arrow-left"></i> E-commerce Store
        </a>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-750 text-xs rounded-xl font-bold border border-slate-300 transition-colors">
            <i class="fas fa-chart-line"></i> Admin Panel
        </a>
        <a href="<?php echo BASE_URL; ?>logout.php" class="px-3.5 py-1.5 bg-rose-50 border border-rose-200 hover:bg-rose-500 hover:text-white text-rose-600 text-xs rounded-xl font-bold transition-all">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<!-- MAIN SCREEN GRID -->
<div class="flex-1 flex overflow-hidden">
    
    <!-- LEFT PANE: BILLING ITEMS TABLE GRID -->
    <div class="w-full lg:w-3/5 p-4 flex flex-col justify-between border-r border-slate-200 h-full overflow-hidden">
        
        <!-- Table Scrollbox -->
        <div class="flex-1 overflow-y-auto bg-white border border-slate-200 rounded-2xl shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5 pl-5">Product Details</th>
                        <th class="p-3.5">Price</th>
                        <th class="p-3.5 text-center" style="width: 140px;">Quantity</th>
                        <th class="p-3.5 text-right pr-5">Total</th>
                        <th class="p-3.5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="pos-bill-body" class="divide-y divide-slate-200 text-slate-700 text-sm">
                    <!-- Loaded dynamically via JS -->
                    <tr id="empty-cart-row">
                        <td colspan="5" class="py-24 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-barcode text-5xl opacity-20"></i>
                                <p class="text-sm">Scan barcode or type items to begin billing.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- System Message Box -->
        <div id="pos-status-msg" class="mt-4 p-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-600 flex items-center gap-2.5 shadow-sm">
            <i class="fas fa-circle-info text-emerald-600"></i>
            <span>Scanner ready. Point and scan product barcode directly.</span>
        </div>
    </div>

    <!-- RIGHT PANE: LOOKUP, MATHS SUMMARY, AND NUMERIC KEYPAD -->
    <div class="w-2/5 hidden lg:flex flex-col justify-between p-4 h-full overflow-hidden space-y-4">
        
        <!-- Search bar input -->
        <div class="glass-panel bg-white shadow-sm p-4 rounded-2xl border border-slate-200 space-y-3 flex-shrink-0">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Product Search & Scanner Lookup</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                    <i class="fas fa-barcode"></i>
                </span>
                <input type="text" id="pos-search-input" autocomplete="off"
                       class="w-full pl-10 pr-10 py-2.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm text-slate-900 font-semibold font-mono"
                       placeholder="Scan barcode or type name...">
                <button id="clear-search-btn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-405 hover:text-slate-800 hidden">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <!-- Search Suggestion results box -->
            <div id="pos-search-results" class="absolute left-4 right-4 mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl hidden z-50 max-h-60 overflow-y-auto">
                <!-- Dynamically loaded -->
            </div>
        </div>

        <!-- Quick Tapping Keys Grid -->
        <div class="glass-card bg-white shadow-sm p-4 rounded-2xl flex-1 overflow-y-auto space-y-3">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Quick Select Items</h4>
            <div class="grid grid-cols-2 gap-2">
                <?php foreach ($quick_products as $qp): ?>
                    <button onclick="addQuickProduct(<?php echo $qp['id']; ?>, '<?php echo sanitize($qp['name']); ?>', <?php echo $qp['price']; ?>, '<?php echo sanitize($qp['barcode']); ?>')" 
                            class="p-2.5 bg-slate-50 border border-slate-200 text-slate-700 font-semibold rounded-xl text-left hover:border-emerald-500 hover:bg-slate-105 transition-all text-xs truncate">
                        <span class="block truncate"><?php echo sanitize($qp['name']); ?></span>
                        <span class="text-[10px] text-emerald-600 font-bold"><?php echo format_price($qp['price']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pricing Summary Box & Pay Calculator -->
        <div class="glass-panel bg-white shadow-sm p-4 rounded-2xl border border-slate-200 flex-shrink-0 space-y-4">
            
            <!-- Numerical calculations -->
            <div class="grid grid-cols-2 gap-3 text-sm border-b border-slate-200 pb-3">
                <div class="flex flex-col gap-1.5">
                    <span class="text-slate-500 font-medium">Subtotal</span>
                    <strong id="pos-subtotal" class="text-base text-slate-800">Rs. 0.00</strong>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-slate-500 font-medium">Discount (%)</span>
                    <input type="number" id="pos-discount" min="0" max="100" value="0"
                           class="w-full bg-white border border-slate-300 px-2 py-1 rounded-lg text-sm focus:outline-none focus:border-emerald-500 font-bold text-emerald-600">
                </div>
            </div>

            <!-- Net Payable -->
            <div class="flex items-center justify-between text-base font-bold text-slate-800 bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
                <span>Net Amount Due</span>
                <span id="pos-net-payable" class="text-2xl font-black text-emerald-600">Rs. 0.00</span>
            </div>

            <!-- Cash Paid & Change -->
            <div class="grid grid-cols-2 gap-3 text-sm pt-1">
                <div>
                    <span class="block text-slate-500 mb-1 font-medium">Cash Received</span>
                    <input type="number" id="pos-cash-paid" min="0" value="0" step="any"
                           class="w-full bg-white border border-slate-300 px-3 py-1.5 rounded-xl font-bold text-sm focus:outline-none focus:border-emerald-500 font-mono text-emerald-600">
                </div>
                <div>
                    <span class="block text-slate-500 mb-1 font-medium">Change Due</span>
                    <div id="pos-change-due" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-black font-mono text-base text-slate-800">
                        Rs. 0.00
                    </div>
                </div>
            </div>

            <!-- Payment Type -->
            <div class="flex items-center gap-2">
                <select id="pos-payment-method" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none text-xs font-bold text-slate-700">
                    <option value="Cash">Cash Transaction</option>
                    <option value="Card">Credit/Debit Card</option>
                    <option value="Mobile Wallet">Mobile Wallet (EasyPaisa/JazzCash)</option>
                </select>
            </div>

            <!-- Checkout Action Button -->
            <button onclick="checkoutPOS()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-sm transition-all shadow-lg shadow-emerald-600/10 flex items-center justify-center gap-2 uppercase tracking-widest">
                <i class="fas fa-print"></i> Fulfill & Print Receipt
            </button>
        </div>
    </div>
</div>

<!-- Clock Script -->
<script>
    function updateClock() {
        const clock = document.getElementById('pos-clock');
        if (clock) {
            const now = new Date();
            clock.innerText = now.toLocaleTimeString();
        }
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>

<!-- POS billing system controller script -->
<script src="<?php echo BASE_URL; ?>assets/js/pos.js"></script>
</body>
</html>
