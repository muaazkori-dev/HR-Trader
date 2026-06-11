<?php
// HR Traders - Clean Seeded Dummy Products Utility
// This script deletes seeded products that have " (Item XX)" in their name,
// deletes their associated images from the server, and keeps customer-entered/edited products intact.
// For security, this script will automatically delete itself after execution.

require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Traders - Dummy Products Purge Utility</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding: 40px 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        h2, h3, h4 {
            color: #0f172a;
        }
        .container {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            border: 1px solid #e2e8f0;
        }
        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-info {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #075985;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #475569;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 9999px;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-warning {
            background-color: #fef9c3;
            color: #854d0e;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>HR Traders - Dummy Products Purge Utility</h2>
    
    <?php
    try {
        // 1. Identify the products to delete (containing "(Item " in their name)
        $stmt = $pdo->prepare("SELECT id, name, image FROM products WHERE name LIKE :pattern");
        $stmt->execute(['pattern' => '%(Item %']);
        $dummy_products = $stmt->fetchAll();

        if (empty($dummy_products)) {
            echo '<div class="alert-success">No dummy products found in the database matching the pattern \'%(Item %\'.</div>';
        } else {
            echo '<div class="alert-info">Found <strong>' . count($dummy_products) . '</strong> dummy products. Starting cleanup...</div>';
            
            echo "<h3>List of Purged Products</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Product Name</th><th>Image Path</th><th>Image File Status</th></tr>";
            
            $images_deleted = 0;
            $images_failed = 0;
            $product_ids_to_delete = [];

            foreach ($dummy_products as $p) {
                $product_ids_to_delete[] = $p['id'];
                $image_path = $p['image'];
                $file_status = "No Image Associated";

                if (!empty($image_path)) {
                    $absolute_image_path = __DIR__ . '/' . $image_path;
                    if (file_exists($absolute_image_path) && is_file($absolute_image_path)) {
                        if (unlink($absolute_image_path)) {
                            $file_status = '<span class="badge badge-success">Deleted from Disk</span>';
                            $images_deleted++;
                        } else {
                            $file_status = '<span class="badge badge-danger">Failed to Delete</span>';
                            $images_failed++;
                        }
                    } else {
                        $file_status = '<span class="badge badge-warning">Not Found on Disk</span>';
                    }
                }

                echo "<tr>";
                echo "<td>" . $p['id'] . "</td>";
                echo "<td>" . htmlspecialchars($p['name']) . "</td>";
                echo "<td>" . htmlspecialchars($image_path ? $image_path : '-') . "</td>";
                echo "<td>" . $file_status . "</td>";
                echo "</tr>";
            }
            echo "</table>";

            // 2. Perform DB deletion in a transaction
            $pdo->beginTransaction();
            $placeholders = implode(',', array_fill(0, count($product_ids_to_delete), '?'));
            $delete_stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)");
            $delete_stmt->execute($product_ids_to_delete);
            $pdo->commit();

            echo '<div class="alert-success">';
            echo '<strong>Purge Successful!</strong><br>';
            echo 'Deleted ' . count($product_ids_to_delete) . ' dummy products from the database.<br>';
            echo 'Cleaned up ' . $images_deleted . ' image files from server storage' . ($images_failed > 0 ? ' (Failed to delete ' . $images_failed . ' files)' : '') . '.';
            echo '</div>';
        }

        // 3. Show remaining products count and sample
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $remaining_count = $stmt->fetchColumn();
        echo "<h3>Total Remaining Products in Database: <span style='color:#2563eb;'>" . $remaining_count . "</span></h3>";

        if ($remaining_count > 0) {
            $stmt = $pdo->query("SELECT id, name, category, price, stock_quantity, image FROM products ORDER BY id ASC");
            $remaining_products = $stmt->fetchAll();
            echo "<h4>List of Remaining Products (Real Customer / Custom-Edited Products):</h4>";
            echo "<table>";
            echo "<tr style='background-color:#e0f2fe;'><th>ID</th><th>Product Name</th><th>Category</th><th>Price (Rs.)</th><th>Stock Qty</th><th>Image Path</th></tr>";
            foreach ($remaining_products as $p) {
                echo "<tr>";
                echo "<td>" . $p['id'] . "</td>";
                echo "<td><strong>" . htmlspecialchars($p['name']) . "</strong></td>";
                echo "<td><code>" . htmlspecialchars($p['category']) . "</code></td>";
                echo "<td>" . number_format($p['price'], 2) . "</td>";
                echo "<td>" . $p['stock_quantity'] . "</td>";
                echo "<td>" . htmlspecialchars($p['image'] ? $p['image'] : '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo '<div class="alert-danger" style="background-color:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px;">';
        echo '<strong>Error occurred during cleanup:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }

    // Self-delete file for security
    @unlink(__FILE__);
    echo '<p style="color:#64748b; font-size:12px; font-style:italic; margin-top:30px; border-top:1px solid #e2e8f0; padding-top:10px;">';
    echo 'Security Lock: This cleanup utility script (delete_dummy_products.php) has automatically deleted itself from the server files after running to prevent unauthorized access.';
    echo '</p>';
    ?>

</div>

</body>
</html>
