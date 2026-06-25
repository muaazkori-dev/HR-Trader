<?php
// HR Traders - 50 Products Seeder with Custom Uploaded Images & Database Schema Self-Healing
// Designed to be run in the browser to populate the product registry instantly.

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$message = "";
$status = "";

if (isset($_POST['seed_products'])) {
    try {
        $pdo->beginTransaction();

        // 1. Alter products.category to VARCHAR(50) to support new categories (fixes ENUM empty string conversion on Hostinger)
        $pdo->exec("ALTER TABLE products MODIFY COLUMN category VARCHAR(50) NOT NULL");
        
        // 2. Clean up previously seeded empty-category dummy products
        $pdo->exec("DELETE FROM products WHERE name LIKE '%(Item %'");
        
        // 3. Fix existing 7UP category to correct 'beverages' category
        $pdo->exec("UPDATE products SET category = 'beverages' WHERE id = 1 OR name LIKE '%7UP%'");

        // Sample product blueprints across categories to match the styles of your images
        $blueprints = [
            ['category' => 'anaj', 'unit' => 'kg', 'weight' => '1 kg', 'names' => ['Super Basmati Rice', 'Chana Daal Premium', 'Moong Daal Special', 'Maash Daal Whole', 'Masoor Daal Red', 'Wheat Flour (Atta)', 'Fine Flour (Maida)', 'Semolina (Suji)', 'White Chickpeas (Kala Chana)', 'Red Beans (Lobia)']],
            ['category' => 'beverages', 'unit' => 'pcs', 'weight' => '1.5 Ltr', 'names' => ['Cola Soda Classic', 'Lemon Orange Fizzy', 'Dew Mountain Drink', 'Sprite Lemon Lime', 'Fanta Citrus Blast', 'Mango Fruit Juice', 'Apple Nectar Drink', 'Peach Iced Tea', 'Club Soda Water', 'Energy Boost Drink', 'Pure Mineral Water', 'Spring Drinking Water', 'Purified Alkaline Water', 'Sparkling Club Water', 'Electrolyte Water Bottle', 'Smart Hydration Water', 'Zero Sodium Water', 'Natural Mineral Water', 'Eco Bottle Water', 'Premium Glacial Water']],
            ['category' => 'ice_cream', 'unit' => 'pcs', 'weight' => '500 ml', 'names' => ['Vanilla Fudge Ice Cream', 'Chocolate Belgian Tub', 'Mango King Delight', 'Strawberry Swirl Scoop', 'Kulfa Traditional Cup', 'Pista Almond Cone', 'Caramel Crunch Bar', 'Blueberry Frozen Yogurt', 'Tutti Frutti Tub', 'Coconut Cream Ice Cream']],
            ['category' => 'milk', 'unit' => 'pcs', 'weight' => '1 Ltr', 'names' => ['UHT Whole Milk', 'Low Fat Slim Milk', 'Organic Soy Milk', 'Almond Nut Milk', 'Fresh Dairy Milk', 'Condensed Sweet Milk', 'Evaporated Milk Can', 'Chocolate Milk Shake', 'Strawberry Dairy Drink', 'Powdered Milk Premium']],
            ['category' => 'cosmetics', 'unit' => 'pcs', 'weight' => '200 ml', 'names' => ['Anti-Dandruff Shampoo', 'Moisturizing Hair Conditioner', 'Aloe Vera Body Wash', 'Charcoal Face Scrub', 'Sandalwood Beauty Soap', 'Herbal Toothpaste', 'Deodorant Body Spray', 'Hydrating Face Cream', 'Rose Water Spray', 'Sunscreen Lotion SPF 50']],
            ['category' => 'snacks', 'unit' => 'pcs', 'weight' => '100 g', 'names' => ['Potato Lays Chips', 'Kurkure Red Chilli', 'Cheetos Cheese Puffs', 'Wavy Masala Crisps', 'Premium Chocolate Cookies', 'Butter Baker Biscuits', 'Salted Popcorn Bag', 'Nimko Mix Special', 'Peanut Butter Cookies', 'Spicy Potato Sticks']]
        ];

        // Ensure target directory for products exists
        $dest_dir = __DIR__ . '/assets/images/products/';
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0777, true);
        }

        // Scan the custom Products directory
        $source_dir = __DIR__ . '/Products';
        $image_files = [];
        if (is_dir($source_dir)) {
            $files = scandir($source_dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                // Skip duplicate copies with (1) in their names to keep it clean
                if (strpos($file, '(1)') !== false) continue;
                
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $image_files[] = $file;
                }
            }
        }

        $total_inserted = 0;
        
        // Loop to generate 50 products
        for ($i = 1; $i <= 50; $i++) {
            // Select a random blueprint category
            $blueprint = $blueprints[array_rand($blueprints)];
            $category = $blueprint['category'];
            $unit = $blueprint['unit'];
            $weight = $blueprint['weight'];
            
            // Pick a random name from the list and append index to guarantee uniqueness
            $base_name = $blueprint['names'][array_rand($blueprint['names'])];
            $product_name = $base_name . " (Item " . str_pad($i, 2, '0', STR_PAD_LEFT) . ")";
            
            // Auto generate unique 8-digit barcode
            do {
                $barcode = (string)mt_rand(10000000, 99999999);
                $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = :barcode");
                $stmt->execute(['barcode' => $barcode]);
            } while ($stmt->fetch());

            // Random price between 80 and 850
            $price = (float)mt_rand(80, 850);
            // Purchase price is calculated with a healthy profit margin
            $purchase_price = round($price * (mt_rand(70, 80) / 100), 2);
            $stock_quantity = 99; // Set quantity to 99 as requested
            $description = "Temporary placeholder description for " . $product_name . ". Easily editable from the admin products registry desk.";

            // Handle copying of custom uploaded images from Products/ to assets/images/products/
            $image_path = null;
            if (!empty($image_files)) {
                $selected_file = $image_files[($i - 1) % count($image_files)];
                $src_path = $source_dir . '/' . $selected_file;
                
                if (file_exists($src_path)) {
                    $ext = strtolower(pathinfo($selected_file, PATHINFO_EXTENSION));
                    $new_file_name = 'prod_' . uniqid() . '.' . $ext;
                    $dest_path = $dest_dir . $new_file_name;
                    
                    if (copy($src_path, $dest_path)) {
                        $image_path = 'assets/images/products/' . $new_file_name;
                        // Save backup copy outside public_html
                        $backup_dir = __DIR__ . '/../product_uploads/';
                        if (!is_dir($backup_dir)) {
                            @mkdir($backup_dir, 0777, true);
                        }
                        @copy($dest_path, $backup_dir . $new_file_name);
                    }
                }
            }

            // Insert into products table
            $stmt = $pdo->prepare("INSERT INTO products (barcode, name, description, price, purchase_price, stock_quantity, weight, unit, category, image) 
                                   VALUES (:barcode, :name, :description, :price, :purchase_price, :stock, :weight, :unit, :category, :image)");
            $stmt->execute([
                'barcode' => $barcode,
                'name' => $product_name,
                'description' => $description,
                'price' => $price,
                'purchase_price' => $purchase_price,
                'stock' => $stock_quantity,
                'weight' => $weight,
                'unit' => $unit,
                'category' => $category,
                'image' => $image_path
            ]);

            $total_inserted++;
        }

        $pdo->commit();
        $status = "success";
        $message = "Successfully modified database categories column, cleaned old items, and seeded <strong>" . $total_inserted . "</strong> products with custom uploaded pictures and 99 stock quantity!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $status = "error";
        $message = "Failed to seed products: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Traders - Seeder Panel</title>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10b981',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/all.min.css">
    <style>
        body {
            background: radial-gradient(circle at top, #0f172a 0%, #020617 100%);
        }
    </style>
</head>
<body class="text-slate-100 min-h-screen flex items-center justify-center p-6">

<div class="max-w-md w-full bg-slate-900/60 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6">
    
    <div class="text-center">
        <h1 class="text-2xl font-black tracking-wider text-emerald-500 uppercase">HR Traders</h1>
        <p class="text-xs text-slate-400 mt-1">Automatic Custom Image Store Seeder</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="p-4 rounded-2xl border text-sm <?php echo $status === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-450'; ?>">
            <i class="fas <?php echo $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mr-2"></i>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-slate-950/40 p-4 border border-slate-800 rounded-2xl text-xs text-slate-400 space-y-2 leading-relaxed">
        <p><strong>Note:</strong> Running this utility will insert:</p>
        <ul class="list-disc pl-4 space-y-1">
            <li>Convert database `category` column to VARCHAR to support new categories.</li>
            <li>Clean up previously seeded dummy products with empty categories.</li>
            <li>Fix category of existing 7UP to 'beverages'.</li>
            <li>Seed 50 new products distributed across standard categories (Qty: 99).</li>
        </ul>
    </div>

    <!-- SEED BUTTON ACTION -->
    <div class="space-y-3">
        <form action="" method="POST">
            <button type="submit" name="seed_products" 
                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-bold text-sm rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/20">
                <i class="fas fa-boxes-packing"></i> Seed 50 Products (with Custom Images)
            </button>
        </form>
        <a href="<?php echo BASE_URL; ?>admin/products.php" 
           class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 active:scale-98 text-slate-300 font-bold text-xs rounded-xl uppercase tracking-wider text-center transition-all flex items-center justify-center">
            Go to Admin Products Panel
        </a>
    </div>

</div>

</body>
</html>
