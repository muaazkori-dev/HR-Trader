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
<?php
$hero_banners_json = get_setting('store_hero_banners', '');
if (empty($hero_banners_json)) {
    $default_banners = [
        [
            'image' => 'assets/images/hero_grocery_banner.png',
            'tag' => 'Premium Choice',
            'title' => 'Your Premium Grocery Partner',
            'subtitle' => 'Fresh organic crops, groceries, and premium household brands delivered straight to your home.',
            'link' => 'shop.php',
            'color_theme' => 'emerald'
        ],
        [
            'image' => '',
            'tag' => 'Beat The Heat',
            'title' => 'Quench Your Thirst',
            'subtitle' => 'Soft drinks, juices, mineral water bottles, and energy drinks delivered straight to your doorstep ice cold.',
            'link' => 'shop.php?category=beverages',
            'color_theme' => 'teal'
        ],
        [
            'image' => '',
            'tag' => 'Frozen Delights',
            'title' => 'Frozen Ice Creams',
            'subtitle' => 'Family pack ice creams and chicken frozen snacks. *Available for nearby locations to maintain cold chain.',
            'link' => 'shop.php?category=ice_cream',
            'color_theme' => 'cyan'
        ]
    ];
    $hero_banners = $default_banners;
    update_setting('store_hero_banners', json_encode($hero_banners));
} else {
    $hero_banners = json_decode($hero_banners_json, true);
}
?>
<section class="relative bg-slate-50 dark:bg-slate-950 py-8 overflow-hidden border-b border-slate-200/50">
    <div class="slider-container max-w-7xl mx-auto relative px-4 overflow-visible">
        <div class="slider-track flex gap-4 md:gap-6 transition-transform duration-500 ease-out" style="width: max-content;">
            
            <?php foreach ($hero_banners as $idx => $banner): 
                $has_image = !empty($banner['image']) && file_exists(__DIR__ . '/' . $banner['image']);
                $bg_style = $has_image ? "background-image: url('" . BASE_URL . htmlspecialchars($banner['image']) . "');" : "";
                
                $theme = $banner['color_theme'] ?? 'emerald';
                $tag_class = "bg-emerald-600 text-white";
                $btn_class = "bg-emerald-600 hover:bg-emerald-500 text-white";
                $icon_class = "text-emerald-600/10";
                $card_class = "bg-cover bg-center relative";
                
                if (!$has_image) {
                    if ($theme === 'teal') {
                        $card_class = "bg-gradient-to-r from-slate-200 via-slate-100 to-teal-100/30 flex items-center justify-between px-6 sm:px-12";
                        $tag_class = "bg-teal-100 text-teal-700";
                        $btn_class = "bg-teal-600 hover:bg-teal-500 text-white";
                        $icon_class = "text-teal-600/10";
                    } elseif ($theme === 'cyan') {
                        $card_class = "bg-gradient-to-r from-slate-200 via-slate-100 to-cyan-100/30 flex items-center justify-between px-6 sm:px-12";
                        $tag_class = "bg-cyan-100 text-cyan-700";
                        $btn_class = "bg-cyan-600 hover:bg-cyan-500 text-white";
                        $icon_class = "text-cyan-600/10";
                    } else {
                        $card_class = "bg-gradient-to-r from-slate-200 via-slate-100 to-emerald-100/30 flex items-center justify-between px-6 sm:px-12";
                        $tag_class = "bg-emerald-100 text-emerald-700";
                        $btn_class = "bg-emerald-600 hover:bg-emerald-500 text-white";
                        $icon_class = "text-emerald-600/10";
                    }
                } else {
                    $tag_class = "bg-emerald-600 text-white";
                    $btn_class = "bg-emerald-600 hover:bg-emerald-500 text-white";
                }
            ?>
            <!-- Dynamic Slide Card -->
            <div class="slide-card flex-shrink-0 w-[82vw] sm:w-[65vw] lg:w-[55vw] h-[240px] sm:h-[350px] rounded-[32px] overflow-hidden border border-slate-200/60 dark:border-slate-800 transition-all duration-500 <?php echo $card_class; ?>" style="<?php echo $bg_style; ?>">
                <?php if ($has_image): ?>
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-900/80 via-slate-900/35 to-transparent flex items-center px-6 sm:px-12">
                        <div class="max-w-md space-y-2 sm:space-y-3 text-left text-white">
                            <span class="inline-block <?php echo $tag_class; ?> text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider font-sans"><?php echo htmlspecialchars($banner['tag']); ?></span>
                            <h1 class="text-xl sm:text-4xl font-black leading-tight text-white"><?php echo htmlspecialchars($banner['title']); ?></h1>
                            <p class="text-[10px] sm:text-xs text-slate-200 leading-relaxed font-sans"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                            <a href="<?php echo BASE_URL . htmlspecialchars($banner['link']); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 <?php echo $btn_class; ?> font-bold rounded-xl text-xs transition-all shadow-md font-sans">
                                Shop Now <i class="fas fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="max-w-md space-y-2 sm:space-y-3 text-left">
                        <span class="inline-block <?php echo $tag_class; ?> text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider font-sans"><?php echo htmlspecialchars($banner['tag']); ?></span>
                        <h1 class="text-xl sm:text-4xl font-black text-slate-900 dark:text-white leading-tight"><?php echo htmlspecialchars($banner['title']); ?></h1>
                        <p class="text-[10px] sm:text-xs text-slate-550 dark:text-slate-400 leading-relaxed font-sans"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                        <a href="<?php echo BASE_URL . htmlspecialchars($banner['link']); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 <?php echo $btn_class; ?> font-bold rounded-xl text-xs transition-all shadow-md font-sans">
                            Shop Now <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                    <div class="hidden sm:block <?php echo $icon_class; ?> text-9xl pr-4">
                        <?php if ($theme === 'teal'): ?>
                            <i class="fas fa-glass-water"></i>
                        <?php elseif ($theme === 'cyan'): ?>
                            <i class="fas fa-ice-cream"></i>
                        <?php else: ?>
                            <i class="fas fa-basket-shopping"></i>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        </div>
        
        <!-- Slide Dot Indicators -->
        <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 z-10 flex gap-2">
            <?php foreach ($hero_banners as $idx => $banner): ?>
                <button onclick="setSlide(<?php echo $idx; ?>)" class="w-3 h-3 rounded-full bg-slate-300 slide-dot transition-all"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CATEGORIES QUICK LOOKUP -->
