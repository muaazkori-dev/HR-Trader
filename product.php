<?php
// HR Traders Dedicated Product Details Page
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header("Location: " . BASE_URL . "shop.php");
    exit();
}

// 1. Fetch Product details
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        header("Location: " . BASE_URL . "shop.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database Query Failed: " . $e->getMessage());
}

// 2. Handle POST review submission
$review_success = isset($_GET['success_msg']) ? $_GET['success_msg'] : '';
$review_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $reviewer_name = trim($_POST['reviewer_name'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if (empty($reviewer_name) || $rating < 1 || $rating > 5 || empty($comment)) {
        $review_error = "Please fill in all fields and select a star rating.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO reviews (product_id, reviewer_name, rating, comment) VALUES (:pid, :name, :rating, :comment)");
            $stmt->execute([
                'pid' => $product_id,
                'name' => $reviewer_name,
                'rating' => $rating,
                'comment' => $comment
            ]);
            
            // Redirect to prevent form resubmission
            header("Location: " . BASE_URL . "product.php?id=" . $product_id . "&success_msg=" . urlencode("Shukriya! Aap ka review submit ho gaya hai."));
            exit();
        } catch (PDOException $e) {
            $review_error = "Failed to save your review. Please try again.";
        }
    }
}

// 3. Fetch reviews for this product
try {
    $stmt = $pdo->prepare("SELECT reviewer_name, rating, comment, created_at FROM reviews WHERE product_id = :pid ORDER BY id DESC");
    $stmt->execute(['pid' => $product_id]);
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}

// Calculate reviews average
$total_reviews = count($reviews);
$average_rating = 0.0;
if ($total_reviews > 0) {
    $rating_sum = 0;
    foreach ($reviews as $rev) {
        $rating_sum += (int)$rev['rating'];
    }
    $average_rating = round($rating_sum / $total_reviews, 1);
}

// Set SEO configuration tags before requiring header
$seo_title = htmlspecialchars($product['name']) . " - Buy Online | " . STORE_NAME;
$seo_desc = "Order " . htmlspecialchars($product['name']) . " for only " . format_price($product['price']) . " at " . STORE_NAME . ". " . substr(strip_tags($product['description']), 0, 150) . "...";

require_once __DIR__ . '/includes/header.php';

$is_frozen = ($product['category'] === 'ice_cream');
$is_out_of_stock = ($product['stock_quantity'] <= 0);
$img_src = !empty($product['image']) ? BASE_URL . $product['image'] : BASE_URL . 'assets/images/placeholder.svg';
?>

