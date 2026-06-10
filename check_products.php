<?php
// HR Traders - Category & Product Diagnostics Utility
require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>HR Traders Category & Product Diagnostics</h2>";

// 1. Check $CATEGORIES array configuration
echo "<h3>1. Categories Configured in config/db.php:</h3>";
echo "<pre>";
print_r($CATEGORIES);
echo "</pre>";

// 2. Count total products in database
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total = $stmt->fetchColumn();
    echo "<h3>2. Total Products in Database Table: <strong>$total</strong></h3>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error counting products: " . $e->getMessage() . "</p>";
}

// 3. Count products grouped by category in the database
echo "<h3>3. Product Counts Grouped by Category in Database:</h3>";
try {
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category");
    $groups = $stmt->fetchAll();
    if (empty($groups)) {
        echo "<p style='color:orange;'>No products found in the database table.</p>";
    } else {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f0f0f0;'><th>Category Value in DB</th><th>Product Count</th></tr>";
        foreach ($groups as $row) {
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($row['category']) . "</code></td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error listing categories from DB: " . $e->getMessage() . "</p>";
}

// 4. Sample list of first 5 products
echo "<h3>4. Sample of First 5 Products in Database:</h3>";
try {
    $stmt = $pdo->query("SELECT id, barcode, name, category, price, stock_quantity FROM products LIMIT 5");
    $samples = $stmt->fetchAll();
    if (empty($samples)) {
        echo "<p>No products found.</p>";
    } else {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Barcode</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th></tr>";
        foreach ($samples as $p) {
            echo "<tr>";
            echo "<td>" . $p['id'] . "</td>";
            echo "<td>" . htmlspecialchars($p['barcode']) . "</td>";
            echo "<td>" . htmlspecialchars($p['name']) . "</td>";
            echo "<td><code>" . htmlspecialchars($p['category']) . "</code></td>";
            echo "<td>" . $p['price'] . "</td>";
            echo "<td>" . $p['stock_quantity'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error fetching product samples: " . $e->getMessage() . "</p>";
}
?>
