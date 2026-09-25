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

                // Fetch items and calculate pricing breakdown
                $stmt_items = $pdo->prepare("SELECT oi.*, COALESCE(oi.product_name, p.name) as display_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :id");
                $stmt_items->execute(['id' => $ord['id']]);
                $items = $stmt_items->fetchAll();
                $items_arr = [];
                $items_subtotal = 0;
                $items_modal_data = [];
                foreach ($items as $it) {
                    $item_name = $it['display_name'] ?? 'Product';
                    $item_price = (float)$it['price'];
                    $item_qty = (int)$it['quantity'];
                    $item_line = $item_price * $item_qty;
                    $items_subtotal += $item_line;
                    $items_arr[] = sanitize($item_name) . " (x" . $item_qty . ")";
                    $items_modal_data[] = [
                        'name' => $item_name,
                        'price' => $item_price,
                        'quantity' => $item_qty,
                        'total' => $item_line
                    ];
                }
                $delivery_charges = max(0, (float)$ord['total_amount'] - $items_subtotal);

                $order_modal_data = [
                    'id' => (int)$ord['id'],
                    'ref' => $ref,
                    'status' => $ord['status'],
                    'created_at' => date('d-M-Y h:i A', strtotime($ord['created_at'])),
                    'customer_name' => $ord['customer_name'],
                    'customer_phone' => $ord['customer_phone'],
                    'customer_address' => $ord['customer_address'],
                    'payment_method' => $ord['payment_method'] ?? 'COD',
                    'notes' => $ord['notes'] ?? '',
                    'items_subtotal' => $items_subtotal,
                    'delivery_charges' => $delivery_charges,
                    'total_amount' => (float)$ord['total_amount'],
                    'items' => $items_modal_data
                ];
                ?>
                <!-- ORDER ROW CARD -->
                <div class="glass-panel bg-white shadow-sm p-5 rounded-2xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-5 transition-all hover:border-slate-300">
                    
                    <!-- Customer and details columns -->
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <!-- Clickable #HRT Order Number -->
                            <button onclick='openOrderDetailsModal(<?php echo htmlspecialchars(json_encode($order_modal_data), ENT_QUOTES, "UTF-8"); ?>)'
                                    class="font-mono text-sm font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 hover:border-emerald-400 border border-emerald-200 px-2.5 py-1 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer shadow-xs active:scale-95 group"
                                    title="Click to view complete order details & invoice slip">
                                <span class="group-hover:underline underline-offset-2"><?php echo $ref; ?></span>
                                <i class="fas fa-eye text-emerald-600 group-hover:scale-110 transition-transform"></i>
                            </button>

                            <!-- Quick Print Thermal Slip Button -->
                            <button onclick='printThermalReceiptDirect(<?php echo htmlspecialchars(json_encode($order_modal_data), ENT_QUOTES, "UTF-8"); ?>)'
                                    class="px-2 py-1 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 text-slate-650 border border-slate-250 rounded-lg flex items-center gap-1 text-[11px] font-bold transition-all cursor-pointer active:scale-95"
                                    title="Print on Cashier 80mm/58mm Thermal Printer">
                                <i class="fas fa-print text-emerald-600"></i>
                                <span class="hidden sm:inline">Print Slip</span>
                            </button>

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

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-slate-600 pt-1">
                            <div>
                                <span class="text-slate-400 block uppercase font-semibold">Recipient</span>
                                <strong class="text-slate-800 text-[13px]"><?php echo sanitize($ord['customer_name']); ?></strong>
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
                        </div>

                        <!-- Full Complete Delivery Address -->
                        <div class="text-xs text-left mt-2">
                            <span class="text-slate-400 block uppercase font-semibold text-[10px] mb-0.5">Complete Delivery Address</span>
                            <p class="text-slate-800 font-medium whitespace-normal break-words leading-relaxed bg-amber-50/40 p-2.5 rounded-xl border border-amber-200/50 select-text">
                                📍 <?php echo sanitize($ord['customer_address']); ?>
                            </p>
                        </div>

                        <!-- Mini Items listing preview -->
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 mt-2 text-xs text-slate-700">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-slate-500">Purchased Items:</span>
                                <button onclick='openOrderDetailsModal(<?php echo htmlspecialchars(json_encode($order_modal_data), ENT_QUOTES, "UTF-8"); ?>)'
                                        class="text-[11px] text-emerald-600 hover:text-emerald-700 font-bold hover:underline cursor-pointer">
                                    View Full Details →
                                </button>
                            </div>
                            <span class="font-medium text-slate-800 leading-relaxed"><?php echo !empty($items_arr) ? implode(', ', $items_arr) : 'No items'; ?></span>
                        </div>
                    </div>

                    <!-- Pricing & Quick status actions column -->
                    <div class="flex flex-col sm:flex-row md:flex-col items-stretch sm:items-center md:items-end justify-between gap-4 border-t md:border-t-0 md:border-l border-slate-200 pt-4 md:pt-0 md:pl-6 md:w-64">
                        <div class="text-left md:text-right">
                            <span class="text-[10px] text-slate-400 uppercase font-semibold block tracking-wider">Total Invoice</span>
                            <span class="text-xl font-black text-emerald-600"><?php echo format_price($ord['total_amount']); ?></span>
                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                                <span>Items: <?php echo format_price($items_subtotal); ?></span>
                                <span class="text-slate-300">•</span>
                                <span class="<?php echo $delivery_charges > 0 ? 'text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/60' : 'text-slate-500 font-medium'; ?>">
                                    Delivery: <?php echo $delivery_charges > 0 ? '+ ' . format_price($delivery_charges) : 'Free'; ?>
                                </span>
                            </div>
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

