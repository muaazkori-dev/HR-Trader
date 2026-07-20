<?php
// HR Traders Manager/Staff Order Fulfillment Desk
// Restricted focusing purely on order packaging, dispatch, and delivery updates

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce staff access (owner & manager)
require_role(['owner', 'manager']);

// Fetch orders filter state
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$valid_statuses = ['pending', 'packaging', 'out_for_delivery', 'delivered', 'cancelled'];

try {
    if (in_array($filter_status, $valid_statuses)) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE status = :status ORDER BY id DESC");
        $stmt->execute(['status' => $filter_status]);
    } else {
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
        $filter_status = ''; // default all
    }
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

// Fetch dynamic WhatsApp dispatch template
$whatsapp_template = get_setting('whatsapp_dispatch_template', 'Hi {name}, your order #{ref} has been dispatched! Total Invoice: {total}. Delivery Address: {address}. Thank you for shopping with HR Traders!');
$current_theme = get_setting('active_theme', 'emerald_green');
$dark_themes = ['midnight_indigo', 'cyberpunk_neon', 'deep_purple', 'forest_dark', 'forest_green', 'crimson_dark', 'crimson_rose'];
$html_class = in_array($current_theme, $dark_themes) ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $html_class; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
    <title>HR Traders - Manager Fulfillment Panel</title>
    <!-- Define BASE_URL globally for client-side JS AJAX fetches -->
    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
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
                        darkbg: '#090d16',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.3">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/all.min.css">
</head>
<body class="theme-<?php echo get_setting('active_theme', 'emerald_green'); ?> bg-slate-50 text-slate-800 min-h-screen flex flex-col">

<!-- STICKY HEADER -->
<header class="bg-white border-b border-slate-200 px-4 py-3 md:px-6 md:py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3 z-10 flex-shrink-0">
    <div class="flex items-center justify-between w-full md:w-auto">
        <span class="text-base md:text-lg font-black text-emerald-600 tracking-wider">
            HR TRADERS <span class="text-[10px] md:text-xs text-slate-500 font-bold uppercase">Staff Panel</span>
        </span>
        <!-- Mobile Logout button -->
        <a href="<?php echo BASE_URL; ?>logout.php" class="md:hidden px-2.5 py-1.5 bg-rose-50 border border-rose-200 hover:bg-rose-500 hover:text-white text-rose-600 text-xs rounded-xl font-bold transition-all">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
    
    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto pb-1 md:pb-0">
        <span class="text-[10px] md:text-xs px-2.5 py-1.5 bg-slate-100 border border-slate-200 text-slate-700 font-semibold rounded-xl flex-shrink-0">
            Cashier Desk: <?php echo sanitize($_SESSION['name']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)
        </span>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] md:text-xs rounded-xl font-bold border border-slate-300 transition-colors flex-shrink-0">
            <i class="fas fa-chart-line"></i> <?php echo is_owner() ? 'Owner Dashboard' : 'Manager Dashboard'; ?>
        </a>
        <!-- Desktop Logout button -->
        <a href="<?php echo BASE_URL; ?>logout.php" class="hidden md:flex px-3.5 py-1.5 bg-rose-50 border border-rose-200 hover:bg-rose-500 hover:text-white text-rose-600 text-xs rounded-xl font-bold transition-all flex-shrink-0">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </a>
    </div>
</header>

