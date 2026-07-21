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
$min_order_limit_enabled = get_setting('min_order_limit_enabled', 'true') === 'true';
$first_order_free_delivery = get_setting('first_order_free_delivery', 'true') === 'true';

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

// Determine if this qualifies as a first order to adjust shipping fee
$is_first_order = false;
if (is_logged_in()) {
    $is_first_order = true;
    try {
        // 1. Check if user has already received free delivery before (total_amount = items subtotal)
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM orders o
            JOIN (
                SELECT order_id, SUM(price * quantity) AS subtotal 
                FROM order_items 
                GROUP BY order_id
            ) oi ON o.id = oi.order_id
            WHERE o.user_id = :uid 
              AND o.status != 'cancelled' 
              AND o.total_amount = oi.subtotal
        ");
        $stmt_check->execute(['uid' => $_SESSION['user_id']]);
        if ((int)$stmt_check->fetchColumn() > 0) {
            $is_first_order = false;
        }
    } catch (PDOException $e) {}

    if ($is_first_order && !empty($preset_phone)) {
        try {
            // 2. Check by phone number for previous free delivery
            $stmt_phone_check = $pdo->prepare("
                SELECT COUNT(*) 
                FROM orders o
                JOIN (
                    SELECT order_id, SUM(price * quantity) AS subtotal 
                    FROM order_items 
                    GROUP BY order_id
                ) oi ON o.id = oi.order_id
                WHERE o.customer_phone = :phone 
                  AND o.status != 'cancelled' 
                  AND o.total_amount = oi.subtotal
            ");
            $stmt_phone_check->execute(['phone' => $preset_phone]);
            if ((int)$stmt_phone_check->fetchColumn() > 0) {
                $is_first_order = false;
            }
        } catch (PDOException $e) {}
    }

    if ($is_first_order && !empty($preset_address)) {
        try {
            if (!function_exists('normalize_address_for_check')) {
                function normalize_address_for_check($addr) {
                    return preg_replace('/[^a-zA-Z0-9]/', '', strtolower($addr));
                }
            }
            $input_addr_clean = normalize_address_for_check($preset_address);
            
            // Retrieve addresses of orders that received free delivery
            $stmt_addr = $pdo->query("
                SELECT DISTINCT o.customer_address 
                FROM orders o
                JOIN (
                    SELECT order_id, SUM(price * quantity) AS subtotal 
                    FROM order_items 
                    GROUP BY order_id
                ) oi ON o.id = oi.order_id
                WHERE o.status != 'cancelled' 
                  AND o.total_amount = oi.subtotal
            ");
            $addresses = $stmt_addr->fetchAll(PDO::FETCH_COLUMN);
            foreach ($addresses as $addr) {
                if (normalize_address_for_check($addr) === $input_addr_clean) {
                    $is_first_order = false;
                    break;
                }
            }
        } catch (PDOException $e) {}
    }
}
$shipping_fee = ($is_first_order && $first_order_free_delivery) ? 0.00 : (float)get_setting('shipping_fee', '180.00');

// 2. Handle COD checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart_items)) {
    if (get_setting('shop_status', 'open') === 'closed') {
        $checkout_error = htmlspecialchars(STORE_NAME) . " is temporarily CLOSED. We cannot accept orders at this time.";
    } elseif ($min_order_limit_enabled && $subtotal < $min_order_value) {
        $checkout_error = "Order subtotal is below the minimum required order value of " . format_price($min_order_value) . ".";
    } else {
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $customer_address = trim($_POST['customer_address'] ?? '');

        if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
            $checkout_error = "All shipping fields are required to process delivery.";
        } else {
            // Re-evaluate shipping fee based on submitted phone number / address to prevent client tampering
            $post_is_first = false;
            if (is_logged_in()) {
                $post_is_first = true;
                try {
                    $stmt_check = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM orders o
                        JOIN (
                            SELECT order_id, SUM(price * quantity) AS subtotal 
                            FROM order_items 
                            GROUP BY order_id
                        ) oi ON o.id = oi.order_id
                        WHERE o.user_id = :uid 
                          AND o.status != 'cancelled' 
                          AND o.total_amount = oi.subtotal
                    ");
                    $stmt_check->execute(['uid' => $_SESSION['user_id']]);
                    if ((int)$stmt_check->fetchColumn() > 0) {
                        $post_is_first = false;
                    }
                } catch (PDOException $e) {}

                if ($post_is_first && !empty($customer_phone)) {
                    try {
                        $stmt_phone_check = $pdo->prepare("
                            SELECT COUNT(*) 
                            FROM orders o
                            JOIN (
                                SELECT order_id, SUM(price * quantity) AS subtotal 
                                FROM order_items 
                                GROUP BY order_id
                            ) oi ON o.id = oi.order_id
                            WHERE o.customer_phone = :phone 
                              AND o.status != 'cancelled' 
                              AND o.total_amount = oi.subtotal
                        ");
                        $stmt_phone_check->execute(['phone' => $customer_phone]);
                        if ((int)$stmt_phone_check->fetchColumn() > 0) {
                            $post_is_first = false;
                        }
                    } catch (PDOException $e) {}
                }

                if ($post_is_first && !empty($customer_address)) {
                    try {
                        if (!function_exists('normalize_address_for_check')) {
                            function normalize_address_for_check($addr) {
                                return preg_replace('/[^a-zA-Z0-9]/', '', strtolower($addr));
                            }
                        }
                        $input_addr_clean = normalize_address_for_check($customer_address);
                        
                        // Retrieve addresses of orders that received free delivery
                        $stmt_addr = $pdo->query("
                            SELECT DISTINCT o.customer_address 
                            FROM orders o
                            JOIN (
                                SELECT order_id, SUM(price * quantity) AS subtotal 
                                FROM order_items 
                                GROUP BY order_id
                            ) oi ON o.id = oi.order_id
                            WHERE o.status != 'cancelled' 
                              AND o.total_amount = oi.subtotal
                        ");
                        $addresses = $stmt_addr->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($addresses as $addr) {
                            if (normalize_address_for_check($addr) === $input_addr_clean) {
                                $post_is_first = false;
                                break;
                            }
                        }
                    } catch (PDOException $e) {}
                }
            }
            $shipping_fee = ($post_is_first && $first_order_free_delivery) ? 0.00 : (float)get_setting('shipping_fee', '180.00');

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

    <?php if ($min_order_limit_enabled && $subtotal < $min_order_value): ?>
        <div class="mb-6 p-4 bg-amber-50 border border-amber-250 text-amber-800 rounded-2xl text-xs flex items-center gap-3">
            <i class="fas fa-circle-exclamation text-base"></i>
            <span>
                Minimum order limit is <strong><?php echo format_price($min_order_value); ?></strong>. Your current cart total is <strong><?php echo format_price($subtotal); ?></strong>. Please add more items from the <a href="shop.php" class="underline font-bold">shop catalog</a>.
            </span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Cart items checklist -->
        <div class="lg:col-span-2 space-y-6 order-2 lg:order-1">
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
                        <span id="checkout-delivery-charges" class="font-bold <?php echo $shipping_fee > 0 ? 'text-slate-850' : 'text-emerald-600'; ?>">
                            <?php echo $shipping_fee > 0 ? format_price($shipping_fee) : 'FREE'; ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between font-bold text-base pt-3 border-t border-slate-200 text-slate-900">
                        <span>Total Invoice Amount</span>
                        <span id="checkout-grand-total" class="text-xl text-emerald-600"><?php echo format_price($subtotal + $shipping_fee); ?></span>
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
        <div class="col-span-1 order-1 lg:order-2">
            <div class="glass-panel p-6 rounded-2xl border border-slate-200 sticky top-24">
                <h3 class="font-bold text-lg text-slate-800 mb-4">Shipping Credentials</h3>
                
                <form action="checkout.php" method="POST" id="checkout-form" class="space-y-4">
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
                    <?php elseif ($min_order_limit_enabled && $subtotal < $min_order_value): ?>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const phoneInput = document.getElementById('customer_phone');
    const addressInput = document.getElementById('customer_address');
    if (!phoneInput) return;

    const subtotal = <?php echo (float)$subtotal; ?>;
    let checkTimeout;

    const triggerCheck = () => {
        clearTimeout(checkTimeout);
        checkTimeout = setTimeout(checkFirstOrderShipping, 500);
    };

    phoneInput.addEventListener('input', triggerCheck);
    phoneInput.addEventListener('blur', checkFirstOrderShipping);

    if (addressInput) {
        addressInput.addEventListener('input', triggerCheck);
        addressInput.addEventListener('blur', checkFirstOrderShipping);
    }

    function checkFirstOrderShipping() {
        const phone = phoneInput.value.trim();
        const address = addressInput ? addressInput.value.trim() : '';
        if (phone.length < 10) return;

        fetch(BASE_URL + 'api/check_shipping.php?phone=' + encodeURIComponent(phone) + '&address=' + encodeURIComponent(address))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const fee = parseFloat(data.shipping_fee);
                    const chargesEl = document.getElementById('checkout-delivery-charges');
                    const grandTotalEl = document.getElementById('checkout-grand-total');

                    if (chargesEl && grandTotalEl) {
                        if (fee > 0) {
                            chargesEl.className = 'font-bold text-slate-850';
                            chargesEl.innerText = 'Rs. ' + fee.toFixed(2);
                            grandTotalEl.innerText = 'Rs. ' + (subtotal + fee).toFixed(2);
                        } else {
                            chargesEl.className = 'font-bold text-emerald-600';
                            chargesEl.innerText = 'FREE';
                            grandTotalEl.innerText = 'Rs. ' + subtotal.toFixed(2);
                        }
                    }
                }
            })
            .catch(err => console.error(err));
    }

    // Intercept checkout form submission to prompt guest users
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // If logged in, submit normally
            if (typeof IS_USER_LOGGED_IN !== 'undefined' && IS_USER_LOGGED_IN) {
                if (typeof requestNotificationPermission === 'function') {
                    requestNotificationPermission();
                }
                return;
            }

            // If guest checkout has not been explicitly confirmed, prompt the promo modal
            if (!guestConfirmed) {
                e.preventDefault();
                openFreeDeliveryPromoModal();
            } else {
                if (typeof requestNotificationPermission === 'function') {
                    requestNotificationPermission();
                }
            }
        });
    }
});