<!-- Complete Order Details & Invoice Slip Modal -->
<div id="order-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-5 bg-slate-900/60 backdrop-blur-sm overflow-y-auto" onclick="if(event.target === this) closeOrderDetailsModal()">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col my-auto max-h-[92vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between gap-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-500/20 text-emerald-400 rounded-xl border border-emerald-500/30">
                    <i class="fas fa-file-invoice text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 id="modal-order-ref" class="text-lg font-black tracking-tight font-mono">#HRT-00000</h2>
                        <span id="modal-order-status" class="px-2 py-0.5 rounded text-[10px] uppercase font-black border">PENDING</span>
                    </div>
                    <p id="modal-order-subtitle" class="text-xs text-slate-400 mt-0.5">Order Invoice & Customer Slip</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="printThermalReceiptModal()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg flex items-center gap-1.5 transition-all cursor-pointer active:scale-95 shadow-xs" title="Print on Cashier Thermal Printer (80mm/58mm)">
                    <i class="fas fa-print"></i> <span>Print Thermal Slip</span>
                </button>
                <button onclick="closeOrderDetailsModal()" class="p-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition-colors cursor-pointer text-lg leading-none">
                    &times;
                </button>
            </div>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto space-y-6 flex-1 text-slate-800 text-left">
            <!-- Customer Information Grid -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer & Delivery Details</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block uppercase font-semibold text-[10px]">Customer Name</span>
                        <strong id="modal-customer-name" class="text-slate-900 text-sm">Customer</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block uppercase font-semibold text-[10px]">Phone / Contact</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <a id="modal-customer-phone-link" href="#" class="font-mono text-emerald-700 font-bold hover:underline">03000000000</a>
                            <button id="modal-whatsapp-btn" class="px-2 py-0.5 bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-bold rounded-md flex items-center gap-1 transition-all cursor-pointer">
                                <i class="fab fa-whatsapp"></i> Alert
                            </button>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 block uppercase font-semibold text-[10px]">Payment Method</span>
                        <span id="modal-payment-method" class="font-semibold text-slate-700">Cash on Delivery (COD)</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block uppercase font-semibold text-[10px]">Order Date & Time</span>
                        <span id="modal-order-date" class="font-medium text-slate-700">Date</span>
                    </div>
                </div>

                <!-- Delivery Address Box -->
                <div class="pt-2 border-t border-slate-200">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-slate-400 uppercase font-semibold text-[10px]">Full Delivery Address</span>
                        <button onclick="copyModalAddress()" class="text-[10px] text-emerald-700 hover:text-emerald-800 font-bold flex items-center gap-1 cursor-pointer">
                            <i id="copy-address-icon" class="fas fa-copy"></i> <span id="copy-address-text">Copy Address</span>
                        </button>
                    </div>
                    <div id="modal-customer-address" class="bg-amber-50/60 p-3 rounded-lg border border-amber-200/70 text-xs font-medium text-slate-900 leading-relaxed select-text">
                        📍 Address
                    </div>
                </div>

                <!-- Notes -->
                <div id="modal-notes-container" class="pt-2 border-t border-slate-200 hidden">
                    <span class="text-slate-400 uppercase font-semibold text-[10px] block mb-0.5">Customer Notes</span>
                    <div id="modal-notes" class="bg-blue-50/50 p-2.5 rounded-lg border border-blue-200/60 text-xs text-blue-900"></div>
                </div>
            </div>

            <!-- Ordered Items Table -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                    <i class="fas fa-box text-slate-400"></i>
                    <span>Ordered Items</span>
                </h3>
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3 w-10 text-center">#</th>
                                <th class="py-2.5 px-3">Product Name</th>
                                <th class="py-2.5 px-3 text-right">Price</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="modal-items-tbody" class="divide-y divide-slate-100 bg-white">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pricing & Delivery Charges Breakdown -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-2">
                <div class="flex justify-between items-center text-xs text-slate-600">
                    <span>Items Subtotal</span>
                    <span id="modal-items-subtotal" class="font-mono font-semibold">Rs. 0.00</span>
                </div>

                <!-- Delivery Charges Row -->
                <div class="flex justify-between items-center text-xs p-2.5 rounded-lg bg-emerald-50/80 border border-emerald-200">
                    <span class="font-bold text-emerald-800 flex items-center gap-1.5">
                        <i class="fas fa-truck text-emerald-600"></i>
                        Delivery Charges (شامل شدہ ڈیلیوری چارجز):
                    </span>
                    <span id="modal-delivery-charges" class="font-mono font-bold text-emerald-700 text-sm">+ Rs. 0.00</span>
                </div>

                <div class="pt-2.5 border-t border-slate-200 flex justify-between items-center">
                    <div>
                        <span class="text-xs uppercase font-black text-slate-800 block">Total Bill / Net Payable</span>
                        <span class="text-[10px] text-slate-400">Amount to collect upon delivery (COD)</span>
                    </div>
                    <span id="modal-total-amount" class="text-2xl font-black text-emerald-600 font-mono">Rs. 0.00</span>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-slate-100 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 print:hidden">
            <div id="modal-status-actions" class="flex items-center gap-2 w-full sm:w-auto"></div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button onclick="printThermalReceiptModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg flex items-center gap-1.5 cursor-pointer active:scale-95 shadow-sm" title="Print on Cashier 80mm/58mm Thermal Printer">
                    <i class="fas fa-print"></i> <span>Print Thermal Slip (کیشئر سلپ)</span>
                </button>
                <button onclick="closeOrderDetailsModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- AJAX Status Updater Script -->
<script>
let currentModalAddress = "";
let currentModalOrder = null;

function cleanAddressForThermal(addr) {
    if (!addr) return '';
    return addr
      .replace(/📍\s*Live\s*(?:GPS\s*)?Location:?\s*https?:\/\/\S+/gi, '')
      .replace(/https?:\/\/(?:www\.)?(?:google\.com\/maps[^\s,)]*|maps\.google\.com[^\s,)]*|\S+)/gi, '')
      .replace(/[\(\[]\s*GPS:\s*[^)\]]+[\)\]]/gi, '')
      .replace(/📍\s*Live\s*(?:GPS\s*)?Location:?/gi, '')
      .split('\n')
      .map(line => line.replace(/^[\s,.-]+|[\s,.-]+$/g, '').trim())
      .filter(line => line.length > 0)
      .join('\n')
      .trim();
}

