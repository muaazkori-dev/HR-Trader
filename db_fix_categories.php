<?php
// HR Traders - Database Category Auto-Healer & Migrator
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== HR TRADERS DATABASE AUTO-HEALER ===\n\n";

try {
    // 1. Ensure category column is VARCHAR(50) so it doesn't reject new categories
    echo "1. Altering products category column to VARCHAR(50)...\n";
    $pdo->exec("ALTER TABLE products MODIFY COLUMN category VARCHAR(50) NOT NULL");
    echo "[SUCCESS] Category column altered or verified as VARCHAR(50).\n\n";
} catch (PDOException $e) {
    echo "[INFO] Column alteration status/warning: " . $e->getMessage() . "\n\n";
}

// 2. Run standard migrations
echo "2. Running standard category mapping updates...\n";
$standard_updates = [
    "UPDATE products SET category = 'anaj' WHERE category IN ('pulses_rice', 'snacks_chips')",
    "UPDATE products SET category = 'beverages' WHERE category IN ('cold_drinks', 'water')",
    "UPDATE products SET category = 'ice_cream' WHERE category = 'frozen_icecream'",
    "UPDATE products SET category = 'milk' WHERE category = 'milk_dairy'"
];

foreach ($standard_updates as $sql) {
    try {
        $count = $pdo->exec($sql);
        echo "- Executed: $sql (Affected: $count)\n";
    } catch (PDOException $e) {
        echo "- [ERROR] Query failed: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 3. Heal any products with empty or invalid categories
echo "3. Healing empty or invalid categories using product names...\n";
$valid_categories = ['anaj', 'ice_cream', 'beverages', 'milk', 'cosmetics', 'snacks'];

try {
    // Fetch all products
    $stmt = $pdo->query("SELECT id, name, category FROM products");
    $products = $stmt->fetchAll();
    
    $healed_count = 0;
    foreach ($products as $p) {
        $id = $p['id'];
        $name = strtolower($p['name']);
        $cat = trim($p['category']);
        
        // If category is empty or not in the valid list, heal it
        if ($cat === '' || !in_array($cat, $valid_categories)) {
            $new_cat = '';
            
            // Rules to match category based on keywords in name
            if (preg_match('/\b(rice|daal|flour|atta|maida|suji|chana|lobia|chickpeas|beans|pulses|anaj)\b/i', $name)) {
                $new_cat = 'anaj';
            } elseif (preg_match('/\b(cola|fizzy|drink|soda|juice|water|nectar|tea|7up|pepsi|mirinda|sprite|dew|fanta|beverages)\b/i', $name)) {
                $new_cat = 'beverages';
            } elseif (preg_match('/\b(ice\s*cream|yogurt|fudge|belgian|delight|scoop|cup|cone|bar|tub|icecream)\b/i', $name)) {
                $new_cat = 'ice_cream';
            } elseif (preg_match('/\b(milk|shake|dairy)\b/i', $name)) {
                $new_cat = 'milk';
            } elseif (preg_match('/\b(shampoo|conditioner|wash|scrub|soap|toothpaste|spray|cream|lotion|cosmetic|deodorant)\b/i', $name)) {
                $new_cat = 'cosmetics';
            } elseif (preg_match('/\b(chips|cookies|biscuits|snacks|nimko|popcorn|krunch|snack|puff|crisps|cheetos|lays|kurkure)\b/i', $name)) {
                $new_cat = 'snacks';
            }
            
            if ($new_cat !== '') {
                $update_stmt = $pdo->prepare("UPDATE products SET category = :cat WHERE id = :id");
                $update_stmt->execute(['cat' => $new_cat, 'id' => $id]);
                echo "- Healed product ID $id: '" . $p['name'] . "' -> set to category '$new_cat'\n";
                $healed_count++;
            } else {
                // Default fallback if no keywords match
                $default_cat = 'anaj';
                $update_stmt = $pdo->prepare("UPDATE products SET category = :cat WHERE id = :id");
                $update_stmt->execute(['cat' => $default_cat, 'id' => $id]);
                echo "- Fallback product ID $id: '" . $p['name'] . "' -> set to category '$default_cat'\n";
                $healed_count++;
            }
        }
    }
    echo "[SUCCESS] Healed $healed_count products.\n\n";
} catch (Exception $e) {
    echo "[ERROR] Failed healing categories: " . $e->getMessage() . "\n\n";
}

// 4. Print post-healing summary
echo "4. Post-Healing Categories Summary:\n";
try {
    $stmt = $pdo->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category");
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        echo "- '" . $row['category'] . "': " . $row['cnt'] . "\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Summary failed: " . $e->getMessage() . "\n";
}

echo "\n=== PROCESS COMPLETED ===";
?>