<main class="flex-1 max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 w-full">
    
    <!-- Title Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Fulfillment Desk</h1>
            <p class="text-xs text-slate-500 mt-1">Monitor, pack, and mark delivery status for online customer orders</p>
        </div>

        <!-- Filter tabs -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?php echo BASE_URL; ?>admin/manager.php" class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all <?php echo empty($filter_status) ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/10' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'; ?>">
                All Orders
            </a>
            <?php foreach ($valid_statuses as $st): ?>
                <a href="<?php echo BASE_URL; ?>admin/manager.php?status=<?php echo $st; ?>" class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all <?php echo $filter_status === $st ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/10' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $st)); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ORDERS QUEUE LIST -->
    <div class="space-y-4">
        <?php if (empty($orders)): ?>
            <div class="glass-panel py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <i class="fas fa-truck-ramp-box text-5xl mb-4 opacity-25 animate-pulse text-slate-300"></i>
                <h3 class="font-bold text-slate-650 text-base">No orders in queue</h3>
                <p class="text-xs mt-1 text-slate-400">There are no incoming customer orders matching this filter.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $ord): ?>
                <?php 
                $ref = "#HRT-" . str_pad($ord['id'], 5, '0', STR_PAD_LEFT);
                $is_pending = $ord['status'] === 'pending';
                $is_packaging = $ord['status'] === 'packaging';
                $is_shipping = $ord['status'] === 'out_for_delivery';
                $is_delivered = $ord['status'] === 'delivered';
                $is_cancelled = $ord['status'] === 'cancelled';
                ?>
                <!-- ORDER ROW CARD -->
                <div class="glass-panel bg-white shadow-sm p-5 rounded-2xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-5 transition-all hover:border-slate-300">
                    
                    <!-- Customer and details columns -->
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm font-bold text-slate-800"><?php echo $ref; ?></span>
                            <!-- Status pills -->
                            <span class="px-2.5 py-0.5 rounded text-[10px] uppercase font-black border <?php 
                                switch($ord['status']) {
                                    case 'pending': echo 'bg-amber-50 text-amber-700 border-amber-200'; break;
                                    case 'packaging': echo 'bg-blue-50 text-blue-700 border-blue-200'; break;
                                    case 'out_for_delivery': echo 'bg-purple-50 text-purple-700 border-purple-200'; break;
                                    case 'delivered': echo 'bg-emerald-50 text-emerald-700 border-emerald-200'; break;
                                    case 'cancelled': echo 'bg-rose-50 text-rose-700 border-rose-200'; break;
                                }
                            ?>">
                                <?php echo str_replace('_', ' ', $ord['status']); ?>
                            </span>
                            <span class="text-xs text-slate-400 font-medium"><?php echo date('d-M-Y h:i A', strtotime($ord['created_at'])); ?></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs text-slate-600 pt-1">
                            <div>
                                <span class="text-slate-400 block uppercase font-semibold">Recipient</span>
                                <strong class="text-slate-800"><?php echo sanitize($ord['customer_name']); ?></strong>
                            </div>
                             <div>
                                <span class="text-slate-400 block uppercase font-semibold">Contact</span>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="font-mono text-slate-700"><?php echo sanitize($ord['customer_phone']); ?></span>
                                    <button onclick="sendWhatsAppNotification('<?php echo rawurlencode(sanitize($ord['customer_name'])); ?>', '<?php echo $ref; ?>', '<?php echo rawurlencode(sanitize(format_price($ord['total_amount']))); ?>', '<?php echo rawurlencode(sanitize($ord['customer_address'])); ?>', '<?php echo sanitize($ord['customer_phone']); ?>')"
                                            class="px-1.5 py-0.5 bg-emerald-50 hover:bg-emerald-500 hover:text-white text-emerald-600 border border-emerald-250 text-[9px] font-bold rounded flex items-center gap-0.5 transition-all cursor-pointer"
                                            title="Send WhatsApp dispatch notification">
                                        <i class="fab fa-whatsapp"></i> Alert
                                    </button>
                                </div>
                            </div>
                            <div class="col-span-1 sm:col-span-2 md:col-span-1">
                                <span class="text-slate-400 block uppercase font-semibold">Address</span>
                                <span class="truncate block max-w-xs text-slate-700" title="<?php echo sanitize($ord['customer_address']); ?>">
                                    <?php echo sanitize($ord['customer_address']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Mini Items listing preview -->
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 mt-2 text-xs text-slate-700">
                            <span class="font-bold text-slate-500 block mb-1">Purchased Gird:</span>
                            <?php
                            $stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :id");
                            $stmt_items->execute(['id' => $ord['id']]);
                            $items = $stmt_items->fetchAll();
                            $items_arr = [];
                            foreach ($items as $it) {
                                $items_arr[] = sanitize($it['name']) . " (x" . $it['quantity'] . ")";
                            }
                            echo implode(', ', $items_arr);
                            ?>
                        </div>
                    </div>

                    <!-- Pricing & Quick status actions column -->
                    <div class="flex flex-col sm:flex-row md:flex-col items-stretch sm:items-center md:items-end justify-between gap-4 border-t md:border-t-0 md:border-l border-slate-200 pt-4 md:pt-0 md:pl-6 md:w-64">
                        <div class="text-left md:text-right">
                            <span class="text-[10px] text-slate-450 uppercase font-semibold block">Total Invoice</span>
                            <span class="text-lg font-black text-emerald-600"><?php echo format_price($ord['total_amount']); ?></span>
                        </div>

                        <div class="flex flex-wrap gap-2 w-full md:justify-end">
                            <?php if ($is_pending): ?>
                                <button onclick="updateOrderStatus(<?php echo $ord['id']; ?>, 'packaging')" 
                                        class="flex-1 sm:flex-initial px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition-colors">
                                    Start Packaging
                                </button>
                                <button onclick="updateOrderStatus(<?php echo $ord['id']; ?>, 'cancelled')" 
                                        class="px-3 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 hover:border-rose-300 text-xs rounded-lg transition-colors">
                                    Cancel
                                </button>
                            <?php elseif ($is_packaging): ?>
                                <button onclick="updateOrderStatus(<?php echo $ord['id']; ?>, 'out_for_delivery')" 
                                        class="flex-1 sm:flex-initial px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-lg transition-colors">
                                    Dispatch / Ship
                                </button>
                                <button onclick="updateOrderStatus(<?php echo $ord['id']; ?>, 'cancelled')" 
                                        class="px-3 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 hover:border-rose-300 text-xs rounded-lg transition-colors">
                                    Cancel
                                </button>
                            <?php elseif ($is_shipping): ?>
                                <button onclick="updateOrderStatus(<?php echo $ord['id']; ?>, 'delivered')" 
                                        class="flex-1 sm:flex-initial px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition-colors">
                                    Mark Delivered
                                </button>
                                <button onclick="updateOrderStatus(<?php echo $ord['id']; ?>, 'cancelled')" 
                                        class="px-3 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 hover:border-rose-300 text-xs rounded-lg transition-colors">
                                    Cancel
                                </button>
                            <?php elseif ($is_delivered): ?>
                                <span class="text-emerald-600 text-xs font-bold py-1 flex items-center gap-1">
                                    <i class="fas fa-circle-check"></i> Order Fulfill Completed
                                </span>
                            <?php elseif ($is_cancelled): ?>
                                <span class="text-rose-600 text-xs font-bold py-1 flex items-center gap-1">
                                    <i class="fas fa-ban"></i> Order Cancelled & Stock Synced
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Toasts indicator -->
<div id="toast-container" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none"></div>

<!-- AJAX Status Updater Script -->
<script>
function updateOrderStatus(orderId, status) {
    let confirmMsg = "";
    if (status === 'delivered') {
        confirmMsg = "Mark order as Delivered? This will sync transaction profit records and close order.";
    } else if (status === 'cancelled') {
        confirmMsg = "Are you sure you want to Cancel this order? This will return purchased items back to store stock.";
    }

    if (confirmMsg && !confirm(confirmMsg)) {
        return;
    }

    // Call update API
    fetch(BASE_URL + 'admin/api/update_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: orderId, status: status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Refresh table details after 1s
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message || 'Failed to update order.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Network error, please try again.', 'error');
    });
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast-msg pointer-events-auto p-4 rounded-xl shadow-2xl flex items-center gap-3 border text-sm ';
    
    if (type === 'success') {
        toast.className += 'bg-white border-emerald-250 text-emerald-700 shadow-xl';
        toast.innerHTML = `<i class="fas fa-check-circle text-emerald-650 text-base"></i> <span>${message}</span>`;
    } else {
        toast.className += 'bg-white border-rose-250 text-rose-700 shadow-xl';
        toast.innerHTML = `<i class="fas fa-times-circle text-rose-650 text-base"></i> <span>${message}</span>`;
    }
    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function sendWhatsAppNotification(name, ref, total, address, phone) {
    // Decode URL components safely
    name = decodeURIComponent(name);
    total = decodeURIComponent(total);
    address = decodeURIComponent(address);
    
    // Normalize phone number (replace leading 0 with 92 for Pakistan)
    let formattedPhone = phone.trim().replace(/\D/g, ''); 
    if (formattedPhone.startsWith('0')) {
        formattedPhone = '92' + formattedPhone.substring(1);
    } else if (formattedPhone.length === 10 && !formattedPhone.startsWith('92')) {
        formattedPhone = '92' + formattedPhone;
    }
    
    // Load PHP-injected WhatsApp dispatch template
    let template = `<?php echo addslashes($whatsapp_template); ?>`;
    
    // Perform dynamic variable interpolation
    let message = template
        .replace(/{name}/g, name)
        .replace(/{ref}/g, ref)
        .replace(/{total}/g, total)
        .replace(/{address}/g, address);
        
    // Launch WhatsApp Web/App window
    let url = 'https://wa.me/' + formattedPhone + '?text=' + encodeURIComponent(message);
    window.open(url, '_blank');
}
</script>