function cleanNotesForThermal(notes) {
    if (!notes) return '';
    return notes
      .replace(/\[\s*GPS:\s*https?:\/\/[^\]]+\]/gi, '')
      .replace(/\[\s*GPS:[^\]]+\]/gi, '')
      .replace(/\(\s*GPS:\s*[^)]+\)/gi, '')
      .replace(/https?:\/\/\S+/gi, '')
      .replace(/\|\s*$/g, '')
      .replace(/^\s*\|/g, '')
      .trim();
}

function printThermalReceiptDirect(order) {
    let printFrame = document.getElementById('thermal-print-iframe-php');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = 'thermal-print-iframe-php';
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = 'none';
        printFrame.style.visibility = 'hidden';
        document.body.appendChild(printFrame);
    }

    const doc = printFrame.contentWindow?.document || printFrame.contentDocument;
    if (!doc) return;

    const cleanAddress = cleanAddressForThermal(order.customer_address) || order.customer_address || 'Address not provided';
    const cleanNotes = cleanNotesForThermal(order.notes);

    let itemsHtml = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach((it, idx) => {
            itemsHtml += `
                <tr>
                    <td style="padding: 3px 0; vertical-align: top; word-break: break-word;">
                        <div style="font-weight: bold; font-size: 11.5px; color: #000;">${idx + 1}. ${it.name}</div>
                        <div style="font-size: 10.5px; color: #000;">Rs. ${parseFloat(it.price).toFixed(0)} × ${it.quantity}</div>
                    </td>
                    <td style="text-align: right; padding: 3px 0; vertical-align: top; font-weight: bold; font-size: 11.5px; white-space: nowrap; color: #000;">
                        Rs. ${parseFloat(it.total).toFixed(0)}
                    </td>
                </tr>
            `;
        });
    } else {
        itemsHtml = '<tr><td colspan="2" style="text-align: center; padding: 6px 0; color: #000;">No items recorded</td></tr>';
    }

    const delFeeText = order.delivery_charges > 0 ? `+ Rs. ${parseFloat(order.delivery_charges).toFixed(0)}` : 'FREE';

    const thermalHtml = `
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8" />
          <title>Receipt ${order.ref}</title>
          <style>
            @page { size: 80mm auto; margin: 0; }
            @media print {
              html, body {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 1mm 2.5mm !important;
              }
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
              width: 100%;
              max-width: 80mm;
              margin: 0 auto;
              background: #fff;
              color: #000;
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
              font-size: 12px;
              line-height: 1.35;
              padding: 2mm 3mm;
              -webkit-print-color-adjust: exact;
              print-color-adjust: exact;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-left { text-align: left; }
            .bold { font-weight: bold; }
            .divider { border-top: 1px dashed #000; margin: 5px 0; }
            .double-divider { border-top: 2px solid #000; margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; font-size: 11.5px; color: #000; }
            .meta-table td { padding: 1.5px 0; color: #000; }
          </style>
        </head>
        <body>
          <div class="text-center" style="margin-bottom: 4px;">
            <h2 style="font-size: 17px; font-weight: 900; letter-spacing: 0.5px;">HR TRADERS</h2>
            <p style="font-size: 10.5px; font-weight: 600; margin-top: 1px;">ONLINE DELIVERY ORDER SLIP</p>
            <p style="font-size: 10.5px; font-weight: 600;">www.thehrtraders.com</p>
          </div>
          <div class="double-divider"></div>
          <table class="meta-table">
            <tr><td><strong>Order No:</strong></td><td class="text-right bold" style="font-size: 14px;">${order.ref}</td></tr>
            <tr><td><strong>Date:</strong></td><td class="text-right">${order.created_at}</td></tr>
            <tr><td><strong>Payment:</strong></td><td class="text-right bold">${order.payment_method || 'Cash on Delivery (COD)'}</td></tr>
            <tr><td><strong>Status:</strong></td><td class="text-right bold" style="text-transform: uppercase;">${order.status.replace(/_/g, ' ')}</td></tr>
          </table>
          <div class="divider"></div>
          <div style="margin: 4px 0;">
            <div style="font-size: 10px; font-weight: bold; text-transform: uppercase;">RIDER / DELIVERY DISPATCH:</div>
            <div style="font-size: 12.5px; font-weight: bold; margin-top: 1px;">Customer: ${order.customer_name}</div>
            <div style="font-size: 12.5px; font-weight: bold; font-family: monospace;">Phone: ${order.customer_phone}</div>
            <div style="margin-top: 4px; padding: 4px; border: 1px dashed #000; font-size: 11.5px; line-height: 1.4;">
              <strong>Delivery Address:</strong><br/>
              ${cleanAddress.replace(/\n/g, '<br/>')}
            </div>
            ${cleanNotes ? `<div style="margin-top: 3px; font-size: 10.5px; font-style: italic;">Note: ${cleanNotes.replace(/\n/g, '<br/>')}</div>` : ''}
          </div>
          <div class="divider"></div>
          <table style="margin: 4px 0;">
            <thead>
              <tr style="border-bottom: 1px solid #000;">
                <th class="text-left" style="padding-bottom: 3px;">ITEM</th>
                <th class="text-right" style="padding-bottom: 3px;">TOTAL</th>
              </tr>
            </thead>
            <tbody>
              ${itemsHtml}
            </tbody>
          </table>
          <div class="divider"></div>
          <table>
            <tr><td>Items Subtotal:</td><td class="text-right">Rs. ${parseFloat(order.items_subtotal).toFixed(0)}</td></tr>
            <tr><td style="font-weight: bold;">Delivery Charges:</td><td class="text-right bold">${delFeeText}</td></tr>
          </table>
          <div class="double-divider"></div>
          <div style="margin: 4px 0;">
            <table style="font-size: 14px;">
              <tr>
                <td style="font-weight: 900;">TOTAL PAYABLE:</td>
                <td class="text-right" style="font-weight: 900; font-size: 15px;">Rs. ${parseFloat(order.total_amount).toFixed(0)}</td>
              </tr>
            </table>
            <div class="text-right" style="font-size: 9px; font-weight: bold; margin-top: 1px;">(COLLECT CASH ON DELIVERY)</div>
          </div>
          <div class="divider"></div>
          <div class="text-center" style="font-size: 10px; margin-top: 6px; line-height: 1.4;">
            <div class="bold">Thank you for ordering with us!</div>
            <div>HR Traders • Quality Guaranteed</div>
            <div style="font-size: 9px; margin-top: 2px;">*** Rider Delivery Slip ***</div>
          </div>
        </body>
      </html>
    `;

    doc.open();
    doc.write(thermalHtml);
    doc.close();

    setTimeout(() => {
        try {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();
        } catch (e) {
            console.error(e);
        }
    }, 250);
}

