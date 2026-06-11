<?php
// HR Traders E-commerce Footer Component
$timings_json = get_setting('shop_timings', '{}');
$shop_timings = json_decode($timings_json, true);
$today_day = date('l');
$today_timings = $shop_timings[$today_day] ?? '6:00 AM - 12:00 PM';
?>
</main> <!-- End Main Container -->

<footer class="bg-slate-100 border-t border-slate-250 mt-16 text-slate-600">
    <div class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            
            <!-- Store info -->
            <div class="space-y-4 col-span-1 md:col-span-2">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full overflow-hidden border border-slate-200 bg-white shadow-sm flex-shrink-0 flex items-center justify-center p-1">
                        <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="HR Traders Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <span class="text-lg font-black tracking-tight leading-none text-slate-900"><?php echo htmlspecialchars(STORE_NAME); ?></span>
                        <span class="text-[9px] text-slate-400 font-normal uppercase tracking-wider mt-0.5">Premium Store</span>
                    </div>
                </div>
                <p class="text-sm text-slate-555 max-w-sm">
                    Premium grocery shopping experience, bridging high quality physical store products with rapid, hassle-free online cash-on-delivery.
                </p>
                <div class="flex items-center gap-4 text-slate-500 text-lg">
                    <a href="https://wa.me/<?php echo WHATSAPP_NUMBER; ?>?text=Hi" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-600"><i class="fab fa-whatsapp"></i></a>
                    <a href="<?php echo htmlspecialchars(get_setting('facebook_url', '#')); ?>" target="_blank" class="hover:text-emerald-600"><i class="fab fa-facebook"></i></a>
                    <a href="<?php echo htmlspecialchars(get_setting('instagram_url', '#')); ?>" target="_blank" class="hover:text-emerald-600"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- Categories Quick Links -->
            <div class="space-y-3">
                <h4 class="text-slate-800 font-semibold text-sm uppercase tracking-wider">Quick Categories</h4>
                <ul class="space-y-2 text-sm text-slate-650">
                    <li><a href="<?php echo BASE_URL; ?>shop.php?category=anaj#shop-container" class="hover:text-emerald-600 transition-colors">Anaj</a></li>
                    <li><a href="<?php echo BASE_URL; ?>shop.php?category=ice_cream#shop-container" class="hover:text-emerald-600 transition-colors">Ice Cream</a></li>
                    <li><a href="<?php echo BASE_URL; ?>shop.php?category=beverages#shop-container" class="hover:text-emerald-600 transition-colors">Beverages</a></li>
                    <li><a href="<?php echo BASE_URL; ?>shop.php?category=milk#shop-container" class="hover:text-emerald-600 transition-colors">Milk</a></li>
                    <li><a href="<?php echo BASE_URL; ?>shop.php?category=cosmetics#shop-container" class="hover:text-emerald-600 transition-colors">Cosmetics</a></li>
                    <li><a href="<?php echo BASE_URL; ?>shop.php?category=snacks#shop-container" class="hover:text-emerald-600 transition-colors">Snacks</a></li>
                </ul>
            </div>

            <div class="space-y-4">
                <h4 class="text-slate-800 font-semibold text-sm uppercase tracking-wider">Store Locations</h4>
                <div class="space-y-4 text-xs text-slate-550">
                    <!-- Branch 1 -->
                    <div class="space-y-1.5 border-b border-slate-200/50 pb-3">
                        <span class="text-[10px] font-bold text-slate-700 block uppercase tracking-wider">Branch 1 (Toor Colony)</span>
                        <a href="https://maps.app.goo.gl/S7BB1SyefKsfKX5K7" target="_blank" rel="noopener noreferrer" class="flex items-start gap-2 hover:text-emerald-600 transition-colors leading-tight">
                            <i class="fas fa-map-marker-alt text-emerald-600 mt-0.5 flex-shrink-0"></i>
                            <span>Toor Colony, Front of Hira Public School, Tando Adam</span>
                        </a>
                        <a href="tel:923033943814" class="flex items-center gap-2 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-phone-alt text-emerald-600 flex-shrink-0 text-[10px]"></i>
                            <span>+92 303 3943814</span>
                        </a>
                    </div>
                    <!-- Branch 2 -->
                    <div class="space-y-1.5 border-b border-slate-200/50 pb-3">
                        <span class="text-[10px] font-bold text-slate-700 block uppercase tracking-wider">Branch 2 (Gulshan-e-Sardar)</span>
                        <a href="https://maps.app.goo.gl/3MThGg3KyDduX4eB7?g_st=awb" target="_blank" rel="noopener noreferrer" class="flex items-start gap-2 hover:text-emerald-600 transition-colors leading-tight">
                            <i class="fas fa-map-marker-alt text-emerald-600 mt-0.5 flex-shrink-0"></i>
                            <span>Gulshan-e-Sardar, near Ayoub Hotel, Tando Adam</span>
                        </a>
                        <a href="tel:923137889859" class="flex items-center gap-2 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-phone-alt text-emerald-600 flex-shrink-0 text-[10px]"></i>
                            <span>+92 313 7889859</span>
                        </a>
                    </div>
                    <!-- Timing/Schedule Button -->
                    <div class="relative pt-1">
                        <button id="timing-toggle-btn" class="flex items-center gap-2 text-slate-550 hover:text-emerald-600 transition-colors focus:outline-none w-full text-left cursor-pointer">
                            <i class="fas fa-clock text-emerald-600 flex-shrink-0 text-sm"></i>
                            <span class="font-medium text-xs"><?php echo htmlspecialchars($today_timings); ?> &bull; <span class="text-[10px] text-emerald-600 font-bold underline decoration-dotted">Weekly Schedule</span></span>
                        </button>
                        
                        <!-- Timing Popover Card -->
                        <div id="timing-popover" class="hidden absolute bottom-full left-0 mb-3 w-72 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-4 z-50 origin-bottom-left">
                            <!-- Popover Header -->
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-2">
                                <span class="font-bold text-slate-855 dark:text-white flex items-center gap-2 text-xs uppercase tracking-wider">
                                    <i class="fas fa-calendar-alt text-emerald-600"></i> Store Timings
                                </span>
                                <button id="timing-close-btn" class="text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 p-0.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                            <!-- Days List -->
                            <div class="space-y-2 text-xs">
                                <?php 
                                $days_of_week = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                foreach ($days_of_week as $day):
                                    $day_val = $shop_timings[$day] ?? '6:00 AM - 12:00 PM';
                                    $is_today = ($day === $today_day);
                                    if ($day === 'Friday' && strpos($day_val, '&') !== false) {
                                        // Friday split shifts highlight
                                        $shifts = explode('&', $day_val);
                                        ?>
                                        <div class="flex justify-between items-start p-2 rounded-xl border <?php echo $is_today ? 'text-emerald-805 bg-emerald-50 dark:bg-emerald-950/20 border-emerald-250 font-bold' : 'text-slate-600 border-transparent'; ?>">
                                            <span class="<?php echo $is_today ? 'font-bold' : 'font-medium'; ?>">Friday</span>
                                            <div class="text-right flex flex-col <?php echo $is_today ? 'font-bold text-[11px]' : 'font-semibold text-xs'; ?> leading-tight">
                                                <?php foreach ($shifts as $sh): ?>
                                                    <span><?php echo trim($sh); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php
                                    } else {
                                        ?>
                                        <div class="flex justify-between items-center p-1.5 rounded-lg <?php echo $is_today ? 'text-emerald-805 bg-emerald-50/50 border border-emerald-250/40 dark:bg-emerald-950/10 font-bold' : 'text-slate-600'; ?>">
                                            <span class="font-medium"><?php echo $day; ?></span>
                                            <span class="font-semibold text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($day_val); ?></span>
                                        </div>
                                        <?php
                                    }
                                endforeach;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="border-t border-slate-250 mt-8 pt-8 text-center text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p>&copy; <?php echo date('Y'); ?> HR Traders. All rights reserved.</p>
            <p class="flex items-center gap-1">
                Built with <i class="fas fa-heart text-red-500"></i> strictly in Core PHP &amp; Vanilla JS
            </p>
        </div>
    </div>
