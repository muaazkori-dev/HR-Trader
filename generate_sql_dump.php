<?php
// Script to generate PostgreSQL insert statements from local MySQL products table
header('Content-Type: text/plain; charset=utf-8');

$conn = new mysqli('127.0.0.1', 'root', '', 'hr_traders');
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error . "\nMake sure XAMPP MySQL is running.");
}

$result = $conn->query("SELECT * FROM products ORDER BY id ASC");
if (!$result) {
    die("Error fetching products: " . $conn->error);
}

$count = $result->num_rows;
echo "-- Found $count products in local database. Copy and run this SQL in Supabase SQL editor:\n\n";

// Clear existing dummy products on Supabase first to prevent conflicts
echo "TRUNCATE TABLE public.products RESTART IDENTITY CASCADE;\n\n";

echo "INSERT INTO public.products (id, barcode, name, description, price, purchase_price, stock_quantity, weight, unit, category, image) VALUES\n";

$rows = [];
while ($row = $result->fetch_assoc()) {
    $id = (int)$row['id'];
    $barcode = pg_escape_string_val($row['barcode']);
    $name = pg_escape_string_val($row['name']);
    $description = pg_escape_string_val($row['description']);
    $price = (float)$row['price'];
    $purchase_price = (float)$row['purchase_price'];
    $stock_quantity = (int)$row['stock_quantity'];
    $weight = pg_escape_string_val($row['weight']);
    $unit = pg_escape_string_val($row['unit']);
    $category = pg_escape_string_val($row['category']);
    
    // Convert local asset path to relative image path
    $image = pg_escape_string_val($row['image']);

    $rows[] = "($id, '$barcode', '$name', " . ($description ? "'$description'" : "NULL") . ", $price, $purchase_price, $stock_quantity, " . ($weight ? "'$weight'" : "NULL") . ", '$unit', '$category', " . ($image ? "'$image'" : "NULL") . ")";
}

echo implode(",\n", $rows);
echo ";\n\n-- Finished seeding $count products.\n";

function pg_escape_string_val($val) {
    if ($val === null) return '';
    // Replace single quotes with two single quotes for SQL escaping
    return str_replace("'", "''", $val);
}
?>