function printThermalReceiptModal() {
    if (currentModalOrder) {
        printThermalReceiptDirect(currentModalOrder);
    }
}

function openOrderDetailsModal(order) {
    currentModalOrder = order;
    currentModalAddress = order.customer_address;
    document.getElementById('modal-order-ref').innerText = order.ref;
    document.getElementById('modal-order-status').innerText = order.status.replace(/_/g, ' ').toUpperCase();
    document.getElementById('modal-order-subtitle').innerText = `Order Invoice & Customer Slip • ${order.created_at}`;
    
    document.getElementById('modal-customer-name').innerText = order.customer_name;
    const phoneLink = document.getElementById('modal-customer-phone-link');
    phoneLink.innerText = order.customer_phone;
    phoneLink.href = 'tel:' + order.customer_phone;
    
    document.getElementById('modal-whatsapp-btn').onclick = function() {
        sendWhatsAppNotification(
            encodeURIComponent(order.customer_name),
            order.ref,
            encodeURIComponent(order.total_amount),
            encodeURIComponent(order.customer_address),
            order.customer_phone
        );
    };
    
    document.getElementById('modal-payment-method').innerText = order.payment_method || 'Cash on Delivery (COD)';
    document.getElementById('modal-order-date').innerText = order.created_at;
    document.getElementById('modal-customer-address').innerText = `📍 ${order.customer_address}`;
    
    if (order.notes && order.notes.trim()) {
        document.getElementById('modal-notes-container').classList.remove('hidden');
        document.getElementById('modal-notes').innerText = order.notes;
    } else {
        document.getElementById('modal-notes-container').classList.add('hidden');
    }
    
    // Items table
    const tbody = document.getElementById('modal-items-tbody');
    tbody.innerHTML = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach((it, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/60';
            tr.innerHTML = `
                <td class="py-2.5 px-3 text-center font-mono text-slate-400">${idx + 1}</td>
                <td class="py-2.5 px-3 font-semibold text-slate-800">${it.name}</td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-600">Rs. ${parseFloat(it.price).toFixed(2)}</td>
                <td class="py-2.5 px-3 text-center font-bold text-slate-800">x${it.quantity}</td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">Rs. ${parseFloat(it.total).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-slate-400">No items recorded for this order.</td></tr>';
    }
    
    // Pricing
    document.getElementById('modal-items-subtotal').innerText = `Rs. ${parseFloat(order.items_subtotal).toFixed(2)}`;
    const delText = order.delivery_charges > 0 
        ? `+ Rs. ${parseFloat(order.delivery_charges).toFixed(2)}` 
        : 'FREE DELIVERY (Rs. 0.00)';
    document.getElementById('modal-delivery-charges').innerText = delText;
    document.getElementById('modal-total-amount').innerText = `Rs. ${parseFloat(order.total_amount).toFixed(2)}`;
    
    // Quick action buttons inside modal
    const actionsContainer = document.getElementById('modal-status-actions');
    actionsContainer.innerHTML = '';
    if (order.status === 'pending') {
        actionsContainer.innerHTML = `
            <button onclick="updateOrderStatus(${order.id}, 'packaging')" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg">Start Packaging</button>
            <button onclick="updateOrderStatus(${order.id}, 'cancelled')" class="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 text-xs rounded-lg">Cancel</button>
        `;
    } else if (order.status === 'packaging') {
        actionsContainer.innerHTML = `
            <button onclick="updateOrderStatus(${order.id}, 'out_for_delivery')" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-lg">Dispatch / Ship</button>
            <button onclick="updateOrderStatus(${order.id}, 'cancelled')" class="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 text-xs rounded-lg">Cancel</button>
        `;
    } else if (order.status === 'out_for_delivery') {
        actionsContainer.innerHTML = `
            <button onclick="updateOrderStatus(${order.id}, 'delivered')" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg">Mark Delivered</button>
            <button onclick="updateOrderStatus(${order.id}, 'cancelled')" class="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-slate-700 border border-slate-300 hover:text-rose-700 text-xs rounded-lg">Cancel</button>
        `;
    } else if (order.status === 'delivered') {
        actionsContainer.innerHTML = `<span class="text-emerald-600 text-xs font-bold"><i class="fas fa-circle-check"></i> Delivered Successfully</span>`;
    } else if (order.status === 'cancelled') {
        actionsContainer.innerHTML = `<span class="text-rose-600 text-xs font-bold"><i class="fas fa-ban"></i> Order Cancelled</span>`;
    }
    
    const modal = document.getElementById('order-details-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeOrderDetailsModal() {
    const modal = document.getElementById('order-details-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function copyModalAddress() {
    if (navigator.clipboard && currentModalAddress) {
        navigator.clipboard.writeText(currentModalAddress);
        const icon = document.getElementById('copy-address-icon');
        const text = document.getElementById('copy-address-text');
        icon.className = 'fas fa-check text-emerald-600';
        text.innerText = 'Address Copied!';
        setTimeout(() => {
            icon.className = 'fas fa-copy';
            text.innerText = 'Copy Address';
        }, 2000);
    }
}
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

        let lastOrderId = null;

        async function initLastOrder() {
            try {
                const { data, error } = await supabaseClient
                    .from('orders')
                    .select('id')
                    .order('id', { descending: true })
                    .limit(1);
                if (!error && data && data.length > 0) {
                    lastOrderId = data[0].id;
                }
            } catch(e) {
                console.error(e);
            }
        }
        initLastOrder();

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
                        lastOrderId = newOrder.id;
                        showOrderToast(newOrder.id, newOrder.customer_name || 'Guest', newOrder.total_amount || 0);
                        playChime();
                        setTimeout(() => window.location.reload(), 1500);
                    }
                }
            )
            .subscribe();

        setInterval(async () => {
            if (!lastOrderId) return;
            try {
                const { data, error } = await supabaseClient
                    .from('orders')
                    .select('id, customer_name, total_amount')
                    .order('id', { descending: true })
                    .limit(1);
                if (!error && data && data.length > 0) {
                    const latestId = data[0].id;
                    if (latestId > lastOrderId) {
                        lastOrderId = latestId;
                        showOrderToast(latestId, data[0].customer_name || 'Guest', data[0].total_amount || 0);
                        playChime();
                        setTimeout(() => window.location.reload(), 1500);
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }, 15000);

    } catch (err) {
        console.error(err);
    }
});
</script>
</body>
</html>