</footer>

<!-- FLOATING WHATSAPP CHAT SELECTOR -->
<div id="whatsapp-selector-card" class="fixed bottom-24 right-6 bg-white border border-slate-200 rounded-3xl shadow-2xl p-4 z-50 w-72 transition-all duration-300 transform scale-95 opacity-0 pointer-events-none origin-bottom-right">
    <div class="flex items-center justify-between pb-2 border-b border-slate-100 mb-3">
        <span class="font-bold text-slate-800 flex items-center gap-2 text-xs uppercase tracking-wider">
            <i class="fab fa-whatsapp text-emerald-600 text-lg"></i> WhatsApp Support
        </span>
        <button onclick="toggleWhatsappSelector(false)" class="text-slate-400 hover:text-slate-650 p-0.5 rounded-lg hover:bg-slate-105 transition-all focus:outline-none">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>
    <div class="space-y-2">
        <!-- Branch 1 Button -->
        <a href="https://wa.me/923033943814?text=Salam%20HR%20Traders%20Branch%201" target="_blank" rel="noopener noreferrer" 
           class="flex items-center gap-3 p-2.5 rounded-2xl border border-slate-100 hover:border-emerald-250 hover:bg-emerald-50/30 transition-all group">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0 group-hover:bg-emerald-100 transition-colors">
                <i class="fab fa-whatsapp"></i>
            </div>
            <div class="flex-1 text-left">
                <span class="font-bold text-xs text-slate-800 block leading-tight">Branch 1 (Toor Colony)</span>
                <span class="text-[10px] text-slate-500 font-mono">+92 303 3943814</span>
            </div>
        </a>
        <!-- Branch 2 Button -->
        <a href="https://wa.me/923137889859?text=Salam%20HR%20Traders%20Branch%202" target="_blank" rel="noopener noreferrer" 
           class="flex items-center gap-3 p-2.5 rounded-2xl border border-slate-100 hover:border-emerald-250 hover:bg-emerald-50/30 transition-all group">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0 group-hover:bg-emerald-100 transition-colors">
                <i class="fab fa-whatsapp"></i>
            </div>
            <div class="flex-1 text-left">
                <span class="font-bold text-xs text-slate-800 block leading-tight">Branch 2 (Gulshan-e-Sardar)</span>
                <span class="text-[10px] text-slate-500 font-mono">+92 313 7889859</span>
            </div>
        </a>
    </div>
