<?php
// HR Traders Storefront Home Page
require_once __DIR__ . '/includes/header.php';

$announcement = get_setting('homepage_announcement', '');
$shop_status = get_setting('shop_status', 'open');
?>

<?php if ($shop_status === 'closed'): ?>
<!-- STORE CLOSED EMERGENCY BANNER -->
<div class="bg-rose-600 text-white py-3 px-4 text-center text-xs sm:text-sm font-bold tracking-wide relative overflow-hidden print-hidden z-20 flex items-center justify-center gap-2 shadow-lg">
    <i class="fas fa-circle-exclamation text-base animate-pulse"></i>
    <span>Notice: <?php echo htmlspecialchars(STORE_NAME); ?> is temporarily CLOSED. You can browse items, but checkout operations are disabled.</span>
</div>
<?php elseif (!empty($announcement)): ?>
<!-- TOP ANNOUNCEMENT BANNER -->
<div class="bg-emerald-600 text-white py-2.5 px-4 text-center text-xs font-semibold tracking-wider relative overflow-hidden print-hidden flex items-center justify-center gap-2 shadow-sm">
    <i class="fas fa-bullhorn text-xs animate-bounce"></i>
    <span><?php echo htmlspecialchars($announcement); ?></span>
</div>
<?php endif; ?>

<?php
// Fetch 6 featured products from the database
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 6");
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_products = [];
}
?>

<!-- DYNAMIC SLIDING BANNER (DEAL SLIDER) -->
<section class="relative bg-slate-100 border-b border-slate-200 h-[280px] sm:h-[400px] slider-container">
    
    <!-- Slide 1 -->
    <div class="slide active bg-gradient-to-r from-slate-200 via-slate-100 to-emerald-100/30 flex items-center justify-between px-6 sm:px-12 lg:px-24">
        <div class="max-w-xl space-y-3 sm:space-y-4">
            <span class="inline-block bg-emerald-100 text-emerald-700 border border-emerald-200/50 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Organic Crops</span>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 leading-tight">Fresh Pulses & Grains</h1>
            <p class="text-xs sm:text-sm text-slate-600">Directly sourced from premium farms. Pure, machine-cleaned, and packed under strict hygiene conditions for your health.</p>
            <a href="<?php echo BASE_URL; ?>shop.php?category=anaj" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-sm transition-all shadow-lg shadow-emerald-600/10">
                Explore Pulses <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="hidden md:block text-emerald-600/5 text-[180px] pr-12"><i class="fas fa-seedling"></i></div>
    </div>

    <!-- Slide 2 -->
    <div class="slide bg-gradient-to-r from-slate-200 via-slate-100 to-teal-100/30 flex items-center justify-between px-6 sm:px-12 lg:px-24">
        <div class="max-w-xl space-y-3 sm:space-y-4">
            <span class="inline-block bg-teal-100 text-teal-700 border border-teal-200/50 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Beat The Heat</span>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 leading-tight">Quench Your Thirst</h1>
            <p class="text-xs sm:text-sm text-slate-600">Carbonated soft drinks, juices, mineral water bottles, and energy drinks delivered straight to your doorstep ice cold.</p>
            <a href="<?php echo BASE_URL; ?>shop.php?category=beverages" class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl text-sm transition-all shadow-lg shadow-teal-600/10">
                Shop Beverages <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="hidden md:block text-teal-600/5 text-[180px] pr-12"><i class="fas fa-glass-water"></i></div>
    </div>

    <!-- Slide 3 -->
    <div class="slide bg-gradient-to-r from-slate-200 via-slate-100 to-cyan-100/30 flex items-center justify-between px-6 sm:px-12 lg:px-24">
        <div class="max-w-xl space-y-3 sm:space-y-4">
            <span class="inline-block bg-cyan-100 text-cyan-700 border border-cyan-200/50 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Frozen Delights</span>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 leading-tight">Frozen Ice Creams</h1>
            <p class="text-xs sm:text-sm text-slate-600">Family pack ice creams and chicken frozen snacks. *Available for nearby Lahore locations only to maintain cold-chain storage.</p>
            <a href="<?php echo BASE_URL; ?>shop.php?category=ice_cream" class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl text-sm transition-all shadow-lg shadow-cyan-600/10">
                Browse Frozen Items <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="hidden md:block text-cyan-600/5 text-[180px] pr-12"><i class="fas fa-ice-cream"></i></div>
    </div>

    <!-- Slide Dot Indicators -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 flex gap-2">
        <button onclick="setSlide(0)" class="w-3 h-3 rounded-full bg-slate-300 slide-dot transition-all"></button>
        <button onclick="setSlide(1)" class="w-3 h-3 rounded-full bg-slate-300 slide-dot transition-all"></button>
        <button onclick="setSlide(2)" class="w-3 h-3 rounded-full bg-slate-300 slide-dot transition-all"></button>
    </div>