let guestConfirmed = false;

function openFreeDeliveryPromoModal() {
    const modal = document.getElementById('free-delivery-promo-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.relative').classList.remove('scale-95');
        modal.querySelector('.relative').classList.add('scale-100');
    }, 50);
}

function closeFreeDeliveryPromoModal() {
    const modal = document.getElementById('free-delivery-promo-modal');
    if (!modal) return;
    modal.classList.add('opacity-0');
    modal.querySelector('.relative').classList.remove('scale-100');
    modal.querySelector('.relative').classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function optInFreeDeliverySignup() {
    closeFreeDeliveryPromoModal();
    if (typeof openAuthModal === 'function') {
        openAuthModal();
        if (typeof toggleAuthMode === 'function') {
            toggleAuthMode('signup');
        }
    }
}

function proceedAsGuestWithCharges() {
    closeFreeDeliveryPromoModal();
    guestConfirmed = true;
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.submit();
    }
}
</script>

<!-- FREE DELIVERY PROMO MODAL -->
<div id="free-delivery-promo-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden transition-opacity duration-300 opacity-0" onclick="if(event.target === this) closeFreeDeliveryPromoModal()">
    <div class="relative w-full max-w-sm bg-white border border-slate-200 rounded-3xl p-6 shadow-2xl transform scale-95 transition-all duration-300 flex flex-col gap-5 text-slate-800 text-center">
        <button onclick="closeFreeDeliveryPromoModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-all focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>

        <div class="space-y-1.5">
            <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mx-auto border border-emerald-200">
                <i class="fas fa-gift animate-bounce"></i>
            </div>
            <h3 class="font-extrabold text-slate-900 text-lg">Muft Delivery Hasil Karen! 🎁</h3>
            <p class="text-[11px] text-slate-500 max-w-[260px] mx-auto leading-relaxed">
                Apna account register/sign up karein aur pehli delivery bilkul **FREE** hasil karein!
            </p>
        </div>

        <div class="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-100 text-xs text-emerald-700 leading-normal font-semibold">
            Sign Up karne par aapko first order delivery charges (Rs. 180) bilkul free milenge.
        </div>

        <div class="flex flex-col gap-2">
            <button onclick="optInFreeDeliverySignup()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-bold text-xs rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-600/10 cursor-pointer">
                <i class="fas fa-user-plus text-[10px]"></i> Sign Up & Get Free Delivery
            </button>
            <button onclick="proceedAsGuestWithCharges()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-xs rounded-xl uppercase tracking-wider transition-all flex items-center justify-center cursor-pointer">
                Continue as Guest (Pay Rs. 180)
            </button>
        </div>
    </div>
</div>

<?php if (!is_logged_in()): ?>
<script>
// Auto-prompt guest users to sign in or register upon checking out
document.addEventListener('DOMContentLoaded', function() {
    if (typeof openAuthModal === 'function') {
        openAuthModal();
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