</div>

<!-- FLOATING WHATSAPP TRIGGER BUTTON -->
<button onclick="toggleWhatsappSelector()" 
        class="whatsapp-float hover:scale-110 active:scale-95 focus:outline-none transition-all shadow-xl flex items-center justify-center cursor-pointer" 
        title="Chat with HR Traders on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</button>

<script>
function toggleWhatsappSelector(show) {
    const card = document.getElementById('whatsapp-selector-card');
    if (!card) return;
    
    if (show === undefined) {
        show = card.classList.contains('pointer-events-none');
    }
    
    if (show) {
        card.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
        card.classList.add('opacity-100', 'scale-100');
    } else {
        card.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
        card.classList.remove('opacity-100', 'scale-100');
    }
}

// Close selector card when clicking outside
document.addEventListener('click', function(e) {
    const card = document.getElementById('whatsapp-selector-card');
    const trigger = document.querySelector('.whatsapp-float');
    if (card && !card.classList.contains('pointer-events-none')) {
        if (!card.contains(e.target) && !trigger.contains(e.target)) {
            toggleWhatsappSelector(false);
        }
    }
});
</script>


<!-- PRODUCT DETAILS MODAL (REMOVED - REDIRECTING TO PRODUCT.PHP) -->

<!-- PRODUCT DEMAND MODAL DIALOG -->
<div id="demand-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden transition-opacity duration-300 opacity-0">
    <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl transform scale-95 transition-all duration-300 flex flex-col gap-5 text-slate-800">
        <!-- Close button -->
        <button onclick="toggleDemandModal(false)" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
            <i class="fas fa-times text-lg"></i>
        </button>

        <!-- Header -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/20 text-amber-500 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="text-left">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">Demand Box / Item Request</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Jo grocery item hamare paas nahi hai, uski demand yahan likhein!</p>
            </div>
        </div>

        <!-- Form content -->
        <form id="demand-box-form" onsubmit="event.preventDefault(); submitProductDemand();" class="space-y-4">
            <div class="text-left">
                <label for="demand-name" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Apna Naam (Your Name)</label>
                <input type="text" id="demand-name" required placeholder="Enter your full name" 
                       class="w-full px-3.5 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:border-amber-500 placeholder-slate-405 text-slate-900 dark:text-white font-semibold">
            </div>

            <div class="text-left">
                <label for="demand-phone" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">WhatsApp / Phone Number</label>
                <input type="tel" id="demand-phone" required placeholder="e.g. 03001234567" 
                       class="w-full px-3.5 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:border-amber-500 placeholder-slate-405 text-slate-900 dark:text-white font-semibold font-mono">
            </div>

            <div class="text-left">
                <label for="demand-details" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Required Item Details (Product details)</label>
                <textarea id="demand-details" required rows="3" placeholder="Type items name, brand, or weight details..." 
                          class="w-full px-3.5 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:border-amber-500 placeholder-slate-405 text-slate-900 dark:text-white font-semibold resize-none"></textarea>
            </div>

            <!-- Submit button -->
            <button type="submit" id="demand-submit-btn" 
                    class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-black rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 shadow-md shadow-amber-500/10 active:scale-95 uppercase tracking-wider mt-2">
                <i class="fas fa-paper-plane text-[10px]"></i> Submit Demand
            </button>
        </form>
    </div>