</section>

<!-- CATEGORIES QUICK LOOKUP -->
<section class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
    <div class="text-center space-y-2 mb-10">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Browse By Categories</h2>
        <p class="text-sm text-slate-500">Select a category to filter your grocery requirements</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Category 1: Anaj -->
        <a href="<?php echo BASE_URL; ?>shop.php?category=anaj#shop-container" class="glass-card p-6 rounded-2xl flex flex-col items-center justify-center text-center gap-3">
            <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center border border-emerald-200">
                <img src="<?php echo BASE_URL; ?>assets/images/categories/anaj.png?v=2.1" alt="Anaj" class="w-full h-full object-cover">
            </div>
            <div>
                <h4 class="font-bold text-slate-800">Anaj</h4>
                <p class="text-[10px] text-slate-500 urdu-text tracking-wider">اناج</p>
            </div>
        </a>

        <!-- Category 2: Ice Cream -->
        <a href="<?php echo BASE_URL; ?>shop.php?category=ice_cream#shop-container" class="glass-card p-6 rounded-2xl flex flex-col items-center justify-center text-center gap-3">
            <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center border border-cyan-200">
                <img src="<?php echo BASE_URL; ?>assets/images/categories/ice_cream.png?v=2.1" alt="Ice Cream" class="w-full h-full object-cover">
            </div>
            <div>
                <h4 class="font-bold text-slate-800">Ice Cream</h4>
                <p class="text-[10px] text-slate-550 urdu-text tracking-wider">آئس کریم</p>
            </div>
        </a>

        <!-- Category 3: Beverages -->
        <a href="<?php echo BASE_URL; ?>shop.php?category=beverages#shop-container" class="glass-card p-6 rounded-2xl flex flex-col items-center justify-center text-center gap-3">
            <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center border border-teal-200">
                <img src="<?php echo BASE_URL; ?>assets/images/categories/cold_drinks.png?v=2.1" alt="Beverages" class="w-full h-full object-cover">
            </div>
            <div>
                <h4 class="font-bold text-slate-800">Beverages</h4>
                <p class="text-[10px] text-slate-500 urdu-text tracking-wider">مشروبات</p>
            </div>
        </a>

        <!-- Category 5: Milk -->
        <a href="<?php echo BASE_URL; ?>shop.php?category=milk#shop-container" class="glass-card p-6 rounded-2xl flex flex-col items-center justify-center text-center gap-3">
            <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center border border-amber-200">
                <img src="<?php echo BASE_URL; ?>assets/images/categories/milk.png?v=2.1" alt="Milk" class="w-full h-full object-cover">
            </div>
            <div>
                <h4 class="font-bold text-slate-800">Milk</h4>
                <p class="text-[10px] text-slate-500 urdu-text tracking-wider">دودھ</p>
            </div>
        </a>

        <!-- Category 6: Cosmetics -->
        <a href="<?php echo BASE_URL; ?>shop.php?category=cosmetics#shop-container" class="glass-card p-6 rounded-2xl flex flex-col items-center justify-center text-center gap-3">
            <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center border border-rose-200">
                <img src="<?php echo BASE_URL; ?>assets/images/categories/cosmetics.png?v=2.1" alt="Cosmetics" class="w-full h-full object-cover">
            </div>
            <div>
                <h4 class="font-bold text-slate-800">Cosmetics</h4>
                <p class="text-[10px] text-slate-500 urdu-text tracking-wider">کاسمیٹکس</p>
            </div>
        </a>
    </div>
</section>

