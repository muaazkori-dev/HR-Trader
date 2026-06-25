<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config/db.php';

echo "=== DIAGNOSTIC: PRODUCT IMAGES STATUS ===\n\n";

try {
    // 1. Check directories
    $target_dir = __DIR__ . '/assets/images/products';
    $backup_dir = __DIR__ . '/../product_uploads';
    
    echo "Directories check:\n";
    echo "Target dir exists: " . (is_dir($target_dir) ? "Yes" : "No") . " (Writable: " . (is_writable($target_dir) ? "Yes" : "No") . ")\n";
    echo "Backup dir exists: " . (@is_dir($backup_dir) ? "Yes" : "No") . " (Writable: " . (@is_writable($backup_dir) ? "Yes" : "No") . ")\n";
    
    // 2. Fetch products
    $stmt = $pdo->query("SELECT id, name, image, price FROM products ORDER BY id DESC LIMIT 50");
    $products = $stmt->fetchAll();
    
    echo "\nTotal products fetched (last 50): " . count($products) . "\n\n";
    echo str_pad("ID", 6) . str_pad("Name", 35) . str_pad("Image Column", 40) . str_pad("Target File", 15) . str_pad("Backup File", 15) . "\n";
    echo str_repeat("-", 115) . "\n";
    
    foreach ($products as $p) {
        $img_val = $p['image'];
        $name_short = strlen($p['name']) > 32 ? substr($p['name'], 0, 30) . '..' : $p['name'];
        
        $target_exists = "N/A";
        $backup_exists = "N/A";
        
        if (!empty($img_val)) {
            $filename = basename($img_val);
            $target_file = __DIR__ . '/' . $img_val;
            $backup_file = $backup_dir . '/' . $filename;
            
            $target_exists = file_exists($target_file) ? "Found" : "MISSING";
            $backup_exists = @file_exists($backup_file) ? "Found" : "MISSING";
        }
        
        echo str_pad($p['id'], 6) . 
             str_pad($name_short, 35) . 
             str_pad(!empty($img_val) ? $img_val : '[EMPTY]', 40) . 
             str_pad($target_exists, 15) . 
             str_pad($backup_exists, 15) . "\n";
    }
    
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