<?php
$supabase_url = 'https://placeholder-xarwwlbbaevclyljkvzt.supabase.co';
$supabase_key = 'placeholder-anon-key-WUAhugqCtckYHXxcQNg';

$env_file = __DIR__ . '/../next-store/.env.local';
if (file_exists($env_file)) {
    $env_content = file_get_contents($env_file);
    if ($env_content) {
        $lines = explode("\n", $env_content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim(trim($parts[1]), '"\'');
                if ($key === 'NEXT_PUBLIC_SUPABASE_URL') {
                    $supabase_url = $val;
                } elseif ($key === 'NEXT_PUBLIC_SUPABASE_ANON_KEY') {
                    $supabase_key = $val;
                }
            }
        }
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    try {
        const sbUrl = "<?php echo $supabase_url; ?>";
        const sbKey = "<?php echo $supabase_key; ?>";
        if (!sbUrl || !sbKey || sbUrl.includes('placeholder')) return;
        
        const supabaseClient = window.supabase.createClient(sbUrl, sbKey);
        
        function playChime() {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return;
                const ctx = new AudioContextClass();
                
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, ctx.currentTime);
                gain1.gain.setValueAtTime(0.08, ctx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start();
                osc1.stop(ctx.currentTime + 0.4);

                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, ctx.currentTime + 0.12);
                gain2.gain.setValueAtTime(0.08, ctx.currentTime + 0.12);
                gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.62);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(ctx.currentTime + 0.12);
                osc2.stop(ctx.currentTime + 0.62);
            } catch (e) {
                console.error(e);
            }
        }

        function showOrderToast(orderId, name, amount) {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'fixed bottom-10 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto p-4 rounded-xl shadow-2xl flex flex-col gap-2 border bg-slate-900 text-white text-sm max-w-sm w-full animate-bounce';
            toast.style.borderColor = '#1e293b';
            
            toast.innerHTML = `
                <div class="flex items-start justify-between gap-3 text-left">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🔔</span>
                        <div>
                            <strong class="text-emerald-500 font-extrabold block text-xs uppercase">New Order Received!</strong>
                            <span class="font-bold text-xs block mt-0.5">Order #HRT-${String(orderId).padStart(5, '0')}</span>
                        </div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-slate-400 hover:text-white font-bold">&times;</button>
                </div>
                <div class="text-[11px] text-slate-300 leading-normal text-left">
                    New order placed by <strong>${name}</strong> for <strong>Rs. ${parseFloat(amount).toFixed(0)}</strong>.
                </div>
                <div class="flex gap-2 mt-1">
                    <a href="manager.php" class="flex-grow py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center text-[10px] uppercase rounded-lg">View Queue</a>
                    <button onclick="this.parentElement.parentElement.remove()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-350 text-[10px] font-bold uppercase rounded-lg">Dismiss</button>
                </div>
            `;
            container.appendChild(toast);
        }

        supabaseClient
            .channel('schema-insert-realtime-php')
            .on(
                'postgres_changes',
                {
                    event: 'INSERT',
                    schema: 'public',
                    table: 'orders'
                },
                (payload) => {
                    if (payload.new) {
                        const newOrder = payload.new;
                        showOrderToast(newOrder.id, newOrder.customer_name || 'Guest', newOrder.total_amount || 0);
                        playChime();
                    }
                }
            )
            .subscribe();

    } catch (err) {
        console.error(err);
    }
});
</script>
</body>
</html>
