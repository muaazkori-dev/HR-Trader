<?php
// HR Traders Storefront Product Catalog Page
require_once __DIR__ . '/includes/header.php';

// Get selected category filter and search query
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Verify category is valid
$is_valid_cat = array_key_exists($selected_category, $CATEGORIES);

// Fetch products based on category filter and search query
try {
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    if ($is_valid_cat) {
        if ($selected_category === 'cosmetics') {
            $sql .= " AND category IN ('cosmetics', 'shampoo', 'soap', 'toothpaste', 'body_wash', 'deodorant')";
        } else {
            $sql .= " AND category = :category";
            $params['category'] = $selected_category;
        }
    } else {
        $selected_category = ''; // Reset invalid categories
    }

    if ($search_query !== '') {
        $sql .= " AND (name LIKE :search1 OR category LIKE :search2)";
        $clean_search = preg_replace('/\s+/', '%', $search_query);
        $params['search1'] = "%" . $clean_search . "%";
        $params['search2'] = "%" . $clean_search . "%";
    }

    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<div id="shop-container" class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    
    <!-- Title & Category Selector Tabs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">
                <?php 
                if ($search_query !== '') {
                    echo 'Search Results for "' . sanitize($search_query) . '" <span class="text-slate-500 font-normal">(' . count($products) . ')</span>';
                } elseif ($selected_category && $is_valid_cat) {
                    echo $CATEGORIES[$selected_category]['name'] . ' <span class="text-slate-500 font-normal">(' . count($products) . ')</span>';
                } else {
                    echo 'All Store Products <span class="text-slate-550 font-normal">(' . count($products) . ')</span>';
                }
                ?>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Explore our range of fresh quality daily groceries</p>
        </div>

        <!-- Filter tabs list -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?php echo BASE_URL; ?>shop.php" class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all <?php echo empty($selected_category) ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'; ?>">
                All items
            </a>
            <?php foreach ($CATEGORIES as $key => $cat): ?>
                <?php if (isset($cat['parent'])) continue; ?>
                <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo $key; ?>" class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all <?php echo $selected_category === $key ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'; ?>">
                    <?php echo $cat['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- VISUAL WARNING ALERT IF FROZEN CATEGORY IS SELECTED -->
    <?php if ($selected_category === 'ice_cream'): ?>
        <div class="frozen-alert-border bg-rose-50 border border-rose-250 rounded-2xl p-6 mb-8 flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left transition-all">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="space-y-1">
                <h3 class="font-bold text-rose-700 text-base leading-tight">Available For Nearby Locations Only</h3>
                <p class="text-xs text-rose-600">
                    To maintain standard food safety and cold-chain temperature, frozen products are delivered to surrounding shop zones only.
                </p>
                <p class="urdu-text text-sm text-rose-700 font-bold mt-1 tracking-wide">
                    یہ فروزن پروڈکٹس صرف قریبی علاقوں میں ہوم ڈیلیوری کے لئے دستیاب ہیں۔
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- PRODUCTS GRID -->
    <?php if (empty($products)): ?>
        <div class="glass-panel rounded-2xl py-16 text-center text-slate-655 border border-slate-200 px-4">
            <i class="fas fa-shopping-basket text-5xl mb-4 opacity-15"></i>
            <h3 class="font-bold text-slate-800 text-lg">No products found</h3>
            <p class="text-xs mt-1">There are no products registered or matching your criteria in the store catalog.</p>
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?php echo BASE_URL; ?>shop.php" class="text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-755 px-4 py-2 rounded-xl border border-slate-300 transition-all">
                    View All Stock
                </a>
                <button onclick="openDemandModal()" class="text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 px-4 py-2 rounded-xl border border-amber-250 transition-all flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-clipboard-list text-sm"></i> Apni Demand Likhein (Request Item)
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-6">
            <?php foreach ($products as $product): ?>
                <?php 
                $is_frozen = $product['category'] === 'ice_cream'; 
                $is_out_of_stock = $product['stock_quantity'] <= 0;
                ?>
                <!-- PRODUCT CARD -->
                <div class="glass-card rounded-2xl overflow-hidden flex flex-col border <?php echo $is_frozen ? 'frozen-alert-border border-rose-500/20' : 'border-slate-200'; ?> hover:shadow-md transition-shadow">
                    <!-- Image Area -->
                    <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="block h-32 sm:h-48 bg-slate-50 flex items-center justify-center relative border-b border-slate-250/60 overflow-hidden cursor-pointer group">
                        <?php 
                        $img_src = get_product_image_url($product['image'] ?? '');
                        ?>
                        <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($product['name']); ?>" class="w-full h-full object-cover transition-transform group-hover:scale-105 duration-300" loading="lazy">

                        <!-- Floating Labels (Smaller on mobile) -->
                        <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded-md text-[8px] sm:text-[10px] uppercase font-bold bg-white/90 backdrop-blur-sm border border-slate-200 text-slate-600 shadow-sm">
                            <?php echo $CATEGORIES[$product['category']]['name'] ?? $product['category']; ?>
                        </span>

                        <?php if ($product['old_price'] > $product['price'] || $product['discount_percentage'] > 0): ?>
                            <?php 
                            $pct = $product['discount_percentage'] ?: (int)round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); 
                            ?>
                            <span class="absolute top-7 left-2 px-1.5 py-0.5 rounded-md text-[8px] sm:text-[9px] uppercase font-bold bg-rose-600 text-white shadow-md">
                                <?php echo $pct; ?>% OFF
                            </span>
                        <?php endif; ?>

                        <!-- Stock Status -->
                        <?php if ($is_out_of_stock): ?>
                            <span class="absolute top-2 right-2 px-1.5 py-0.5 rounded-md text-[8px] sm:text-[9px] uppercase font-bold bg-rose-600 text-white shadow-sm">
                                Out
                            </span>
                        <?php else: ?>
                            <span class="absolute top-2 right-2 px-1.5 py-0.5 rounded-md text-[8px] sm:text-[9px] uppercase font-bold bg-emerald-50/95 backdrop-blur-sm border border-emerald-250 text-emerald-700 shadow-sm">
                                In Stock
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Content Area -->
                    <div class="p-2.5 sm:p-5 flex-1 flex flex-col justify-between gap-2.5 text-left">
                        <div>
                            <h3 class="font-bold text-slate-800 text-xs sm:text-base leading-tight line-clamp-2 min-h-[32px] sm:min-h-[40px]">
                                <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="hover:text-emerald-600 transition-colors">
                                    <?php echo sanitize($product['name']); ?>
                                </a>
                            </h3>
                            
                            <!-- Hide description & barcode on mobile to save space -->
                            <p class="hidden sm:block text-xs text-slate-500 line-clamp-2 mt-1 min-h-[32px]"><?php echo sanitize($product['description'] ?: 'No description provided.'); ?></p>
                            
                            <div class="flex items-center justify-between text-[10px] sm:text-xs text-slate-500 mt-1">
                                <span><?php echo sanitize($product['weight']); ?></span>
                                <span class="hidden sm:inline font-mono text-[9px] text-slate-400">Barcode: <?php echo sanitize($product['barcode']); ?></span>
                            </div>
                        </div>

                        <!-- Frozen Warn alert if category matches (Compact on mobile) -->
                        <?php if ($is_frozen): ?>
                            <div class="bg-rose-50/50 border border-rose-105 rounded-xl p-1.5 sm:p-3 text-center">
                                <span class="text-[9px] sm:text-xs text-rose-600 font-bold block">
                                    <i class="fas fa-snowflake mr-0.5"></i> Nearby Only
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Pricing & Action Row -->
                        <div class="space-y-2 pt-1 border-t border-slate-100/80">
                            <!-- Price Display -->
                            <div class="flex items-baseline justify-between sm:justify-start gap-1">
                                <span class="text-sm sm:text-lg font-black text-emerald-600"><?php echo format_price($product['price']); ?></span>
                                <?php if ($product['old_price'] > $product['price'] || $product['discount_percentage'] > 0): ?>
                                    <?php 
                                    $old_pr = $product['old_price'] ?: ($product['price'] / (1 - ($product['discount_percentage'] / 100))); 
                                    ?>
                                    <span class="text-[10px] sm:text-xs font-bold text-slate-400 line-through"><?php echo format_price($old_pr); ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Buttons -->
                            <?php if ($is_out_of_stock): ?>
                                <button disabled class="w-full py-2 bg-slate-100 text-slate-400 rounded-xl font-bold text-[10px] sm:text-xs cursor-not-allowed text-center">
                                    Out of Stock
                                </button>
                            <?php else: ?>
                                <div class="flex items-center gap-1.5 w-full">
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                            class="flex-1 justify-center py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 active:scale-95 text-slate-700 rounded-xl font-bold text-[10px] sm:text-xs transition-all flex items-center gap-1 cursor-pointer">
                                        <i class="fas fa-plus"></i> <span class="hidden sm:inline">Add</span><span class="sm:hidden">Cart</span>
                                    </button>
                                    <button onclick="buyNow(<?php echo $product['id']; ?>)" 
                                            class="hidden sm:flex flex-1 justify-center py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl font-black text-xs transition-all shadow-md shadow-emerald-600/10 items-center gap-1 cursor-pointer">
                                        Buy Now
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
