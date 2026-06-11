<?php
// HR Traders Customer Checkout Page
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$cart_items = [];
$subtotal = 0;
$checkout_error = "";

$min_order_value = (float)get_setting('min_order_value', '0.00');
$shipping_fee = (float)get_setting('shipping_fee', '0.00');

// Initialize cart if empty
if (empty($_SESSION['cart'])) {
    header("Location: " . BASE_URL . "shop.php");
    exit();
}

// 1. Fetch Cart details for review
$placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
try {
    $stmt = $pdo->prepare("SELECT id, name, price, weight, category, stock_quantity FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($_SESSION['cart']));
    $products = $stmt->fetchAll();

    foreach ($products as $prod) {
        $p_id = $prod['id'];
        $qty = $_SESSION['cart'][$p_id];
        
        // Ensure cart doesn't exceed stock
        if ($prod['stock_quantity'] < $qty) {
            $qty = $prod['stock_quantity'];
            $_SESSION['cart'][$p_id] = $qty;
        }

        if ($qty > 0) {
            $item_total = $prod['price'] * $qty;
            $subtotal += $item_total;
            $cart_items[] = [
                'id' => $prod['id'],
                'name' => $prod['name'],
                'price' => (float)$prod['price'],
                'weight' => $prod['weight'],
                'category' => $prod['category'],
                'qty' => $qty,
                'total' => $item_total
            ];
        }
    }
} catch (PDOException $e) {
    $checkout_error = "Failed to load cart items from database.";
}

// Fetch user profile presets for logged in customers
$preset_name = '';
$preset_phone = '';
$preset_address = '';

if (is_logged_in()) {
    try {
        // Fetch direct profile details from users table
        $stmt_u = $pdo->prepare("SELECT name, phone, address FROM users WHERE id = :id LIMIT 1");
        $stmt_u->execute(['id' => $_SESSION['user_id']]);
        $u_profile = $stmt_u->fetch(PDO::FETCH_ASSOC);
        
        if ($u_profile) {
            $preset_name = $u_profile['name'];
            $preset_phone = $u_profile['phone'] ?? '';
            $preset_address = $u_profile['address'] ?? '';
        }
        
        // If phone or address are empty in users table profile, fallback to last placed order details
        if (empty($preset_phone) || empty($preset_address)) {
            $stmt_pref = $pdo->prepare("SELECT customer_phone, customer_address FROM orders WHERE user_id = :user_id ORDER BY id DESC LIMIT 1");
            $stmt_pref->execute(['user_id' => $_SESSION['user_id']]);
            $pref = $stmt_pref->fetch(PDO::FETCH_ASSOC);
            if ($pref) {
                if (empty($preset_phone)) {
                    $preset_phone = $pref['customer_phone'] ?? '';
                }
                if (empty($preset_address)) {
                    $preset_address = $pref['customer_address'] ?? '';
                }
            }
        }
    } catch (PDOException $e) {
        // Ignore
    }
}

// 2. Handle COD checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart_items)) {
    if (get_setting('shop_status', 'open') === 'closed') {
        $checkout_error = htmlspecialchars(STORE_NAME) . " is temporarily CLOSED. We cannot accept orders at this time.";
    } elseif ($subtotal < $min_order_value) {
        $checkout_error = "Order subtotal is below the minimum required order value of " . format_price($min_order_value) . ".";
    } else {
        $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');

    if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
        $checkout_error = "All shipping fields are required to process delivery.";
    } else {
        // Run database transaction to record order and lock stock levels
        $pdo->beginTransaction();
        try {
            // Verify stock is still valid before placing
            foreach ($cart_items as $item) {
                $chk_stmt = $pdo->prepare("SELECT name, stock_quantity FROM products WHERE id = :id FOR UPDATE");
                $chk_stmt->execute(['id' => $item['id']]);
                $prod_live = $chk_stmt->fetch();

                if (!$prod_live || $prod_live['stock_quantity'] < $item['qty']) {
                    throw new Exception("Stock level changed for '{$item['name']}'. Only {$prod_live['stock_quantity']} left. Please update cart.");
                }
            }

            // Insert into orders table (including shipping fee in total amount)
            $user_id = $_SESSION['user_id'] ?? null;
            $order_total = $subtotal + $shipping_fee;
            $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, total_amount, payment_method, status) 
                                         VALUES (:user_id, :name, :phone, :address, :total_amount, 'COD', 'pending')");
            $stmt_order->execute([
                'user_id' => $user_id,
                'name' => $customer_name,
                'phone' => $customer_phone,
                'address' => $customer_address,
                'total_amount' => $order_total
            ]);
            $order_id = $pdo->lastInsertId();

            // Insert into order_items and decrement stock
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) 
                                         VALUES (:order_id, :product_id, :price, :quantity)");
            
            foreach ($cart_items as $item) {
                $stmt_item->execute([
                    'order_id' => $order_id,
                    'product_id' => $item['id'],
                    'price' => $item['price'],
                    'quantity' => $item['qty']
                ]);

                // Decrement product inventory stock immediately
                adjust_stock($pdo, $item['id'], -$item['qty']);
            }

            $pdo->commit();

            // Clear session cart
            $_SESSION['cart'] = [];

            // Redirect to success page
            header("Location: " . BASE_URL . "order_success.php?order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $checkout_error = "Order processing failed: " . $e->getMessage();
        }
    }
    }
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900">Checkout Desk</h1>
        <p class="text-xs text-slate-500 mt-1">Review your basket and complete shipping address details</p>
    </div>

    <?php if (!empty($checkout_error)): ?>
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-600 text-sm flex items-center gap-3">
            <i class="fas fa-times-circle"></i>
            <span><?php echo $checkout_error; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($subtotal < $min_order_value): ?>
        <div class="mb-6 p-4 bg-amber-50 border border-amber-250 text-amber-800 rounded-2xl text-xs flex items-center gap-3">
            <i class="fas fa-circle-exclamation text-base"></i>
            <span>
                Minimum order limit is <strong><?php echo format_price($min_order_value); ?></strong>. Your current cart total is <strong><?php echo format_price($subtotal); ?></strong>. Please add more items from the <a href="shop.php" class="underline font-bold">shop catalog</a>.
            </span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Cart items checklist -->
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-panel p-6 rounded-2xl border border-slate-200">
                <h3 class="font-bold text-lg text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-basket-shopping text-emerald-600"></i> Review Invoice Items
                </h3>
                
                <div class="divide-y divide-slate-200">
                    <?php foreach ($cart_items as $item): ?>
                        <?php $is_frozen = $item['category'] === 'ice_cream'; ?>
                        <div class="py-4 flex items-center justify-between gap-4 first:pt-0 last:pb-0">
                            <div>
                                <h4 class="font-bold text-slate-800 text-sm"><?php echo sanitize($item['name']); ?></h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    <?php echo !empty($item['weight']) ? sanitize($item['weight']) . ' | ' : ''; ?><?php echo format_price($item['price']); ?>
                                </p>
                                <?php if ($is_frozen): ?>
                                    <span class="text-[9px] font-bold text-rose-600 border border-rose-200 bg-rose-50 px-2 py-0.5 rounded-lg mt-1 inline-block">
                                        <i class="fas fa-circle-exclamation mr-1"></i> Nearby Only
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-right flex items-center gap-6">
                                <div class="text-xs text-slate-505">
                                    Qty: <span class="font-bold text-slate-800 text-sm"><?php echo $item['qty']; ?></span>
                                </div>
                                <div class="font-bold text-slate-800 text-sm w-24 text-right">
                                    <?php echo format_price($item['total']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="border-t border-slate-200 mt-6 pt-6 space-y-2 text-sm text-slate-700">
                    <div class="flex items-center justify-between">
                        <span>Subtotal</span>
                        <span class="font-bold text-slate-800"><?php echo format_price($subtotal); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Delivery Charges</span>
                        <span class="font-bold <?php echo $shipping_fee > 0 ? 'text-slate-850' : 'text-emerald-600'; ?>">
                            <?php echo $shipping_fee > 0 ? format_price($shipping_fee) : 'FREE'; ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between font-bold text-base pt-3 border-t border-slate-200 text-slate-900">
                        <span>Total Invoice Amount</span>
                        <span class="text-xl text-emerald-600"><?php echo format_price($subtotal + $shipping_fee); ?></span>
                    </div>
                </div>
            </div>

            <!-- Shop disclaimer notes -->
            <div class="p-4 bg-slate-100 border border-slate-200 rounded-2xl flex gap-3 text-xs text-slate-600">
                <i class="fas fa-info-circle text-emerald-600 text-sm mt-0.5"></i>
                <div class="space-y-1">
                    <p class="font-semibold text-slate-800">Cash on Delivery (COD)</p>
                    <p>We deliver online orders straight to your address inside Lahore. Payment is collected in cash once you receive and verify the products. Direct support is available via WhatsApp link.</p>
                </div>
            </div>
        </div>

        <!-- Right: COD Checkout Form -->
        <div class="col-span-1">
            <div class="glass-panel p-6 rounded-2xl border border-slate-200 sticky top-24">
                <h3 class="font-bold text-lg text-slate-800 mb-4">Shipping Credentials</h3>
                
                <form action="checkout.php" method="POST" class="space-y-4">
                    <div>
                        <label for="customer_name" class="block text-xs font-semibold text-slate-655 mb-1.5 uppercase tracking-wider">Full Name</label>
                        <input type="text" id="customer_name" name="customer_name" required
                               placeholder="Enter your name" value="<?php echo sanitize($preset_name); ?>"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-slate-50/50 text-sm text-slate-900">
                    </div>

                    <div>
                        <label for="customer_phone" class="block text-xs font-semibold text-slate-655 mb-1.5 uppercase tracking-wider">Contact Phone Number</label>
                        <input type="text" id="customer_phone" name="customer_phone" required
                               placeholder="e.g. 03001234567" value="<?php echo sanitize($preset_phone); ?>"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-slate-50/50 text-sm text-slate-900 font-mono">
                    </div>

                    <div>
                        <label for="customer_address" class="block text-xs font-semibold text-slate-655 mb-1.5 uppercase tracking-wider">Complete Shipping Address</label>
                        <textarea id="customer_address" name="customer_address" rows="3" required
                                  placeholder="House No, Street, Society Block, Area, Lahore"
                                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-slate-50/50 text-sm text-slate-900"><?php echo sanitize($preset_address); ?></textarea>
                    </div>

                    <div class="bg-slate-100 p-4 border border-slate-200 rounded-xl space-y-2">
                        <span class="text-xs font-semibold text-slate-500 block uppercase tracking-wider">Payment Method</span>
                        <div class="flex items-center gap-2 text-emerald-600 font-bold text-sm">
                            <i class="fas fa-hand-holding-dollar text-base"></i>
                            <span>Cash On Delivery (COD)</span>
                        </div>
                    </div>

                    <?php if (get_setting('shop_status', 'open') === 'closed'): ?>
                        <button type="button" disabled class="w-full py-3 bg-slate-200 text-slate-400 font-bold border border-slate-300 rounded-xl text-sm uppercase tracking-widest mt-4 cursor-not-allowed flex items-center justify-center gap-1.5">
                            <i class="fas fa-lock"></i> Store Closed Today
                        </button>
                    <?php elseif ($subtotal < $min_order_value): ?>
                        <button type="button" disabled class="w-full py-3 bg-slate-105 text-slate-400 font-bold border border-slate-200 rounded-xl text-sm uppercase tracking-widest mt-4 cursor-not-allowed flex items-center justify-center gap-1.5">
                            <i class="fas fa-ban"></i> Below Min. Order (<?php echo format_price($min_order_value); ?>)
                        </button>
                    <?php else: ?>
                        <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-black rounded-xl text-sm transition-all shadow-lg shadow-emerald-600/10 uppercase tracking-widest mt-4">
                            Confirm COD Order &rarr;
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