</div>

<!-- Cookie Consent Banner -->
<div id="cookie-consent-banner" class="fixed bottom-4 left-4 right-4 sm:right-auto sm:max-w-md bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-200/80 dark:border-slate-800/80 p-5 rounded-2xl shadow-2xl z-[100] transform translate-y-20 opacity-0 pointer-events-none transition-all duration-500 flex flex-col gap-4">
    <div class="flex items-start gap-3.5">
        <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/20 text-amber-500 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fas fa-cookie-bite animate-pulse"></i>
        </div>
        <div class="space-y-1">
            <h4 class="font-bold text-slate-900 dark:text-white text-sm">Cookie Settings!</h4>
            <p class="text-xs text-slate-650 dark:text-slate-400 leading-normal">
                Hum cookies ka istemal karte hain taake aapka shopping experience behtar ho sake aur cart items aur location preferences ko aapke device par mehfooz rakha ja sake.
            </p>
        </div>
    </div>
    <div class="flex items-center justify-end gap-2.5">
        <button onclick="setCookieConsent('declined')" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-250 transition-colors">
            Decline
        </button>
        <button onclick="setCookieConsent('accepted')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/10 transition-all active:scale-95">
            Accept All
        </button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const consent = localStorage.getItem("cookie_consent");
    if (!consent) {
        setTimeout(() => {
            const banner = document.getElementById("cookie-consent-banner");
            if (banner) {
                banner.classList.remove("translate-y-20", "opacity-0", "pointer-events-none");
                banner.classList.add("translate-y-0", "opacity-100");
            }
        }, 1500);
    }
    
    // Background polling for order and demand statuses
    pollStatuses();
    setInterval(pollStatuses, 30000); // every 30 seconds
});

function requestNotificationPermission() {
    if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                new Notification('HR Traders', {
                    body: 'Shukriya! Ab aapko order aur demand status updates ke notifications milenge.',
                    icon: BASE_URL + 'assets/images/logo.png'
                });
                pollStatuses();
            }
        });
    }
}

function setCookieConsent(status) {
    localStorage.setItem("cookie_consent", status);
    const banner = document.getElementById("cookie-consent-banner");
    if (banner) {
        banner.classList.remove("translate-y-0", "opacity-100");
        banner.classList.add("translate-y-20", "opacity-0", "pointer-events-none");
        setTimeout(() => {
            banner.remove();
        }, 500);
    }
    if (status === 'accepted') {
        requestNotificationPermission();
    }
}