<div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
    
    <!-- Breadcrumbs / Back button -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <nav class="flex text-xs font-semibold text-slate-500 gap-1.5 uppercase tracking-wider">
            <a href="<?php echo BASE_URL; ?>" class="hover:text-emerald-600 transition-colors">Home</a>
            <span>/</span>
            <a href="<?php echo BASE_URL; ?>shop.php" class="hover:text-emerald-600 transition-colors">Shop</a>
            <span>/</span>
            <span class="text-slate-800"><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
        
        <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 hover:text-emerald-655 transition-colors border border-slate-200 bg-white px-3 py-1.5 rounded-xl shadow-sm">
            <i class="fas fa-arrow-left"></i> Back to Shop
        </a>
    </div>

    <!-- MAIN PRODUCT DETAIL GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- LEFT COLUMN: Product Image -->
        <div class="lg:col-span-5">
            <!-- Image Card -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm flex items-center justify-center relative min-h-[300px] sm:min-h-[400px]">
                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="max-h-[280px] sm:max-h-[360px] max-w-full object-contain rounded-2xl drop-shadow-lg transition-transform hover:scale-105 duration-350">
                
                <!-- Category Label -->
                <span class="absolute top-4 left-4 px-3 py-1 rounded-xl text-[10px] uppercase font-bold bg-white/90 backdrop-blur-sm border border-slate-200 text-slate-655 shadow-sm">
                    <?php echo $CATEGORIES[$product['category']]['name'] ?? $product['category']; ?>
                </span>

                <!-- Stock Label -->
                <?php if ($is_out_of_stock): ?>
                    <span class="absolute top-4 right-4 px-3 py-1 rounded-xl text-[10px] uppercase font-bold bg-rose-600 text-white shadow-sm">
                        Sold Out
                    </span>
                <?php else: ?>
                    <span class="absolute top-4 right-4 px-3 py-1 rounded-xl text-[10px] uppercase font-bold bg-emerald-50/90 backdrop-blur-sm border border-emerald-250 text-emerald-700 shadow-sm">
                        In Stock (<?php echo $product['stock_quantity']; ?>)
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: Title, Pricing, Actions -->
        <div class="lg:col-span-7">
            <!-- Title Card -->
            <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-4">
                
                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h1>
                    
                    <!-- Rating summary stars -->
                    <div class="flex items-center gap-2">
                        <div class="flex text-amber-400 text-sm gap-0.5">
                            <?php
                            $floorRating = floor($average_rating);
                            $hasHalf = ($average_rating - $floorRating) >= 0.4;
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $floorRating) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i === $floorRating + 1 && $hasHalf) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star text-slate-300"></i>';
                                }
                            }
                            ?>
                        </div>
                        <span class="text-sm font-extrabold text-slate-850"><?php echo $average_rating; ?></span>
                        <span class="text-xs text-slate-450">(<?php echo $total_reviews; ?> review<?php echo $total_reviews !== 1 ? 's' : ''; ?>)</span>
                    </div>
                </div>

                <!-- Frozen Zone Alert if category matches -->
                <?php if ($is_frozen): ?>
                    <div class="bg-rose-50 border border-rose-250 rounded-2xl p-4 flex gap-3 text-xs text-rose-700">
                        <i class="fas fa-triangle-exclamation text-base mt-0.5 flex-shrink-0"></i>
                        <div>
                            <span class="font-bold block">Available for nearby locations only!</span>
                            <span class="mt-0.5 block">Standard temperature control ki waja se frozen products sirf qareebi zones me deliver kiye jatay hain.</span>
                            <span class="urdu-text font-bold text-sm block mt-1 tracking-wide">یہ آئٹم صرف قریبی علاقوں کے لئے ہوم ڈیلیوری کے لئے دستیاب ہے۔</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Price and Buy panel -->
                <div class="bg-emerald-50/50 border border-emerald-100 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-5 mt-4">
                    <div>
                        <span class="text-[10px] text-emerald-700 block font-bold uppercase tracking-wider mb-1">Selling Price</span>
                        <span class="text-3xl font-black text-emerald-600"><?php echo format_price($product['price']); ?></span>
                    </div>
                    
                    <!-- Qty and Add/Buy actions -->
                    <?php if ($is_out_of_stock): ?>
                        <button disabled class="w-full sm:w-auto px-8 py-3 bg-slate-105 text-slate-400 rounded-xl font-bold text-xs cursor-not-allowed">
                            Out of Stock
                        </button>
                    <?php else: ?>
                        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                            <!-- Qty selector widget -->
                            <div class="flex items-center bg-white border border-slate-300 rounded-xl h-11">
                                <button onclick="adjustPageQty(-1)" class="w-9 h-full flex items-center justify-center text-slate-500 hover:text-slate-850 font-bold focus:outline-none cursor-pointer">-</button>
                                <input type="number" id="product-page-qty" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" readonly
                                       class="w-10 text-center font-bold text-slate-855 text-sm focus:outline-none border-none bg-transparent">
                                <button onclick="adjustPageQty(1)" class="w-9 h-full flex items-center justify-center text-slate-500 hover:text-slate-850 font-bold focus:outline-none cursor-pointer">+</button>
                            </div>
                            
                            <!-- Action buttons -->
                            <button onclick="addQtyToCart(<?php echo $product['id']; ?>)" class="flex-1 sm:flex-none h-11 px-5 bg-slate-100 hover:bg-slate-200 border border-slate-350 active:scale-95 text-slate-755 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                <i class="fas fa-plus"></i> Add to Cart
                            </button>
                            
                            <button onclick="buyQtyNow(<?php echo $product['id']; ?>)" class="flex-1 sm:flex-none h-11 px-6 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl font-black text-xs transition-all shadow-lg shadow-emerald-600/10 flex items-center justify-center gap-1.5 cursor-pointer">
                                Buy Now
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>

    <!-- SECONDARY PRODUCT SPECS & DETAILS GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mt-8">
        
        <!-- LEFT COLUMN: Product Specifications -->
        <div class="lg:col-span-5">
            <!-- Specs Table Card -->
            <div class="glass-panel p-5 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-4">
                <h3 class="font-bold text-xs uppercase tracking-wider text-slate-400">Product Specifications</h3>
                <div class="divide-y divide-slate-100 text-xs text-slate-700">
                    <div class="flex justify-between py-2.5">
                        <span class="font-medium text-slate-500">Unit / Weight</span>
                        <span class="font-bold text-slate-800"><?php echo htmlspecialchars($product['weight'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <span class="font-medium text-slate-500">Barcode / SKU</span>
                        <span class="font-mono font-bold text-slate-800"><?php echo htmlspecialchars($product['barcode'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <span class="font-medium text-slate-500">Inventory Category</span>
                        <span class="font-bold text-slate-800 uppercase"><?php echo str_replace('_', ' ', $product['category']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Description & Reviews -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Description Card -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-3">
                <h3 class="font-bold text-xs uppercase tracking-wider text-slate-400">Product Details</h3>
                <p class="text-sm text-slate-655 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description available for this product.')); ?>
                </p>
            </div>

            <!-- REVIEWS SECTION -->
            <div class="glass-panel p-6 rounded-3xl border border-slate-200 bg-white shadow-sm space-y-6">
                
                <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fas fa-comments text-emerald-600"></i> Customer Reviews
                </h3>

                <!-- Alert Messages -->
                <?php if (!empty($review_success)): ?>
                    <div class="p-4 bg-emerald-50 border border-emerald-250 text-emerald-700 rounded-xl text-xs flex items-center gap-2.5">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($review_success); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($review_error)): ?>
                    <div class="p-4 bg-rose-50 border border-rose-250 text-rose-700 rounded-xl text-xs flex items-center gap-2.5">
                        <i class="fas fa-times-circle"></i>
                        <span><?php echo htmlspecialchars($review_error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Reviews list container -->
                <div class="space-y-4 max-h-[300px] overflow-y-auto pr-1">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-8 text-slate-450 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                            <p class="text-xs">No reviews yet. Be the first to share your experience!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <div class="bg-slate-50 border border-slate-200/60 p-4 rounded-2xl text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-800"><?php echo htmlspecialchars($rev['reviewer_name']); ?></span>
                                        <div class="flex gap-0.5 text-amber-400">
                                            <?php
                                            for ($k = 1; $k <= 5; $k++) {
                                                if ($k <= $rev['rating']) {
                                                    echo '<i class="fas fa-star text-[10px]"></i>';
                                                } else {
                                                    echo '<i class="far fa-star text-slate-300 text-[10px]"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-450"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                </div>
                                <p class="text-slate-655 font-medium leading-relaxed"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <hr class="border-slate-100">

                <!-- Submit Review Form -->
                <div class="bg-slate-50 border border-slate-200/60 rounded-2xl p-5">
                    <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i class="fas fa-pen-nib text-xs text-emerald-600"></i> Share Your Review
                    </h4>
                    
                    <form action="<?php echo BASE_URL; ?>product.php?id=<?php echo $product_id; ?>" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="submit_review">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-slate-700">
                            <div>
                                <label for="reviewer_name" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Your Name</label>
                                <input type="text" id="reviewer_name" name="reviewer_name" required placeholder="Enter your name" 
                                       class="w-full px-4 py-2.5 text-xs bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 placeholder-slate-400 font-semibold text-slate-800">
                            </div>
                            
                            <div>
                                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Your Rating</span>
                                <div class="flex items-center gap-2.5 text-slate-300 text-2xl h-[38px]" id="stars-selector-widget">
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-val="1"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-val="2"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-val="3"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-val="4"></i>
                                    <i class="fas fa-star cursor-pointer hover:text-amber-400 transition-colors" data-val="5"></i>
                                </div>
                                <input type="hidden" id="form-review-rating" name="rating" value="0">
                            </div>
                        </div>

                        <div>
                            <label for="review_comment" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Your Comment</label>
                            <textarea id="review_comment" name="comment" required rows="3" placeholder="Tell others what you think about this product..." 
                                      class="w-full px-4 py-2.5 text-xs bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 placeholder-slate-400 font-medium text-slate-800 resize-none"></textarea>
                        </div>

                        <button type="submit" 
                                class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/10 active:scale-98 cursor-pointer">
                            <i class="fas fa-paper-plane text-[10px]"></i> Submit Review
                        </button>
                    </form>
                </div>

            </div>

        </div>

    </div>

</div>

<!-- Dedicated page interactions JS -->
<script>
// Quantity adjuster
function adjustPageQty(delta) {
    const qtyInput = document.getElementById('product-page-qty');
    if (!qtyInput) return;
    
    let val = parseInt(qtyInput.value) || 1;
    val += delta;
    
    const min = parseInt(qtyInput.getAttribute('min')) || 1;
    const max = parseInt(qtyInput.getAttribute('max')) || 999;
    
    if (val < min) val = min;
    if (val > max) val = max;
    
    qtyInput.value = val;
}

// Add to Cart action
function addQtyToCart(productId) {
    const qtyInput = document.getElementById('product-page-qty');
    const qty = qtyInput ? parseInt(qtyInput.value) : 1;
    
    fetch(BASE_URL + `api/cart.php?action=add&product_id=${productId}&quantity=${qty}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof showToast === 'function') {
                    showToast(data.message, 'success');
                } else {
                    alert(data.message);
                }
                if (typeof updateCartBadge === 'function') {
                    updateCartBadge(data.cart_count);
                }
                if (typeof refreshCartDrawer === 'function') {
                    refreshCartDrawer();
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Failed to add item.', 'error');
                } else {
                    alert(data.message || 'Failed to add item.');
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof showToast === 'function') {
                showToast('Network error, please try again.', 'error');
            } else {
                alert('Network error.');
            }
        });
}

// Buy Now action
function buyQtyNow(productId) {
    const qtyInput = document.getElementById('product-page-qty');
    const qty = qtyInput ? parseInt(qtyInput.value) : 1;
    
    fetch(BASE_URL + `api/cart.php?action=add&product_id=${productId}&quantity=${qty}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = BASE_URL + 'checkout.php';
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Failed to add item.', 'error');
                } else {
                    alert(data.message || 'Failed to add item.');
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof showToast === 'function') {
                showToast('Network error, please try again.', 'error');
            } else {
                alert('Network error.');
            }
        });
}

// Rating stars hover/click behaviour
document.addEventListener('DOMContentLoaded', () => {
    const starsContainer = document.getElementById('stars-selector-widget');
    if (!starsContainer) return;
    
    const stars = starsContainer.querySelectorAll('i');
    const ratingInput = document.getElementById('form-review-rating');
    
    const setRatingHighlight = (val) => {
        stars.forEach(star => {
            const starVal = parseInt(star.getAttribute('data-val')) || 0;
            if (starVal <= val) {
                star.classList.remove('text-slate-300');
                star.classList.add('text-amber-400');
            } else {
                star.classList.add('text-slate-300');
                star.classList.remove('text-amber-400');
            }
        });
    };

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => {
            const val = parseInt(star.getAttribute('data-val')) || 0;
            setRatingHighlight(val);
        });
        
        star.addEventListener('click', () => {
            const val = parseInt(star.getAttribute('data-val')) || 0;
            ratingInput.value = val;
            setRatingHighlight(val);
        });
    });

    starsContainer.addEventListener('mouseleave', () => {
        const val = parseInt(ratingInput.value) || 0;
        setRatingHighlight(val);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
