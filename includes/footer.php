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
                        <span class="text-[10px] font-bold text-slate-700 block uppercase tracking-wider">Branch 1 (Tando Adam)</span>
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
                <span class="font-bold text-xs text-slate-800 block leading-tight">Branch 1 (Tando Adam)</span>
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


<!-- PRODUCT DETAILS MODAL (POPUP OVERLAY) -->
<div id="product-details-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300 opacity-0 pointer-events-none" onclick="if(event.target === this) closeProductDetails()">
    <div class="relative bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform scale-95 transition-all duration-300 flex flex-col md:flex-row border border-slate-200">
        
        <!-- Close Button -->
        <button onclick="closeProductDetails()" class="absolute top-4 right-4 z-10 w-9 h-9 rounded-full bg-slate-100/90 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors flex items-center justify-center shadow-sm">
            <i class="fas fa-times text-lg"></i>
        </button>

        <!-- Left Column: Image & Specs -->
        <div class="w-full md:w-5/12 bg-slate-50 border-r border-slate-200 flex flex-col justify-between p-6 md:p-8">
            <div class="flex-1 flex items-center justify-center min-h-[200px] max-h-[300px] md:max-h-none py-6">
                <img id="modal-product-img" src="" alt="" class="max-h-[240px] md:max-h-[320px] max-w-full object-contain rounded-2xl drop-shadow-md transition-transform hover:scale-105 duration-350">
            </div>
            
            <div class="mt-4 pt-4 border-t border-slate-200/60 space-y-2.5 text-xs text-slate-500">
                <div class="flex justify-between items-center">
                    <span class="font-medium text-slate-400">Unit / Weight:</span>
                    <span id="modal-product-weight" class="font-bold text-slate-700"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium text-slate-400">Barcode / SKU:</span>
                    <span id="modal-product-barcode" class="font-mono font-bold text-slate-700"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium text-slate-400">Category:</span>
                    <span id="modal-product-category" class="font-bold text-slate-700 uppercase"></span>
                </div>
            </div>
        </div>

        <!-- Right Column: Info, Reviews & Review Form -->
        <div class="w-full md:w-7/12 p-6 md:p-8 flex flex-col max-h-[50vh] md:max-h-[90vh] overflow-y-auto">
            <!-- Product Title and Meta -->
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span id="modal-product-stock-badge" class="px-2.5 py-0.5 rounded-lg text-[9px] font-extrabold uppercase"></span>
                </div>
                <h2 id="modal-product-name" class="text-2xl font-black text-slate-900 leading-tight"></h2>
                
                <!-- Rating Score Summary -->
                <div class="flex items-center gap-2">
                    <div id="modal-average-stars" class="flex text-amber-400 text-sm gap-0.5">
                        <!-- Populated dynamically -->
                    </div>
                    <span id="modal-average-score" class="text-sm font-extrabold text-slate-800">0.0</span>
                    <span id="modal-total-reviews-count" class="text-xs text-slate-400">(0 reviews)</span>
                </div>
            </div>

            <!-- Price & Buy actions -->
            <div class="mt-4 bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <span class="text-[10px] text-emerald-700 block font-bold uppercase tracking-wider">Selling Price</span>
                    <span id="modal-product-price" class="text-2xl font-black text-emerald-600"></span>
                </div>
                
                <!-- Action buttons -->
                <div id="modal-actions" class="flex items-center gap-2 w-full sm:w-auto">
                    <!-- Dynamic Cart Buttons populated by JS -->
                </div>
            </div>

            <!-- Description -->
            <div class="mt-5 space-y-1.5">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Product Details</h4>
                <p id="modal-product-desc" class="text-sm text-slate-650 leading-relaxed"></p>
            </div>

            <hr class="my-6 border-slate-200">

            <!-- Reviews Section -->
            <div class="space-y-5 flex-1 flex flex-col">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-comments text-emerald-600"></i> Customer Reviews
                </h3>

                <!-- Reviews list scroll container -->
                <div id="modal-reviews-list" class="space-y-4 max-h-[220px] overflow-y-auto pr-1">
                    <!-- Populated dynamically -->
                </div>

                <hr class="border-slate-200 my-2">

                <!-- Submit review Form -->
                <div class="bg-slate-50 border border-slate-200/60 rounded-2xl p-4">
                    <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i class="fas fa-pen-nib text-xs text-emerald-600"></i> Share Your Review
                    </h4>
                    
                    <form id="product-review-form" onsubmit="event.preventDefault(); submitProductReview();" class="space-y-3">
                        <input type="hidden" id="review-product-id" value="">
                        
                        <!-- Reviewer Name & Stars -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="review-name" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Your Name</label>
                                <input type="text" id="review-name" required placeholder="Enter your name" 
                                       class="w-full px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 placeholder-slate-400">
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Your Rating</span>
                                <div class="flex items-center gap-2 text-slate-300 text-2xl h-[34px]" id="review-stars-selector">
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-value="1"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-value="2"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-value="3"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-value="4"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-value="5"></i>
                                </div>
                                <input type="hidden" id="review-rating" value="0">
                            </div>
                        </div>

                        <!-- Comment -->
                        <div>
                            <label for="review-comment" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Your Comment</label>
                            <textarea id="review-comment" required rows="2" placeholder="Tell others what you think about this product..." 
                                      class="w-full px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 placeholder-slate-400 resize-none"></textarea>
                        </div>

                        <!-- Submit button -->
                        <button type="submit" id="review-submit-btn" 
                                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/10 active:scale-95">
                            <i class="fas fa-paper-plane text-[10px]"></i> Submit Review
                        </button>
                    </form>
                </div>
            </div>

        </div>

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
});

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
}
</script>

<!-- Main storefront script -->
<script src="<?php echo BASE_URL; ?>assets/js/app.js?v=2.1"></script>
</body>
</html>