function triggerOrderNotification(orderId, status) {
    let title = 'Order Update - HR Traders';
    let body = '';
    const paddedId = String(orderId).padStart(5, '0');
    
    switch (status) {
        case 'pending':
            body = `Aapka order #HRT-${paddedId} pending status par hai.`;
            break;
        case 'packaging':
            body = `Aapka order #HRT-${paddedId} ab pack ho raha hai! 📦`;
            break;
        case 'out_for_delivery':
            body = `Aapka order #HRT-${paddedId} delivery ke liye nikal chuka hai! 🚴`;
            break;
        case 'delivered':
            body = `Aapka order #HRT-${paddedId} kamyabi se deliver ho chuka hai. Shukriya! 🎉`;
            break;
        case 'cancelled':
            body = `Aapka order #HRT-${paddedId} cancel kar diya gaya hai.`;
            break;
        default:
            body = `Aapke order #HRT-${paddedId} ka status ab '${status}' hai.`;
    }

    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: BASE_URL + 'assets/images/logo.png'
        });
    }
}

function triggerDemandNotification(demandId, status, details) {
    let title = 'Demand Box Update - HR Traders';
    let body = '';
    const itemTruncated = details.length > 30 ? details.substring(0, 30) + '...' : details;
    
    if (status === 'confirmed') {
        body = `Aapki demand "${itemTruncated}" confirm ho chuki hai! Humne iska intezam kar liya hai. 🛒`;
    } else {
        body = `Aapki demand "${itemTruncated}" ka status ab '${status}' hai.`;
    }

    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: BASE_URL + 'assets/images/logo.png'
        });
    }
}

function pollStatuses() {
    if (!('Notification' in window) || Notification.permission !== 'granted' || localStorage.getItem('cookie_consent') !== 'accepted') {
        return;
    }

    let placedOrders = [];
    let placedDemands = [];
    try {
        placedOrders = JSON.parse(localStorage.getItem('placed_orders') || '[]');
    } catch(e) {}
    try {
        placedDemands = JSON.parse(localStorage.getItem('placed_demands') || '[]');
    } catch(e) {}

    if (placedOrders.length === 0 && placedDemands.length === 0) {
        return;
    }

    const params = new URLSearchParams();
    if (placedOrders.length > 0) {
        params.append('ids', placedOrders.join(','));
    }
    if (placedDemands.length > 0) {
        params.append('demands', placedDemands.join(','));
    }

    fetch(BASE_URL + 'check_order_status.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let cachedStatuses = null;
                const cachedStr = localStorage.getItem('cached_statuses');
                if (cachedStr) {
                    try { cachedStatuses = JSON.parse(cachedStr); } catch(e) {}
                }

                const isFirstRun = (cachedStatuses === null);
                if (isFirstRun) {
                    cachedStatuses = {};
                }

                // Process orders
                if (data.orders && Array.isArray(data.orders)) {
                    data.orders.forEach(order => {
                        const orderId = order.id;
                        const newStatus = order.status;
                        const oldStatus = cachedStatuses['order_' + orderId];

                        if (!isFirstRun && oldStatus !== undefined && oldStatus !== newStatus) {
                            triggerOrderNotification(orderId, newStatus);
                        }
                        cachedStatuses['order_' + orderId] = newStatus;
                    });
                }

                // Process demands
                if (data.demands && Array.isArray(data.demands)) {
                    data.demands.forEach(demand => {
                        const demandId = demand.id;
                        const newStatus = demand.status;
                        const oldStatus = cachedStatuses['demand_' + demandId];

                        if (!isFirstRun && oldStatus !== undefined && oldStatus !== newStatus) {
                            triggerDemandNotification(demandId, newStatus, demand.demand_details);
                        }
                        cachedStatuses['demand_' + demandId] = newStatus;
                    });
                }

                localStorage.setItem('cached_statuses', JSON.stringify(cachedStatuses));
            }
        })
        .catch(err => console.error('Error polling statuses:', err));
}

function toggleDemandModal(show) {
    const modal = document.getElementById('demand-modal');
    if (!modal) return;
    
    if (show) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('.relative').classList.remove('scale-95');
            modal.querySelector('.relative').classList.add('scale-100');
        }, 50);
    } else {
        modal.classList.remove('opacity-100');
        modal.querySelector('.relative').classList.remove('scale-100');
        modal.querySelector('.relative').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden', 'opacity-0');
        }, 300);
    }
}