<!-- FEATURED PRODUCTS GRID -->
<section class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 border-t border-slate-200">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Latest Arrivals</h2>
            <p class="text-xs text-slate-500 mt-1">Fresh stock newly updated in our inventory catalog</p>
        </div>
        <a href="<?php echo BASE_URL; ?>shop.php" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 flex items-center gap-1.5 border border-emerald-200 bg-emerald-50 px-3 py-1.5 rounded-lg transition-colors">
            View All Stock <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($featured_products)): ?>
            <div class="col-span-full py-16 text-center text-slate-400">
                <p>No products available yet. Run the system installer or update products in database.</p>
            </div>
        <?php else: ?>
            <?php foreach ($featured_products as $product): ?>
                <?php 
                $is_frozen = $product['category'] === 'ice_cream'; 
                $is_out_of_stock = $product['stock_quantity'] <= 0;
                ?>
                   <div class="glass-card rounded-2xl overflow-hidden flex flex-col border <?php echo $is_frozen ? 'frozen-alert-border border-rose-500/20' : 'border-slate-200'; ?>">
                    
                    <!-- Product Image representation -->
                    <div onclick="openProductDetails(<?php echo $product['id']; ?>)" class="h-48 bg-slate-50 flex items-center justify-center relative border-b border-slate-200 overflow-hidden cursor-pointer group">
                        <?php 
                        $img_src = !empty($product['image']) ? BASE_URL . $product['image'] : BASE_URL . 'assets/images/placeholder.svg';
                        ?>
                        <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($product['name']); ?>" class="w-full h-full object-cover transition-transform group-hover:scale-105 duration-300" loading="lazy">
                        
                        <!-- Floating Category Label -->
                        <span class="absolute top-3 left-3 px-2 py-0.5 rounded-lg text-[10px] uppercase font-bold bg-white/90 backdrop-blur-sm border border-slate-200 text-slate-655 shadow-sm">
                            <?php echo $CATEGORIES[$product['category']]['name'] ?? $product['category']; ?>
                        </span>

                        <!-- Stock Status -->
                        <?php if ($is_out_of_stock): ?>
                            <span class="absolute top-3 right-3 px-2 py-0.5 rounded-lg text-[9px] uppercase font-bold bg-rose-600 text-white shadow-sm">
                                Sold Out
                            </span>
                        <?php else: ?>
                            <span class="absolute top-3 right-3 px-2 py-0.5 rounded-lg text-[9px] uppercase font-bold bg-emerald-50/90 backdrop-blur-sm border border-emerald-250 text-emerald-700 shadow-sm">
                                In Stock (<?php echo $product['stock_quantity']; ?>)
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base leading-tight truncate-2-lines">
                                    <span class="cursor-pointer hover:text-emerald-600 transition-colors" onclick="openProductDetails(<?php echo $product['id']; ?>)">
                                        <?php echo sanitize($product['name']); ?>
                                    </span>
                                </h3>
                                <p class="text-xs text-slate-555 line-clamp-2 mt-1 min-h-[32px]"><?php echo sanitize($product['description'] ?: 'No description provided.'); ?></p>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span><?php echo sanitize($product['weight']); ?></span>
                                <span class="font-mono text-[10px] text-slate-400">Barcode: <?php echo sanitize($product['barcode']); ?></span>
                            </div>
                        </div>

                        <!-- Frozen Warn alert if category matches -->
                        <?php if ($is_frozen): ?>
                            <div class="bg-rose-50 border border-rose-250 rounded-xl p-3 text-center">
                                <span class="text-xs text-rose-600 font-bold block">
                                    <i class="fas fa-triangle-exclamation mr-1"></i> Nearby Deliveries Only
                                </span>
                                <span class="urdu-text text-[10px] text-rose-600 block font-semibold">یہ آئٹم صرف قریبی علاقوں کے لئے دستیاب ہے</span>
                            </div>
                        <?php endif; ?>

                        <!-- Pricing & Buy -->
                        <div class="flex items-center justify-between pt-2">
                            <div>
                                <span class="text-xs text-slate-500 block">Selling Price</span>
                                <span class="text-lg font-black text-emerald-600"><?php echo format_price($product['price']); ?></span>
                            </div>
                            
                            <?php if ($is_out_of_stock): ?>
                                <button disabled class="px-4 py-2.5 bg-slate-100 text-slate-400 rounded-xl font-bold text-xs cursor-not-allowed">
                                    Out of Stock
                                </button>
                            <?php else: ?>
                                <div class="flex items-center gap-1.5 flex-nowrap">
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 active:scale-95 text-slate-700 rounded-xl font-bold text-xs transition-all flex items-center gap-1">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                    <button onclick="buyNow(<?php echo $product['id']; ?>)" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl font-black text-xs transition-all shadow-md shadow-emerald-600/10 flex items-center gap-1">
                                        Buy Now
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- JAVASCRIPT FOR DEAL SLIDER -->
<script>
let activeSlideIndex = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.slide-dot');

function showSlide(index) {
    slides.forEach((slide, i) => {
        if (i === index) {
            slide.classList.add('active');
            dots[i].classList.remove('bg-slate-300');
            dots[i].classList.add('bg-emerald-600', 'w-6'); // active dot style
        } else {
            slide.classList.remove('active');
            dots[i].classList.add('bg-slate-300');
            dots[i].classList.remove('bg-emerald-600', 'w-6');
        }
    });
    activeSlideIndex = index;
}

function setSlide(index) {
    showSlide(index);
}

// Auto slider rotation
let slideInterval = setInterval(() => {
    let next = (activeSlideIndex + 1) % slides.length;
    showSlide(next);
}, 5000);

// Initialize slide dots
showSlide(0);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
