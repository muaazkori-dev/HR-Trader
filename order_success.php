<?php
// HR Traders Customer Order Success Confirmation Page
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = null;
$order_items = [];

if ($order_id > 0) {
    try {
        // Fetch order details
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute(['id' => $order_id]);
        $order = $stmt->fetch();

        if ($order) {
            // Fetch order items details
            $stmt_items = $pdo->prepare("SELECT oi.*, p.name as prod_name, p.weight as prod_weight 
                                         FROM order_items oi
                                         JOIN products p ON oi.product_id = p.id
                                         WHERE oi.order_id = :order_id");
            $stmt_items->execute(['order_id' => $order_id]);
            $order_items = $stmt_items->fetchAll();
        }
    } catch (PDOException $e) {
        $order = null;
    }
}

// Redirect if invalid order ID
if (!$order) {
    header("Location: " . BASE_URL);
    exit();
}

// DYNAMIC WHATSAPP STRING COMPILER
// Formats the entire cart summary into a beautiful text message
$whatsapp_text = "🛍️ *NEW ORDER - HR TRADERS* 🛍️\n";
$whatsapp_text .= "--------------------------------------\n";
$whatsapp_text .= "*Order Reference:* #HRT-" . str_pad($order['id'], 5, '0', STR_PAD_LEFT) . "\n";
$whatsapp_text .= "*Date:* " . date('d-M-Y h:i A', strtotime($order['created_at'])) . "\n\n";

$whatsapp_text .= "*CUSTOMER DETAILS:*\n";
$whatsapp_text .= "👤 *Name:* " . $order['customer_name'] . "\n";
$whatsapp_text .= "📞 *Phone:* " . $order['customer_phone'] . "\n";
$whatsapp_text .= "📍 *Address:* " . $order['customer_address'] . "\n\n";

$whatsapp_text .= "*ORDERED ITEMS:*\n";
$idx = 1;
foreach ($order_items as $item) {
    $weight_str = !empty($item['prod_weight']) ? " (" . $item['prod_weight'] . ")" : "";
    $whatsapp_text .= $idx . ". " . $item['prod_name'] . $weight_str . " x " . $item['quantity'] . " - " . CURRENCY . " " . number_format($item['price'] * $item['quantity'], 2) . "\n";
    $idx++;
}
$whatsapp_text .= "--------------------------------------\n";
$whatsapp_text .= "*Total Amount:* " . CURRENCY . " " . number_format($order['total_amount'], 2) . "\n";
$whatsapp_text .= "*Payment Mode:* " . $order['payment_method'] . " (Cash on Delivery)\n\n";
$whatsapp_text .= "Thank you for shopping with HR Traders! 🙏";

$whatsapp_url = "https://api.whatsapp.com/send?phone=" . WHATSAPP_NUMBER . "&text=" . urlencode($whatsapp_text);
?>

<div class="max-w-3xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
    <div class="text-center space-y-3 mb-10">
        <div class="w-16 h-16 bg-emerald-50 text-emerald-600 border border-emerald-250 rounded-full flex items-center justify-center text-3xl mx-auto mb-2 animate-bounce">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-slate-900">Order Confirmed!</h1>
        <p class="text-xs text-slate-500">Thank you, your Cash on Delivery order has been successfully placed.</p>
        <span class="inline-block bg-slate-100 border border-slate-200 text-xs px-3 py-1 rounded-full text-slate-750 font-mono">
            Order Reference: #HRT-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>
        </span>
    </div>

    <!-- MAIN INVOICE PANEL -->
    <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-250 space-y-6">
        
        <!-- Top Metadata -->
        <div class="grid grid-cols-2 gap-4 text-xs pb-6 border-b border-slate-200">
            <div class="space-y-1">
                <span class="text-slate-500 block uppercase font-semibold">Delivery Address</span>
                <strong class="text-slate-900 block font-bold"><?php echo sanitize($order['customer_name']); ?></strong>
                <span class="text-slate-500 block font-mono"><?php echo sanitize($order['customer_phone']); ?></span>
                <span class="text-slate-500 block mt-1"><?php echo sanitize($order['customer_address']); ?></span>
            </div>
            <div class="text-right space-y-1">
                <span class="text-slate-500 block uppercase font-semibold">Order Details</span>
                <span class="text-slate-700 block">Date: <?php echo date('d-M-Y h:i A', strtotime($order['created_at'])); ?></span>
                <span class="text-slate-700 block">Payment Method: <?php echo sanitize($order['payment_method']); ?></span>
                <span class="inline-block bg-emerald-50 text-emerald-705 text-[10px] uppercase font-bold border border-emerald-200 px-2 py-0.5 rounded mt-1.5">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
        </div>

        <!-- Invoice Gird -->
        <div class="space-y-4">
            <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">Item Details</h3>
            
            <div class="divide-y divide-slate-200">
                <?php foreach ($order_items as $item): ?>
                    <div class="py-3 flex items-center justify-between gap-4 first:pt-0 last:pb-0 text-sm">
                        <div class="min-w-0">
                            <span class="font-bold text-slate-800 block truncate"><?php echo sanitize($item['prod_name']); ?></span>
                            <span class="text-xs text-slate-500"><?php echo !empty($item['prod_weight']) ? sanitize($item['prod_weight']) . ' | ' : ''; ?><?php echo format_price($item['price']); ?></span>
                        </div>
                        <div class="text-right flex items-center gap-6">
                            <span class="text-slate-500 text-xs font-medium">x <?php echo $item['quantity']; ?></span>
                            <span class="font-bold text-slate-800 w-24 text-right">
                                <?php echo format_price($item['price'] * $item['quantity']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Totals Column -->
        <div class="border-t border-slate-200 pt-6 space-y-3">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Shipping / Delivery Fee</span>
                <span class="font-bold text-slate-800">Rs. 0.00 (Free)</span>
            </div>
            <div class="flex items-center justify-between text-base font-bold text-slate-800 border-t border-slate-200 pt-3">
                <span>Grand Total Invoice</span>
                <span class="text-xl text-emerald-600"><?php echo format_price($order['total_amount']); ?></span>
            </div>
        </div>

    </div>

    <!-- WHATSAPP CTA LINK AND MAIN NAVIGATION BUTTONS -->
    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="<?php echo $whatsapp_url; ?>" target="_blank"
           class="flex items-center justify-center gap-2 py-3 bg-[#25d366] hover:bg-[#1ebd58] active:scale-98 text-slate-950 font-black rounded-xl text-sm transition-all shadow-lg shadow-[#25d366]/20">
            <i class="fab fa-whatsapp text-lg"></i> Send Bill to WhatsApp
        </a>
        <a href="<?php echo BASE_URL; ?>"
           class="flex items-center justify-center gap-2 py-3 bg-slate-100 hover:bg-slate-200 active:scale-98 text-slate-800 font-bold rounded-xl text-sm transition-all border border-slate-300">
            <i class="fas fa-home"></i> Continue Shopping
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
