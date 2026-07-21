<?php
// Script to restore all missing product images to Supabase Storage and sync products
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/products.php';

echo "Supabase Restoration & Product Sync Script\n";
echo "=========================================\n";

// 1. Get Supabase credentials
$supabase_url = '';
$supabase_key = '';
$env_path = __DIR__ . '/next-store/.env.local';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " '\"");
            if ($key === 'NEXT_PUBLIC_SUPABASE_URL') {
                $supabase_url = $val;
            } elseif ($key === 'NEXT_PUBLIC_SUPABASE_ANON_KEY') {
                $supabase_key = $val;
            }
        }
    }
}

if (empty($supabase_url) || empty($supabase_key)) {
    die("Error: Supabase credentials not found in next-store/.env.local\n");
}

echo "Supabase URL: $supabase_url\n";

// Helper function to upload to Supabase Storage
function local_upload_to_supabase_storage($local_file_path, $filename, $supabase_url, $supabase_key) {
    if (!file_exists($local_file_path)) {
        return false;
    }
    
    $url = rtrim($supabase_url, '/') . '/storage/v1/object/product-images/products/' . $filename;
    
    $mime = 'image/png';
    $ext = strtolower(pathinfo($local_file_path, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $mime = 'image/jpeg';
    } elseif ($ext === 'webp') {
        $mime = 'image/webp';
    } elseif ($ext === 'svg') {
        $mime = 'image/svg+xml';
    }
    
    $file_data = file_get_contents($local_file_path);
    if (!$file_data) return false;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Content-Type: ' . $mime,
        'x-upsert: true'
    ]);
    
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] === 200 || $info['http_code'] === 201) {
        return rtrim($supabase_url, '/') . '/storage/v1/object/public/product-images/products/' . $filename;
    }
    
    echo "Upload failed for $filename. HTTP Code: " . $info['http_code'] . ", Response: $res\n";
    return false;
}

// Fetch all local products (which contains Meclay, Pantene, etc. if run locally)
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($products) . " products in local database.\n";

$success_count = 0;
foreach ($products as $p) {
    $p_id = $p['id'];
    $name = $p['name'];
    $image_path = $p['image'];
    
    echo "Processing [ID: $p_id] $name...\n";
    
    $final_image_url = null;
    if (!empty($image_path)) {
        if (strpos($image_path, 'http://') === 0 || strpos($image_path, 'https://') === 0) {
            $final_image_url = $image_path;
            echo " - Image is already external: $image_path\n";
        } else {
            // Local path, try uploading
            $local_file = __DIR__ . '/' . ltrim($image_path, '/');
            if (file_exists($local_file)) {
                echo " - Uploading local image: $local_file\n";
                $uploaded_url = local_upload_to_supabase_storage($local_file, basename($image_path), $supabase_url, $supabase_key);
                if ($uploaded_url) {
                    $final_image_url = $uploaded_url;
                    echo "   Successfully uploaded to Supabase: $final_image_url\n";
                }
            } else {
                echo "   Warning: Local image file does not exist at $local_file\n";
            }
        }
    }
    
    // Sync metadata to Supabase
    $payload = [
        'id' => intval($p['id']),
        'barcode' => strval($p['barcode']),
        'name' => strval($p['name']),
        'description' => strval($p['description']),
        'price' => floatval($p['price']),
        'purchase_price' => floatval($p['purchase_price']),
        'stock_quantity' => intval($p['stock_quantity']),
        'weight' => $p['weight'] !== null ? strval($p['weight']) : null,
        'unit' => strval($p['unit']),
        'category' => strval($p['category']),
        'image' => $final_image_url ? $final_image_url : ($p['image'] !== null ? strval($p['image']) : null),
        'old_price' => $p['old_price'] !== null ? floatval($p['old_price']) : null,
        'discount_percentage' => $p['discount_percentage'] !== null ? intval($p['discount_percentage']) : null
    ];
    
    $url = $supabase_url . '/rest/v1/products';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Content-Type: application/json',
        'Prefer: resolution=merge-duplicates'
    ]);
    
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] === 201 || $info['http_code'] === 200 || $info['http_code'] === 204) {
        echo "   Synced metadata successfully to Supabase.\n";
        $success_count++;
    } else {
        echo "   Failed to sync metadata. HTTP Code: " . $info['http_code'] . ", Response: $res\n";
    }
}

echo "\nCompleted. Successfully restored and synced $success_count products to Supabase Cloud.\n";
