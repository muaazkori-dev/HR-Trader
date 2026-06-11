<?php
// HR Traders Customer Account Dashboard
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

// Auth Guard: Only logged in customers can access
if (!is_logged_in()) {
    header("Location: " . BASE_URL);
    exit();
}

$user_id = $_SESSION['user_id'];
$profile = null;
$orders = [];
$demands = [];
$orders_items_grouped = [];

try {
    // 1. Fetch Profile info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($profile) {
        // 2. Fetch Orders
        $stmt_orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY id DESC");
        $stmt_orders->execute(['user_id' => $user_id]);
        $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($orders)) {
            // Fetch all items for these orders in one batch
            $order_ids = array_column($orders, 'id');
            $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
            
            $stmt_items = $pdo->prepare("SELECT oi.*, p.name as prod_name, p.weight as prod_weight, p.category 
                                         FROM order_items oi 
                                         JOIN products p ON oi.product_id = p.id 
                                         WHERE oi.order_id IN ($placeholders)");
            $stmt_items->execute($order_ids);
            $all_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            // Group items by order_id
            foreach ($all_items as $item) {
                $orders_items_grouped[$item['order_id']][] = $item;
            }
        }

        // 3. Fetch Demands matching customer phone
        if (!empty($profile['phone'])) {
            $stmt_demands = $pdo->prepare("SELECT * FROM product_demands WHERE customer_phone = :phone ORDER BY id DESC");
            $stmt_demands->execute(['phone' => $profile['phone']]);
            $demands = $stmt_demands->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
}

if (!$profile) {
    logout_user();
    header("Location: " . BASE_URL);
    exit();
}
?>

<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 flex-1">
    
    <!-- Top Greeting Header -->
    <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 text-slate-800">
        <div class="flex items-center gap-4 text-left">
            <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl border border-emerald-200 flex-shrink-0">
                <i class="fas fa-user-circle"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 leading-tight">Welcome, <?php echo htmlspecialchars($profile['name']); ?>!</h1>
                <p class="text-xs text-slate-400 mt-1 font-mono">Email: <?php echo htmlspecialchars($profile['email'] ?? 'N/A'); ?> &bull; Username: @<?php echo htmlspecialchars($profile['username']); ?></p>
            </div>
        </div>
        
        <!-- Tab Navigation controls -->
        <div class="flex items-center gap-2 border-b md:border-b-0 border-slate-100 pb-3 md:pb-0 overflow-x-auto print-hidden">
            <button onclick="switchAccountTab('orders-section')" id="tab-orders-section" class="account-tab-btn px-4 py-2 text-xs font-bold rounded-xl transition-all bg-emerald-600 text-white cursor-pointer shadow-md shadow-emerald-600/10">
                <i class="fas fa-shopping-bag mr-1.5"></i> My Orders
            </button>
            <button onclick="switchAccountTab('profile-section')" id="tab-profile-section" class="account-tab-btn px-4 py-2 text-xs font-bold rounded-xl transition-all bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer">
                <i class="fas fa-user-cog mr-1.5"></i> Shipping Details
            </button>
            <button onclick="switchAccountTab('demands-section')" id="tab-demands-section" class="account-tab-btn px-4 py-2 text-xs font-bold rounded-xl transition-all bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer">
                <i class="fas fa-clipboard-list mr-1.5"></i> My Demands
            </button>
        </div>
    </div>

    <!-- MAIN BODY SECTIONS -->
    
    <!-- 1. ORDERS SECTION -->
    <div id="orders-section" class="account-section-content space-y-6">
        <h2 class="text-lg font-black text-slate-900 flex items-center gap-2 uppercase tracking-wider">
            <i class="fas fa-shopping-bag text-emerald-600"></i> Recent Purchases
        </h2>
        
        <?php if (empty($orders)): ?>
            <div class="glass-panel p-12 rounded-3xl border border-slate-200 bg-white text-center space-y-4">
                <div class="w-16 h-16 bg-slate-50 border border-slate-200 rounded-full flex items-center justify-center text-slate-400 mx-auto text-2xl">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h3 class="font-bold text-slate-805 text-base">No orders placed yet</h3>
                <p class="text-xs text-slate-450 max-w-sm mx-auto">Aap ne abhi tak koi order place nahi kiya hai. Grocery items khareedne ke liye shop section visit karein.</p>
                <a href="<?php echo BASE_URL; ?>shop.php" class="inline-block px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                    Browse Shop
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6">
                <?php foreach ($orders as $order): ?>
                    <?php 
                    $order_id = $order['id'];
                    $padded_id = str_pad($order_id, 5, '0', STR_PAD_LEFT);
                    $status = $order['status'];
                    $items = $orders_items_grouped[$order_id] ?? [];
                    ?>
                    <div class="glass-panel p-5 sm:p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-6">
                        
                        <!-- Order Top Details Info -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-150 pb-4">
                            <div class="space-y-1 text-left">
                                <span class="text-xs font-bold text-slate-800 font-mono">Reference ID: #HRT-<?php echo $padded_id; ?></span>
                                <span class="block text-[10px] text-slate-400">Date: <?php echo date('d-M-Y h:i A', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-xs font-bold text-slate-500 mr-2">Total Amount: <strong class="text-emerald-600 text-sm"><?php echo format_price($order['total_amount']); ?></strong></span>
                                
                                <button onclick="openInvoiceModal(<?php echo $order_id; ?>)" 
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] rounded-lg border border-slate-300 transition-colors uppercase tracking-wider flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-print"></i> Invoice
                                </button>
                            </div>
                        </div>

                        <!-- Ordered Items Quick Grid Summary -->
                        <div class="text-left space-y-2">
                            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider block">Ordered Items Summary</span>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($items as $item): ?>
                                    <span class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 font-semibold flex items-center gap-1.5">
                                        <i class="fas fa-box text-[10px] text-slate-400"></i>
                                        <?php echo htmlspecialchars($item['prod_name']); ?>
                                        <span class="text-[10px] text-slate-400">x<?php echo $item['quantity']; ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Progress tracking bar -->
                        <div class="pt-2 text-center">
                            <?php if ($status === 'cancelled'): ?>
                                <div class="bg-rose-50 border border-rose-150 p-3.5 rounded-2xl flex items-center justify-center gap-2 text-rose-700 font-bold text-xs">
                                    <i class="fas fa-times-circle text-base"></i>
                                    <span>Aapka order #HRT-<?php echo $padded_id; ?> cancel ho chuka hai.</span>
                                </div>
                            <?php else: ?>
                                <div class="relative max-w-xl mx-auto py-2">
                                    <!-- Progress line behind circles -->
                                    <div class="absolute inset-y-1/2 left-0 right-0 h-0.5 bg-slate-200 -translate-y-1/2 rounded z-0"></div>
                                    
                                    <?php 
                                    // Progress calculations
                                    $step = 1; // Default pending
                                    if ($status === 'packaging') $step = 2;
                                    elseif ($status === 'out_for_delivery') $step = 3;
                                    elseif ($status === 'delivered') $step = 4;
                                    ?>
                                    <!-- Dynamic Progress width filler -->
                                    <div class="absolute inset-y-1/2 left-0 h-0.5 bg-emerald-500 -translate-y-1/2 rounded z-0 transition-all duration-500"
                                         style="width: <?php echo (($step - 1) / 3) * 100; ?>%;"></div>

                                    <!-- Circles grid container -->
                                    <div class="relative flex justify-between items-center z-10 text-[9px] font-bold text-slate-400">
                                        
                                        <!-- Step 1: Placed -->
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs border transition-all duration-300
                                                 <?php echo $step >= 1 ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/20' : 'bg-white text-slate-400 border-slate-250'; ?>">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <span class="<?php echo $step >= 1 ? 'text-emerald-700 font-extrabold' : 'text-slate-500'; ?>">Placed</span>
                                        </div>

                                        <!-- Step 2: Packaging -->
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs border transition-all duration-300
                                                 <?php echo $step >= 2 ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/20' : 'bg-white text-slate-400 border-slate-250'; ?>">
                                                <?php if ($step > 2): ?><i class="fas fa-check"></i><?php else: ?><i class="fas fa-box"></i><?php endif; ?>
                                            </div>
                                            <span class="<?php echo $step >= 2 ? 'text-emerald-700 font-extrabold' : 'text-slate-500'; ?>">Packing</span>
                                        </div>

                                        <!-- Step 3: Out for Delivery -->
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs border transition-all duration-300
                                                 <?php echo $step >= 3 ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/20' : 'bg-white text-slate-400 border-slate-250'; ?>">
                                                <?php if ($step > 3): ?><i class="fas fa-check"></i><?php else: ?><i class="fas fa-motorcycle"></i><?php endif; ?>
                                            </div>
                                            <span class="<?php echo $step >= 3 ? 'text-emerald-700 font-extrabold' : 'text-slate-500'; ?>">Shipped</span>
                                        </div>

                                        <!-- Step 4: Delivered -->
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs border transition-all duration-300
                                                 <?php echo $step >= 4 ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/20' : 'bg-white text-slate-400 border-slate-250'; ?>">
                                                <i class="fas fa-house-chimney"></i>
                                            </div>
                                            <span class="<?php echo $step >= 4 ? 'text-emerald-700 font-extrabold' : 'text-slate-550'; ?>">Delivered</span>
                                        </div>

                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. PROFILE SECTION -->
    <div id="profile-section" class="account-section-content space-y-6 hidden">
        <h2 class="text-lg font-black text-slate-900 flex items-center gap-2 uppercase tracking-wider text-left">
            <i class="fas fa-user-cog text-emerald-600"></i> Shipping Details Settings
        </h2>
        
        <div class="max-w-xl bg-white border border-slate-200 rounded-3xl p-6 shadow-sm glass-panel text-left space-y-4">
            <div>
                <h3 class="font-bold text-slate-800 text-sm">Default Delivery Address</h3>
                <p class="text-[10px] text-slate-500">Apni delivery credentials save karein taake checkout par auto-autofill ho sakein.</p>
            </div>
            
            <form id="profile-update-form" onsubmit="event.preventDefault(); submitProfileUpdate();" class="space-y-4">
                <div>
                    <label for="prof-name" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Full Name</label>
                    <input type="text" id="prof-name" required value="<?php echo htmlspecialchars($profile['name']); ?>" placeholder="Enter name"
                           class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-800 font-semibold">
                </div>

                <div>
                    <label for="prof-phone" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">WhatsApp / Contact Phone Number</label>
                    <input type="text" id="prof-phone" required value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="e.g. 03001234567"
                           class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-800 font-mono font-semibold">
                </div>

                <div>
                    <label for="prof-address" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Complete Shipping Address</label>
                    <textarea id="prof-address" required rows="3" placeholder="House No, Street, Block, Area, City"
                              class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-800 font-semibold resize-none"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                </div>

                <button type="submit" id="profile-submit-btn" 
                        class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 shadow-md shadow-emerald-500/10 active:scale-95 uppercase tracking-wider">
                    <i class="fas fa-save"></i> Save Profile Details
                </button>
            </form>
        </div>
    </div>

    <!-- 3. DEMANDS SECTION -->
    <div id="demands-section" class="account-section-content space-y-6 hidden">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2 uppercase tracking-wider">
                <i class="fas fa-clipboard-list text-emerald-600"></i> My Demand requests
            </h2>
            <button onclick="openDemandModal()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[10px] rounded-xl shadow-md transition-all flex items-center gap-1.5 uppercase tracking-wider cursor-pointer">
                <i class="fas fa-plus"></i> Submit New Demand
            </button>
        </div>

        <?php if (empty($demands)): ?>
            <div class="glass-panel p-12 rounded-3xl border border-slate-200 bg-white text-center space-y-4">
                <div class="w-16 h-16 bg-slate-50 border border-slate-200 rounded-full flex items-center justify-center text-slate-400 mx-auto text-2xl">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="font-bold text-slate-805 text-base">No item requests found</h3>
                <p class="text-xs text-slate-450 max-w-sm mx-auto">Aapka save kiya hua phone number kisi demand request ke sath match nahi ho raha hai. Nayi request submit karne ke liye upar diye gaye button par click karein.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
                <?php foreach ($demands as $demand): ?>
                    <div class="glass-panel p-5 rounded-2xl border border-slate-200 bg-white shadow-sm space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-mono text-slate-400"><?php echo date('d-M-Y h:i A', strtotime($demand['created_at'])); ?></span>
                            <?php if ($demand['status'] === 'confirmed'): ?>
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-705 border border-emerald-200 text-[9px] font-extrabold uppercase rounded-lg">Confirmed / Resolved</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-705 border border-amber-205 text-[9px] font-extrabold uppercase rounded-lg">Pending Review</span>
                            <?php endif; ?>
                        </div>
                        <p class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl text-slate-655 border border-slate-200/50 italic text-xs break-words">
                            "<?php echo htmlspecialchars($demand['demand_details']); ?>"
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- PRINTABLE INVOICE MODAL POPUP -->
<div id="invoice-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden transition-opacity duration-300 opacity-0" onclick="if(event.target === this) closeInvoiceModal()">
    <div class="relative w-full max-w-2xl bg-white border border-slate-250 rounded-3xl p-6 shadow-2xl transform scale-95 transition-all duration-300 flex flex-col gap-5 text-slate-800 max-h-[90vh] overflow-y-auto">
        <!-- Close button -->
        <button onclick="closeInvoiceModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-all focus:outline-none print-hidden">
            <i class="fas fa-times text-lg"></i>
        </button>

        <!-- Printable content wrapper -->
        <div id="invoice-print-area" class="space-y-6 text-left p-2">
            <!-- Header branding details -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                <div class="flex items-center gap-2">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo.png" class="w-10 h-10 object-contain rounded-full border border-slate-200 p-0.5 bg-white">
                    <div>
                        <strong class="text-base font-black text-slate-900 uppercase block leading-none"><?php echo htmlspecialchars(STORE_NAME); ?></strong>
                        <span class="text-[9px] text-slate-400 uppercase tracking-widest font-semibold mt-0.5">Premium Grocery Bill</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-sm font-bold text-slate-800 block" id="inv-ref-id">Invoice #HRT-00000</span>
                    <span class="text-[10px] text-slate-400 font-mono block mt-1" id="inv-date">Date: N/A</span>
                </div>
            </div>

            <!-- Shipping Credentials details -->
            <div class="grid grid-cols-2 gap-4 text-xs bg-slate-50 border border-slate-200/60 p-4 rounded-2xl">
                <div class="space-y-1">
                    <span class="text-slate-405 font-bold uppercase tracking-wider text-[9px] block">Customer Details</span>
                    <strong class="text-slate-800 block text-xs" id="inv-cust-name">N/A</strong>
                    <span class="text-slate-550 block font-mono" id="inv-cust-phone">N/A</span>
                </div>
                <div class="space-y-1">
                    <span class="text-slate-405 font-bold uppercase tracking-wider text-[9px] block">Shipping Destination</span>
                    <span class="text-slate-600 block leading-relaxed" id="inv-cust-address">N/A</span>
                </div>
            </div>

            <!-- Items detailed Table list -->
            <div class="space-y-2.5">
                <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider block">Items Purchase List</span>
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    <table class="w-full border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold">
                                <th class="p-3 text-left">Item Name / details</th>
                                <th class="p-3 text-center w-20">Qty</th>
                                <th class="p-3 text-right w-24">Price</th>
                                <th class="p-3 text-right w-28">Total</th>
                            </tr>
                        </thead>
                        <tbody id="invoice-items-body" class="divide-y divide-slate-100 text-slate-700">
                            <!-- Injected dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Grand Totals summary -->
            <div class="border-t border-slate-200 pt-4 flex flex-col items-end gap-1.5 text-xs text-slate-550">
                <div class="flex justify-between w-60">
                    <span>Shipping Delivery Charge:</span>
                    <strong class="text-slate-800 font-semibold" id="inv-shipping-fee">Rs. 0.00</strong>
                </div>
                <div class="flex justify-between w-60 border-t border-slate-150 pt-2 font-bold text-sm text-slate-900">
                    <span>Invoice Grand Total:</span>
                    <span class="text-emerald-600 text-base" id="inv-grand-total">Rs. 0.00</span>
                </div>
            </div>

            <!-- Terms disclaimer details -->
            <div class="border-t border-slate-200 pt-4 text-center text-[10px] text-slate-400 space-y-1">
                <p>Thank you for placing order with HR Traders. Payment method: Cash On Delivery.</p>
                <p class="font-mono">Verification status: Valid invoice generated online.</p>
            </div>
        </div>

        <!-- Action Control Buttons -->
        <div class="flex items-center justify-end gap-2.5 pt-1 border-t border-slate-100 print-hidden">
            <button onclick="closeInvoiceModal()" class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors">
                Cancel
            </button>
            <button onclick="window.print()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 uppercase tracking-wider cursor-pointer">
                <i class="fas fa-print"></i> Print Invoice bill
            </button>
        </div>
    </div>
</div>

<script>
// Tab Switching system
function switchAccountTab(tabId) {
    document.querySelectorAll('.account-section-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(tabId).classList.remove('hidden');

    document.querySelectorAll('.account-tab-btn').forEach(btn => {
        btn.classList.remove('bg-emerald-600', 'text-white', 'shadow-md', 'shadow-emerald-600/10');
        btn.classList.add('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
    });

    const activeBtn = document.getElementById('tab-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
        activeBtn.classList.add('bg-emerald-600', 'text-white', 'shadow-md', 'shadow-emerald-600/10');
    }
}

// Profile settings update submission
function submitProfileUpdate() {
    const name = document.getElementById('prof-name').value.trim();
    const phone = document.getElementById('prof-phone').value.trim();
    const address = document.getElementById('prof-address').value.trim();
    const btn = document.getElementById('profile-submit-btn');

    if (!name || !phone || !address) {
        alert("All fields are required!");
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> Saving changes...';

    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('address', address);

    fetch(BASE_URL + 'api/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Profile Details';
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast(data.message, 'success');
            } else {
                alert(data.message);
            }
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert(data.message || "Failed to save settings.");
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Profile Details';
        console.error(err);
        alert("An error occurred during save.");
    });
}

// Dynamic Invoice details loader & display overlay modal
const ordersGroupedData = <?php echo json_encode($orders); ?>;
const ordersItemsGroupedData = <?php echo json_encode($orders_items_grouped); ?>;
const shippingFeeConfig = <?php echo (float)get_setting('shipping_fee', '0.00'); ?>;

function openInvoiceModal(orderId) {
    const modal = document.getElementById('invoice-modal');
    if (!modal) return;

    // Find order information
    const order = ordersGroupedData.find(o => parseInt(o.id) === orderId);
    const items = ordersItemsGroupedData[orderId] || [];

    if (!order) {
        alert("Invoice information not found.");
        return;
    }

    const paddedId = String(order.id).padStart(5, '0');
    
    // Bind order fields
    document.getElementById('inv-ref-id').innerText = `Invoice #HRT-${paddedId}`;
    
    const orderDate = new Date(order.created_at).toLocaleDateString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
    document.getElementById('inv-date').innerText = `Date: ${orderDate}`;
    
    document.getElementById('inv-cust-name').innerText = order.customer_name;
    document.getElementById('inv-cust-phone').innerText = order.customer_phone;
    document.getElementById('inv-cust-address').innerText = order.customer_address;

    // Build items rows
    let itemsHtml = '';
    let subtotal = 0;
    items.forEach(item => {
        const lineTotal = parseFloat(item.price) * parseInt(item.quantity);
        subtotal += lineTotal;
        const weightLabel = item.prod_weight ? ` (${item.prod_weight})` : '';
        itemsHtml += `
            <tr class="border-b border-slate-50">
                <td class="p-3 font-semibold text-slate-800">${item.prod_name}${weightLabel}</td>
                <td class="p-3 text-center font-bold text-slate-600">${item.quantity}</td>
                <td class="p-3 text-right font-mono font-semibold text-slate-600">Rs. ${parseFloat(item.price).toFixed(2)}</td>
                <td class="p-3 text-right font-mono font-bold text-slate-800">Rs. ${lineTotal.toFixed(2)}</td>
            </tr>
        `;
    });
    document.getElementById('invoice-items-body').innerHTML = itemsHtml;

    // Set totals
    const shipping = parseFloat(shippingFeeConfig);
    document.getElementById('inv-shipping-fee').innerText = shipping > 0 ? `Rs. ${shipping.toFixed(2)}` : 'Rs. 0.00 (Free)';
    document.getElementById('inv-grand-total').innerText = `Rs. ${parseFloat(order.total_amount).toFixed(2)}`;

    // Show modal transition
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.relative').classList.remove('scale-95');
        modal.querySelector('.relative').classList.add('scale-100');
    }, 50);
}

function closeInvoiceModal() {
    const modal = document.getElementById('invoice-modal');
    if (!modal) return;

    modal.classList.add('opacity-0');
    modal.querySelector('.relative').classList.remove('scale-100');
    modal.querySelector('.relative').classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
