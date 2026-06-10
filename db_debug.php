<?php
// HR Traders Database Diagnostics & Manual Migrator
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';

echo "<h2>HR Traders Database Diagnostic Tool</h2>";
echo "Connected successfully to database: <strong>" . DB_NAME . "</strong><br><br>";

// Manual category updates trigger
echo "<h3>1. Running Category Migrations manually...</h3>";

try {
    $pdo->exec("ALTER TABLE products MODIFY COLUMN category VARCHAR(50) NOT NULL");
    echo "Column <code>category</code> successfully modified to VARCHAR(50).<br>";
} catch (PDOException $e) {
    echo "Info: Column modification status: " . $e->getMessage() . "<br>";
}

$updates = [
    "UPDATE products SET category = 'anaj' WHERE category IN ('pulses_rice', 'snacks_chips')",
    "UPDATE products SET category = 'beverages' WHERE category IN ('cold_drinks', 'water')",
    "UPDATE products SET category = 'ice_cream' WHERE category = 'frozen_icecream'"
];

foreach ($updates as $sql) {
    try {
        $count = $pdo->exec($sql);
        echo "Query: <code>$sql</code> - Affected Rows: <strong>$count</strong><br>";
    } catch (PDOException $e) {
        echo "<span style='color:red;'>Error running migration: " . $e->getMessage() . "</span><br>";
    }
}

echo "<h3>2. Product Count by Category in Database:</h3>";
try {
    $stmt = $pdo->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category");
    $rows = $stmt->fetchAll();
    if (empty($rows)) {
        echo "<span style='color:orange;'>No products found in the database.</span><br>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f4f4f4;'><th>Category Name (in DB)</th><th>Product Count</th></tr>";
        foreach ($rows as $row) {
            echo "<tr><td><code>" . htmlspecialchars($row['category']) . "</code></td><td>" . $row['cnt'] . "</td></tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<span style='color:red;'>Error fetching product categories: " . $e->getMessage() . "</span><br>";
}

echo "<br><strong>Diagnostics completed.</strong>";
?>