<section class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8 border-t border-slate-200/50">
    <div class="text-center space-y-2 mb-10">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">Browse By Categories</h2>
        <p class="text-sm text-slate-500">Select a category to filter your grocery requirements</p>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4">
        <?php 
        foreach ($CATEGORIES as $cat_key => $cat): 
            if (isset($cat['parent'])) continue; // Skip subcategories
        ?>
        <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo urlencode($cat_key); ?>#shop-container" 
           class="glass-card p-4 sm:p-5 rounded-2xl flex flex-col items-center justify-center text-center gap-2.5 w-[105px] sm:w-[120px] flex-shrink-0 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full overflow-hidden flex items-center justify-center border border-slate-200/60 bg-white">
                <img src="<?php echo get_category_icon_url($cat_key); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" class="w-full h-full object-cover">
            </div>
            <div class="min-w-0 w-full">
                <h4 class="font-bold text-slate-800 dark:text-slate-205 text-xs sm:text-sm truncate"><?php echo htmlspecialchars($cat['name']); ?></h4>
                <?php if (!empty($cat['urdu'])): ?>
                    <p class="text-[9px] sm:text-[10px] text-slate-550 urdu-text tracking-wider truncate"><?php echo htmlspecialchars($cat['urdu']); ?></p>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
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
                    <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="block h-48 bg-slate-50 flex items-center justify-center relative border-b border-slate-200 overflow-hidden cursor-pointer group">
                        <?php 
                        $img_src = !empty($product['image']) ? BASE_URL . $product['image'] : BASE_URL . 'assets/images/placeholder.svg';
                        ?>
                        <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($product['name']); ?>" class="w-full h-full object-cover transition-transform group-hover:scale-105 duration-300" loading="lazy">
                        
                        <!-- Floating Category Label -->
                        <span class="absolute top-3 left-3 px-2 py-0.5 rounded-lg text-[10px] uppercase font-bold bg-white/90 backdrop-blur-sm border border-slate-200 text-slate-655 shadow-sm">
                            <?php echo $CATEGORIES[$product['category']]['name'] ?? $product['category']; ?>
                        </span>

                        <?php if ($product['old_price'] > $product['price'] || $product['discount_percentage'] > 0): ?>
                            <?php 
                            $pct = $product['discount_percentage'] ?: (int)round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); 
                            ?>
                            <span class="absolute top-10 left-3 px-2 py-0.5 rounded-lg text-[9px] uppercase font-bold bg-rose-600 text-white shadow-md animate-bounce">
                                Flat <?php echo $pct; ?>% OFF
                            </span>
                        <?php endif; ?>

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
                    </a>

                    <!-- Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base leading-tight truncate-2-lines">
                                    <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="hover:text-emerald-600 transition-colors">
                                        <?php echo sanitize($product['name']); ?>
                                    </a>
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
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                            <div class="flex items-center justify-between w-full sm:w-auto sm:block">
                                <span class="text-xs text-slate-500 block">Selling Price</span>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-lg font-black text-emerald-600"><?php echo format_price($product['price']); ?></span>
                                    <?php if ($product['old_price'] > $product['price'] || $product['discount_percentage'] > 0): ?>
                                        <?php 
                                        $old_pr = $product['old_price'] ?: ($product['price'] / (1 - ($product['discount_percentage'] / 100))); 
                                        ?>
                                        <span class="text-xs font-bold text-slate-400 line-through"><?php echo format_price($old_pr); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($is_out_of_stock): ?>
                                <button disabled class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 text-slate-400 rounded-xl font-bold text-xs cursor-not-allowed text-center">
                                    Out of Stock
                                </button>
                            <?php else: ?>
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="flex-1 sm:flex-none justify-center px-3 py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 active:scale-95 text-slate-700 rounded-xl font-bold text-xs transition-all flex items-center gap-1 cursor-pointer">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                    <button onclick="buyNow(<?php echo $product['id']; ?>)" class="flex-1 sm:flex-none justify-center px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl font-black text-xs transition-all shadow-md shadow-emerald-600/10 flex items-center gap-1 cursor-pointer">
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