function openDemandModal() {
    toggleDemandModal(true);
}

function submitProductDemand() {
    const name = document.getElementById('demand-name').value;
    const phone = document.getElementById('demand-phone').value;
    const details = document.getElementById('demand-details').value;
    const btn = document.getElementById('demand-submit-btn');

    if (!name.trim() || !phone.trim() || !details.trim()) {
        alert("Please fill in all fields!");
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> Submitting...';

    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('details', details);

    fetch(BASE_URL + 'submit_demand.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane text-[10px]"></i> Submit Demand';
        
        if (data.success) {
            alert(data.message);
            if (data.id) {
                let placedDemands = [];
                try {
                    placedDemands = JSON.parse(localStorage.getItem('placed_demands') || '[]');
                } catch(e) {}
                const idVal = parseInt(data.id);
                if (!placedDemands.includes(idVal)) {
                    placedDemands.push(idVal);
                    localStorage.setItem('placed_demands', JSON.stringify(placedDemands));
                }
            }
            document.getElementById('demand-box-form').reset();
            toggleDemandModal(false);
        } else {
            alert(data.message || "Failed to submit request.");
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane text-[10px]"></i> Submit Demand';
        alert("An error occurred: " + err.message);
    });
}
</script>

<!-- MOBILE BOTTOM NAVIGATION BAR -->
<div class="fixed bottom-0 left-0 right-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] md:hidden z-40 flex items-center justify-around h-16 px-2">
    <!-- Home Tab -->
    <a href="<?php echo BASE_URL; ?>" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-emerald-600 transition-colors">
        <i class="fas fa-home text-lg"></i>
        <span class="text-[10px] font-bold mt-1">Home</span>
    </a>
    
    <!-- Shop Tab -->
    <a href="<?php echo BASE_URL; ?>shop.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-emerald-600 transition-colors">
        <i class="fas fa-store text-lg"></i>
        <span class="text-[10px] font-bold mt-1">Shop</span>
    </a>
    
    <!-- Demand Box Tab -->
    <button onclick="openDemandModal()" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-emerald-600 transition-colors focus:outline-none cursor-pointer">
        <i class="fas fa-clipboard-list text-lg"></i>
        <span class="text-[10px] font-bold mt-1">Demand</span>
    </button>
    
    <!-- Cart Tab -->
    <button onclick="toggleCartDrawer(true)" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-emerald-600 transition-colors relative focus:outline-none cursor-pointer">
        <i class="fas fa-shopping-basket text-lg"></i>
        <span id="mobile-cart-badge" class="absolute top-0.5 right-4 bg-emerald-600 text-white font-bold text-[9px] w-4.5 h-4.5 rounded-full flex items-center justify-center transition-all <?php echo $cart_count > 0 ? '' : 'hidden'; ?>">
            <?php echo $cart_count; ?>
        </span>
        <span class="text-[10px] font-bold mt-1">Cart</span>
    </button>
    
    <!-- Profile / Account Tab -->
    <?php if (is_logged_in()): ?>
        <a href="<?php echo BASE_URL; ?>my_account.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-emerald-600 transition-colors">
            <i class="fas fa-user-circle text-lg"></i>
            <span class="text-[10px] font-bold mt-1">Account</span>
        </a>
    <?php else: ?>
        <button onclick="openAuthModal()" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-emerald-600 transition-colors focus:outline-none cursor-pointer">
            <i class="fas fa-sign-in-alt text-lg"></i>
            <span class="text-[10px] font-bold mt-1">Login</span>
        </button>
    <?php endif; ?>
</div>

<style>
@media (max-w: 768px) {
    body {
        padding-bottom: 4rem !important;
    }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll(".fixed.bottom-0 a");
    navLinks.forEach(link => {
        if (currentPath === link.pathname || (link.pathname !== "<?php echo BASE_URL; ?>" && currentPath.includes(link.pathname))) {
            link.classList.remove("text-slate-500");
            link.classList.add("text-emerald-600");
        }
    });
});
</script>

<!-- Main storefront script -->
<script src="<?php echo BASE_URL; ?>assets/js/app.js?v=2.5"></script>
</body>
</html>