function showSlide(index) {
    const container = document.querySelector('.slider-container');
    const track = document.querySelector('.slider-track');
    const slides = document.querySelectorAll('.slide-card');
    const dots = document.querySelectorAll('.slide-dot');
    
    if (!container || !track || slides.length === 0) return;
    
    activeSlideIndex = index;
    
    slides.forEach((slide, i) => {
        if (i === index) {
            slide.classList.add('scale-100', 'opacity-100', 'shadow-xl');
            slide.classList.remove('scale-90', 'opacity-50');
            if (dots[i]) {
                dots[i].classList.remove('bg-slate-300');
                dots[i].classList.add('bg-emerald-600', 'w-6');
            }
        } else {
            slide.classList.remove('scale-100', 'opacity-100', 'shadow-xl');
            slide.classList.add('scale-90', 'opacity-50');
            if (dots[i]) {
                dots[i].classList.add('bg-slate-300');
                dots[i].classList.remove('bg-emerald-600', 'w-6');
            }
        }
    });
    
    // Calculate translate offset to center the active slide dynamically
    const activeSlide = slides[index];
    const containerWidth = container.offsetWidth;
    const slideWidth = activeSlide.offsetWidth;
    const slideLeft = activeSlide.offsetLeft;
    
    const translateX = (containerWidth / 2) - (slideLeft + (slideWidth / 2));
    track.style.transform = `translateX(${translateX}px)`;
    // Mobile Swipe/Touch Control
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    
    container.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        isDragging = true;
        clearInterval(slideInterval);
    }, {passive: true});
    
    container.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
    }, {passive: true});
    
    container.addEventListener('touchend', () => {
        if (!isDragging) return;
        isDragging = false;
        const diffX = startX - currentX;
        if (Math.abs(diffX) > 50) {
            if (diffX > 0) {
                let next = (activeSlideIndex + 1) % slides.length;
                showSlide(next);
            } else {
                let prev = (activeSlideIndex - 1 + slides.length) % slides.length;
                showSlide(prev);
            }
        }
        startInterval();
    });
}

function setSlide(index) {
    showSlide(index);
    resetInterval();
}

let slideInterval;
function startInterval() {
    slideInterval = setInterval(() => {
        const slides = document.querySelectorAll('.slide-card');
        if (slides.length > 0) {
            let next = (activeSlideIndex + 1) % slides.length;
            showSlide(next);
        }
    }, 5000);
}
function resetInterval() {
    clearInterval(slideInterval);
    startInterval();
}

window.addEventListener('load', () => {
    showSlide(0);
    startInterval();
});
window.addEventListener('resize', () => {
    showSlide(activeSlideIndex);
});
document.addEventListener('DOMContentLoaded', () => {
    showSlide(0);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
