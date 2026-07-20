<?php
// HR Traders Products CRUD Manager Panel
// Restricted strictly to owner admins

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Owner/Manager role check
require_role(['owner', 'manager']);

// Run automatic DB migrations if needed
try {
    $q = $pdo->query("SHOW COLUMNS FROM products LIKE 'image'");
    if (!$q->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL");
    }
    
    $q = $pdo->query("SHOW COLUMNS FROM products LIKE 'unit'");
    if (!$q->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN unit VARCHAR(20) DEFAULT 'pcs'");
    }

    $q = $pdo->query("SHOW COLUMNS FROM products LIKE 'purchase_price'");
    if (!$q->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }
} catch (PDOException $e) {
    // Ignore schema update issues in case they are handled elsewhere
}

$success_message = "";
$error_message = "";

function sync_product_to_supabase($product_id, $action = 'upsert') {
    global $pdo;
    
    // 1. Get Supabase URL and Anon Key from .env.local
    $supabase_url = '';
    $supabase_key = '';
    $env_path = __DIR__ . '/../next-store/.env.local';
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
        return false;
    }
    
    if ($action === 'delete') {
        // Send DELETE request to Supabase
        $url = $supabase_url . '/rest/v1/products?id=eq.' . intval($product_id);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $supabase_key,
            'Authorization: Bearer ' . $supabase_key,
            'Content-Type: application/json'
        ]);
        curl_exec($ch);
        curl_close($ch);
        return true;
    }
    
    // Fetch product details from MySQL
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) return false;
    
    // Prepare Supabase Payload
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
        'image' => $p['image'] !== null ? strval($p['image']) : null,
        'old_price' => $p['old_price'] !== null ? floatval($p['old_price']) : null,
        'discount_percentage' => $p['discount_percentage'] !== null ? intval($p['discount_percentage']) : null
    ];
    
    // Send UPSERT (POST with Prefer: resolution=merge-duplicates) request to Supabase
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
    curl_exec($ch);
    curl_close($ch);
    return true;
}

function compressAndSaveUploadedImage($file_tmp, $dest_path, $ext) {
    if (!extension_loaded('gd')) {
        return move_uploaded_file($file_tmp, $dest_path);
    }
    
    $im = null;
    if ($ext === 'png') {
        $im = @imagecreatefrompng($file_tmp);
    } elseif ($ext === 'webp') {
        $im = @imagecreatefromwebp($file_tmp);
    } elseif (in_array($ext, ['jpg', 'jpeg'])) {
        $im = @imagecreatefromjpeg($file_tmp);
    } elseif ($ext === 'gif') {
        $im = @imagecreatefromgif($file_tmp);
    }
    
    if (!$im) {
        return move_uploaded_file($file_tmp, $dest_path);
    }
    
    $width = imagesx($im);
    $height = imagesy($im);
    
    $max_dim = 800;
    if ($width > $max_dim || $height > $max_dim) {
        if ($width > $height) {
            $new_width = $max_dim;
            $new_height = floor($height * ($max_dim / $width));
        } else {
            $new_height = $max_dim;
            $new_width = floor($width * ($max_dim / $height));
        }
        
        $tmp = imagecreatetruecolor($new_width, $new_height);
        if ($ext === 'png') {
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
            $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
            imagefill($tmp, 0, 0, $transparent);
        }
        
        imagecopyresampled($tmp, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($im);
        $im = $tmp;
    }
    
    $success = false;
    if ($ext === 'png') {
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $success = @imagepng($im, $dest_path, 8);
    } elseif ($ext === 'webp') {
        $success = @imagewebp($im, $dest_path, 75);
    } else {
        $success = @imagejpeg($im, $dest_path, 75);
    }
    
    imagedestroy($im);
    return $success;
}

// 1. PROCESS ACTIONS (ADD / EDIT / DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sync_all_products'])) {
        $stmt = $pdo->query("SELECT id FROM products");
        $all_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $synced_count = 0;
        foreach ($all_ids as $p_id) {
            if (sync_product_to_supabase($p_id, 'upsert')) {
                $synced_count++;
            }
        }
        $success_message = "Successfully synced {$synced_count} products to the storefront cloud database!";
    } else {
        $action = $_POST['action'] ?? '';

        // ADD PRODUCT ACTION
        if ($action === 'add') {
        $barcode = trim($_POST['barcode'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : 0.0;
        $purchase_price = isset($_POST['purchase_price']) && $_POST['purchase_price'] !== '' ? (float)$_POST['purchase_price'] : 0.0;
        $old_price = isset($_POST['old_price']) && $_POST['old_price'] !== '' ? (float)$_POST['old_price'] : null;
        $discount_percentage = isset($_POST['discount_percentage']) && $_POST['discount_percentage'] !== '' ? (int)$_POST['discount_percentage'] : 0;
        $stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? (int)$_POST['stock_quantity'] : 0;
        $weight = trim($_POST['weight'] ?? '');
        $unit = trim($_POST['unit'] ?? 'pcs');
        if (empty($unit)) {
            $unit = 'pcs';
        }
        $category = trim($_POST['category'] ?? '');
        if (empty($category)) {
            $category = 'anaj';
        }

        // Auto-calculate missing values
        if ($old_price !== null && $old_price > $price && $discount_percentage === 0) {
            $discount_percentage = (int)round((($old_price - $price) / $old_price) * 100);
        } elseif ($discount_percentage > 0 && ($old_price === null || $old_price == 0.0)) {
            $old_price = (float)round($price / (1 - ($discount_percentage / 100)), 2);
        }

        try {
            // Auto-generate barcode if empty
            if (empty($barcode)) {
                do {
                    $barcode = (string)mt_rand(10000000, 99999999);
                    $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = :barcode");
                    $stmt->execute(['barcode' => $barcode]);
                } while ($stmt->fetch());
            }

            // Set default name if empty
            if (empty($name)) {
                $name = "Product - " . $barcode;
            }

            // Check duplicate barcode
            $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = :barcode");
            $stmt->execute(['barcode' => $barcode]);
            if ($stmt->fetch()) {
                $error_message = "Product with Barcode '{$barcode}' already exists in registry.";
            } else {
                $image_path = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        if ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE) {
                            $error_message = "The uploaded file exceeds the upload limit set on the server. To fix this, open your XAMPP Control Panel, click 'Config' next to Apache, select 'php.ini', search for 'upload_max_filesize' and 'post_max_size', increase their values (e.g. to 50M), save the file, and restart Apache.";
                        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                            $error_message = "File upload failed with error code: " . $_FILES['image']['error'];
                        } else {
                            $file_tmp = $_FILES['image']['tmp_name'];
                            $file_name = $_FILES['image']['name'];
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            
                            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            if (in_array($file_ext, $allowed_exts)) {
                                $new_file_name = uniqid('prod_', true) . '.' . $file_ext;
                                $upload_dir = __DIR__ . '/../assets/images/products/';
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0777, true);
                                }
                                $dest_path = $upload_dir . $new_file_name;
                                if (compressAndSaveUploadedImage($file_tmp, $dest_path, $file_ext)) {
                                    $image_path = 'assets/images/products/' . $new_file_name;
                                    // Save backup copy outside public_html
                                    $backup_dir = __DIR__ . '/../product_uploads/';
                                    if (!is_dir($backup_dir)) {
                                        @mkdir($backup_dir, 0777, true);
                                    }
                                    @copy($dest_path, $backup_dir . $new_file_name);
                                } else {
                                    $error_message = "Failed to upload product image to folder.";
                                }
                            } else {
                                $error_message = "Invalid product image type. Allowed types: " . implode(', ', $allowed_exts);
                            }
                        }
                    }

                    if (empty($error_message)) {
                        $stmt = $pdo->prepare("INSERT INTO products (barcode, name, description, price, purchase_price, stock_quantity, weight, unit, category, image, old_price, discount_percentage) 
                                               VALUES (:barcode, :name, :description, :price, :purchase_price, :stock, :weight, :unit, :category, :image, :old_price, :discount_percentage)");
                        $stmt->execute([
                            'barcode' => $barcode,
                            'name' => $name,
                            'description' => $description,
                            'price' => $price,
                            'purchase_price' => $purchase_price,
                            'stock' => $stock_quantity,
                            'weight' => $weight,
                            'unit' => $unit,
                            'category' => $category,
                            'image' => $image_path,
                            'old_price' => $old_price,
                            'discount_percentage' => $discount_percentage
                        ]);
                        $new_id = $pdo->lastInsertId();
                        $success_message = "Product '{$name}' created successfully.";
                        sync_product_to_supabase($new_id, 'upsert');
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Failed to add product: " . $e->getMessage();
            }
    }

    // EDIT PRODUCT ACTION
    elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : 0.0;
        $purchase_price = isset($_POST['purchase_price']) && $_POST['purchase_price'] !== '' ? (float)$_POST['purchase_price'] : 0.0;
        $old_price = isset($_POST['old_price']) && $_POST['old_price'] !== '' ? (float)$_POST['old_price'] : null;
        $discount_percentage = isset($_POST['discount_percentage']) && $_POST['discount_percentage'] !== '' ? (int)$_POST['discount_percentage'] : 0;
        $stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? (int)$_POST['stock_quantity'] : 0;
        $weight = trim($_POST['weight'] ?? '');
        $unit = trim($_POST['unit'] ?? 'pcs');
        if (empty($unit)) {
            $unit = 'pcs';
        }
        $category = trim($_POST['category'] ?? '');
        if (empty($category)) {
            $category = 'anaj';
        }

        // Auto-calculate missing values
        if ($old_price !== null && $old_price > $price && $discount_percentage === 0) {
            $discount_percentage = (int)round((($old_price - $price) / $old_price) * 100);
        } elseif ($discount_percentage > 0 && ($old_price === null || $old_price == 0.0)) {
            $old_price = (float)round($price / (1 - ($discount_percentage / 100)), 2);
        }

        if ($id <= 0) {
            $error_message = "Invalid product update parameters.";
        } else {
            try {
                // Auto-generate barcode if empty
                if (empty($barcode)) {
                    do {
                        $barcode = (string)mt_rand(10000000, 99999999);
                        $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = :barcode AND id != :id");
                        $stmt->execute(['barcode' => $barcode, 'id' => $id]);
                    } while ($stmt->fetch());
                }

                // Set default name if empty
                if (empty($name)) {
                    $name = "Product - " . $barcode;
                }

                // Check barcode duplicate excluding current product
                $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = :barcode AND id != :id");
                $stmt->execute(['barcode' => $barcode, 'id' => $id]);
                if ($stmt->fetch()) {
                    $error_message = "Barcode '{$barcode}' is registered under another product.";
                } else {
                    // Fetch existing image path
                    $stmt_curr = $pdo->prepare("SELECT image FROM products WHERE id = :id");
                    $stmt_curr->execute(['id' => $id]);
                    $curr_prod = $stmt_curr->fetch();
                    $image_path = $curr_prod ? $curr_prod['image'] : null;

                    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        if ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE) {
                            $error_message = "The uploaded file exceeds the upload limit set on the server. To fix this, open your XAMPP Control Panel, click 'Config' next to Apache, select 'php.ini', search for 'upload_max_filesize' and 'post_max_size', increase their values (e.g. to 50M), save the file, and restart Apache.";
                        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                            $error_message = "File upload failed with error code: " . $_FILES['image']['error'];
                        } else {
                            $file_tmp = $_FILES['image']['tmp_name'];
                            $file_name = $_FILES['image']['name'];
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            
                            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            if (in_array($file_ext, $allowed_exts)) {
                                $new_file_name = uniqid('prod_', true) . '.' . $file_ext;
                                $upload_dir = __DIR__ . '/../assets/images/products/';
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0777, true);
                                }
                                $dest_path = $upload_dir . $new_file_name;
                                if (compressAndSaveUploadedImage($file_tmp, $dest_path, $file_ext)) {
                                    // Remove old image file from disk and backup
                                    if ($image_path) {
                                        @unlink(__DIR__ . '/../' . $image_path);
                                        $old_filename = basename($image_path);
                                        @unlink(__DIR__ . '/../product_uploads/' . $old_filename);
                                    }
                                    $image_path = 'assets/images/products/' . $new_file_name;
                                    // Save backup copy outside public_html
                                    $backup_dir = __DIR__ . '/../product_uploads/';
                                    if (!is_dir($backup_dir)) {
                                        @mkdir($backup_dir, 0777, true);
                                    }
                                    @copy($dest_path, $backup_dir . $new_file_name);
                                } else {
                                    $error_message = "Failed to upload product image to folder.";
                                }
                            } else {
                                $error_message = "Invalid product image type. Allowed types: " . implode(', ', $allowed_exts);
                            }
                        }
                    }

                    if (empty($error_message)) {
                        $stmt = $pdo->prepare("UPDATE products SET 
                                                barcode = :barcode, 
                                                name = :name, 
                                                description = :description, 
                                                price = :price, 
                                                purchase_price = :purchase_price, 
                                                old_price = :old_price,
                                                discount_percentage = :discount_percentage,
                                                stock_quantity = :stock, 
                                                weight = :weight, 
                                                unit = :unit, 
                                                category = :category,
                                                image = :image 
                                               WHERE id = :id");
                        $stmt->execute([
                            'barcode' => $barcode,
                            'name' => $name,
                            'description' => $description,
                            'price' => $price,
                            'purchase_price' => $purchase_price,
                            'old_price' => $old_price,
                            'discount_percentage' => $discount_percentage,
                            'stock' => $stock_quantity,
                            'weight' => $weight,
                            'unit' => $unit,
                            'category' => $category,
                            'image' => $image_path,
                            'id' => $id
                        ]);
                        $success_message = "Product '{$name}' updated successfully.";
                        sync_product_to_supabase($id, 'upsert');
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Failed to update product details: " . $e->getMessage();
            }
        }
    }

    // DELETE PRODUCT ACTION
    elseif ($action === 'delete') {
        if (!is_owner()) {
            $error_message = "Access Denied: Store Managers do not have permission to delete inventory products.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    // Fetch image to delete from disk
                    $stmt_curr = $pdo->prepare("SELECT image FROM products WHERE id = :id");
                    $stmt_curr->execute(['id' => $id]);
                    $prod = $stmt_curr->fetch();
                    if ($prod && !empty($prod['image'])) {
                        @unlink(__DIR__ . '/../' . $prod['image']);
                        $old_filename = basename($prod['image']);
                        @unlink(__DIR__ . '/../product_uploads/' . $old_filename);
                    }

                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $success_message = "Product successfully removed from inventory.";
                    sync_product_to_supabase($id, 'delete');
                } catch (PDOException $e) {
                    $error_message = "Failed to delete product. It might be referenced in sales logs.";
                }
            }
        }
    }

    // BULK DELETE ACTION
    elseif ($action === 'bulk_delete') {
        if (!is_owner()) {
            $error_message = "Access Denied: Store Managers do not have permission to delete inventory products.";
        } else {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_map('intval', $ids);
                try {
                    $in_clause = implode(',', array_fill(0, count($ids), '?'));
                    
                    // Fetch images of all these products to delete files from disk
                    $stmt_img = $pdo->prepare("SELECT image FROM products WHERE id IN ($in_clause)");
                    $stmt_img->execute($ids);
                    $products_to_del = $stmt_img->fetchAll();
                    
                    foreach ($products_to_del as $p_del) {
                        if (!empty($p_del['image']) && file_exists(__DIR__ . '/../' . $p_del['image'])) {
                            @unlink(__DIR__ . '/../' . $p_del['image']);
                        }
                    }
                    
                    // Delete products from database
                    $stmt_del = $pdo->prepare("DELETE FROM products WHERE id IN ($in_clause)");
                    $stmt_del->execute($ids);
                    $success_message = "Selected products and their images were successfully deleted from inventory.";
                    foreach ($ids as $id_del) {
                        sync_product_to_supabase($id_del, 'delete');
                    }
                } catch (PDOException $e) {
                    $error_message = "Failed to delete selected products. Some items might be referenced in sales logs.";
                }
            } else {
                $error_message = "No products were selected for deletion.";
            }
        }
    }

    // BULK EXPORT ACTION
    elseif ($action === 'bulk_export') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $ids = array_map('intval', $ids);
            $in_clause = implode(',', array_fill(0, count($ids), '?'));
            try {
                $stmt_export = $pdo->prepare("SELECT barcode, name, category, stock_quantity FROM products WHERE id IN ($in_clause) ORDER BY id DESC");
                $stmt_export->execute($ids);
                $export_products = $stmt_export->fetchAll();
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=selected_products_export_' . date('Ymd_His') . '.csv');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Barcode', 'Name', 'Category', 'Stock Quantity']);
                
                foreach ($export_products as $p_exp) {
                    fputcsv($output, [
                        $p_exp['barcode'],
                        $p_exp['name'],
                        $CATEGORIES[$p_exp['category']]['name'] ?? $p_exp['category'],
                        $p_exp['stock_quantity']
                    ]);
                }
                fclose($output);
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to export selected products: " . $e->getMessage();
            }
        } else {
            $error_message = "No products were selected for export.";
        }
    }
}

// 2. FETCH INVENTORY FOR RENDERING
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_cat = isset($_GET['category']) ? trim($_GET['category']) : '';

try {
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    if (!empty($search_query)) {
        $sql .= " AND (name LIKE :search1 OR barcode LIKE :search2)";
        $params['search1'] = "%" . $search_query . "%";
        $params['search2'] = "%" . $search_query . "%";
    }

    if (array_key_exists($filter_cat, $CATEGORIES)) {
        if ($filter_cat === 'cosmetics') {
            $sql .= " AND category IN ('cosmetics', 'shampoo', 'soap', 'toothpaste', 'body_wash', 'deodorant')";
        } else {
            $sql .= " AND category = :category";
            $params['category'] = $filter_cat;
        }
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error querying inventory details: " . $e->getMessage());
}
$current_theme = get_setting('active_theme', 'emerald_green');
$dark_themes = ['midnight_indigo', 'cyberpunk_neon', 'deep_purple', 'forest_dark', 'forest_green', 'crimson_dark', 'crimson_rose'];
$html_class = in_array($current_theme, $dark_themes) ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $html_class; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
    <title>HR Traders - Product Inventory Manager</title>
    <script src="<?php echo BASE_URL; ?>assets/js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--theme-primary)',
                        emerald: {
                            50: 'var(--theme-emerald-50, #f0fdf4)',
                            100: 'var(--theme-emerald-100, #dcfce7)',
                            200: 'var(--theme-emerald-200, #bbf7d0)',
                            500: 'var(--theme-primary-hover)',
                            600: 'var(--theme-primary)',
                            700: 'var(--theme-primary-hover)',
                            800: 'var(--theme-primary-hover)',
                        },
                        darkbg: '#090d16',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.3">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/all.min.css">
</head>
<body class="theme-<?php echo get_setting('active_theme', 'emerald_green'); ?> bg-slate-50 text-slate-800 min-h-screen flex flex-col">

<!-- HEADER -->
<header class="bg-white border-b border-slate-200 px-4 py-3 md:px-6 md:py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3 z-10 flex-shrink-0">
    <div class="flex items-center justify-between w-full md:w-auto">
        <span class="text-base md:text-lg font-black text-emerald-600 tracking-wider">
            HR TRADERS <span class="text-[10px] md:text-xs text-slate-500 font-bold uppercase">Inventory Desk</span>
        </span>
        <!-- Mobile Logout button -->
        <a href="<?php echo BASE_URL; ?>logout.php" class="md:hidden px-2.5 py-1.5 bg-rose-50 border border-rose-200 hover:bg-rose-500 hover:text-white text-rose-600 text-xs rounded-xl font-bold transition-all">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
    
    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto pb-1 md:pb-0">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] md:text-xs rounded-xl font-bold border border-slate-300 transition-colors flex-shrink-0">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <!-- Desktop Logout button -->
        <a href="<?php echo BASE_URL; ?>logout.php" class="hidden md:flex px-3.5 py-1.5 bg-rose-50 border border-rose-200 hover:bg-rose-500 hover:text-white text-rose-600 text-xs rounded-xl font-bold transition-all flex-shrink-0">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </a>
    </div>
</header>

<main class="flex-1 max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 w-full space-y-6">

    <!-- Toast Indicators -->
    <?php if (!empty($success_message)): ?>
        <div class="p-4 bg-emerald-50 border border-emerald-250 rounded-2xl text-emerald-700 text-xs flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="p-4 bg-rose-50 border border-rose-250 rounded-2xl text-rose-700 text-xs flex items-center gap-2">
            <i class="fas fa-triangle-exclamation"></i>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <!-- INVENTORY CONTROL ROW -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Search and Filters form -->
        <form action="products.php" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="relative w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-505">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="admin-search" name="search" value="<?php echo sanitize($search_query); ?>" autocomplete="off"
                       class="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-900"
                       placeholder="Search name or barcode...">
                <!-- Suggestions Dropdown -->
                <div id="admin-search-results" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-2xl hidden z-50 max-h-64 overflow-y-auto"></div>
            </div>

            <select name="category" onchange="this.form.submit()"
                    class="bg-white border border-slate-300 px-3 py-2 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-700">
                <option value="">All Categories</option>
                <?php foreach ($CATEGORIES as $key => $cat): ?>
                    <option value="<?php echo $key; ?>" <?php echo $filter_cat === $key ? 'selected' : ''; ?>>
                        <?php echo $cat['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if (!empty($search_query) || !empty($filter_cat)): ?>
                <a href="products.php" class="text-xs text-slate-500 hover:text-slate-800 font-semibold">Clear</a>
            <?php endif; ?>
        </form>

        <div class="flex items-center gap-2 self-start md:self-auto">
            <!-- Sync to Storefront button -->
            <form method="POST" class="inline">
                <button type="submit" name="sync_all_products"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition-all shadow-lg shadow-blue-600/10">
                    <i class="fas fa-sync"></i> Sync Storefront Cloud
                </button>
            </form>
            
            <!-- Add product button -->
            <button onclick="toggleModal('add-product-modal', true)" 
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition-all shadow-lg shadow-emerald-600/10">
                <i class="fas fa-plus"></i> Add New Product
            </button>
        </div>
    </div>

    <!-- PRODUCTS DATA GRID TABLE -->
    <div class="glass-panel bg-white shadow-sm rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider">
                        <th class="p-4 pl-6 pr-4 w-24 text-center select-none">
                            <div class="flex flex-col items-center justify-center">
                                <input type="checkbox" id="select-all" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 transition-all cursor-pointer">
                                <span class="text-[10px] text-slate-400 font-normal block mt-0.5 normal-case tracking-normal">Select All</span>
                            </div>
                        </th>
                        <th class="p-4 pl-4">Barcode</th>
                        <th class="p-4">Name / Title</th>
                        <th class="p-4">Category</th>
                        <th class="p-4 text-right">Purchase Price</th>
                        <th class="p-4 text-right">Selling Price</th>
                        <th class="p-4 text-center">Unit / Weight</th>
                        <th class="p-4 text-center">Stock</th>
                        <th class="p-4 text-center pr-5">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="p-16 text-center text-slate-400">No products matching the selected filters found in catalog.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $prod): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 pl-6 pr-4 text-center w-24">
                                    <input type="checkbox" name="product_ids[]" value="<?php echo $prod['id']; ?>" class="product-select-checkbox w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 transition-all cursor-pointer">
                                </td>
                                <td class="p-4 pl-4 font-mono text-slate-655 font-semibold"><?php echo sanitize($prod['barcode']); ?></td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <?php 
                                        $img_src = !empty($prod['image']) ? BASE_URL . $prod['image'] : BASE_URL . 'assets/images/placeholder.svg';
                                        ?>
                                        <img src="<?php echo $img_src; ?>" alt="Product" class="w-10 h-10 object-cover rounded-lg border border-slate-200 bg-slate-50 flex-shrink-0">
                                        <div>
                                            <strong class="text-slate-900 text-sm block"><?php echo sanitize($prod['name']); ?></strong>
                                            <span class="text-[10px] text-slate-500 truncate block max-w-[200px]" title="<?php echo sanitize($prod['description']); ?>">
                                                <?php echo sanitize($prod['description']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 font-semibold text-slate-700">
                                    <?php echo $CATEGORIES[$prod['category']]['name'] ?? $prod['category']; ?>
                                </td>
                                <td class="p-4 text-right font-mono font-semibold text-slate-605"><?php echo ($prod['purchase_price'] > 0) ? format_price($prod['purchase_price']) : '-'; ?></td>
                                <td class="p-4 text-right font-mono font-bold text-emerald-600">
                                    <?php echo format_price($prod['price']); ?>
                                    <?php if ($prod['old_price'] > $prod['price'] || $prod['discount_percentage'] > 0): ?>
                                        <?php 
                                        $old_pr = $prod['old_price'] ?: ($prod['price'] / (1 - ($prod['discount_percentage'] / 100)));
                                        $pct = $prod['discount_percentage'] ?: (int)round((($old_pr - $prod['price']) / $old_pr) * 100);
                                        ?>
                                        <div class="text-[10px] text-slate-400 line-through font-normal"><?php echo format_price($old_pr); ?></div>
                                        <div class="text-[9px] text-rose-500 font-extrabold uppercase tracking-wide">Flat <?php echo $pct; ?>% OFF</div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center font-semibold text-slate-700"><?php echo !empty($prod['weight']) ? sanitize($prod['weight']) . ' (' . sanitize($prod['unit']) . ')' : sanitize($prod['unit']); ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded font-bold font-mono text-[11px] <?php echo $prod['stock_quantity'] <= 10 ? 'bg-rose-50 text-rose-700 border border-rose-200 animate-pulse' : 'bg-slate-100 text-slate-700 border border-slate-200'; ?>">
                                        <?php echo $prod['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center pr-5 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit product trigger -->
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($prod), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                class="p-2 bg-slate-100 text-slate-600 hover:bg-slate-205 hover:text-slate-900 border border-slate-200 rounded-lg transition-colors" title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if (is_owner()): ?>
                                        <!-- Delete product trigger -->
                                        <form action="products.php" method="POST" class="inline-block m-0" onsubmit="return confirm('Remove product entirely from register?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                            <button type="submit" class="p-2 bg-slate-100 text-slate-500 hover:bg-rose-50 hover:text-rose-600 border border-slate-200 rounded-lg transition-colors" title="Delete Product">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ADD PRODUCT MODAL DIALOG -->
<div id="add-product-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="max-w-lg w-full bg-white border border-slate-250 rounded-3xl p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="font-bold text-base text-slate-900">Register New Inventory Product</h3>
            <button onclick="toggleModal('add-product-modal', false)" class="text-slate-400 hover:text-slate-800"><i class="fas fa-times"></i></button>
        </div>

        <form action="products.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-4 text-xs">
            <input type="hidden" name="action" value="add">
            
            <div class="col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Barcode (Scan/Type)</label>
                <input type="text" name="barcode" autocomplete="off"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-sm text-slate-900 font-bold focus:bg-slate-50/50">
            </div>

            <div class="col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Product Title / Name</label>
                <input type="text" name="name"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-900 font-bold focus:bg-slate-50/50">
            </div>

            <div class="col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Description</label>
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-770 focus:bg-slate-50/50"></textarea>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Purchase Price (Cost)</label>
                <input type="number" step="any" name="purchase_price" min="0" placeholder="0.00"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-emerald-600 font-bold focus:bg-emerald-50/20">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Selling Price</label>
                <input type="number" step="any" name="price" min="0" required
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-emerald-600 font-bold focus:bg-emerald-50/20">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Original Price (Old Price)</label>
                <input type="number" step="any" name="old_price" min="0" placeholder="e.g. 1000.00"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-slate-600 focus:bg-slate-50/50">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Discount Percentage (%)</label>
                <input type="number" name="discount_percentage" min="0" max="100" placeholder="e.g. 15"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-slate-600 focus:bg-slate-50/50">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" placeholder="0"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-slate-900 font-bold focus:bg-slate-50/50">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Category</label>
                <select name="category"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-700 focus:bg-slate-50/50">
                    <?php foreach ($CATEGORIES as $key => $cat): ?>
                        <option value="<?php echo $key; ?>"><?php echo $cat['name']; ?></option>
                     <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Unit Weight (e.g. 1 kg)</label>
                <input type="text" name="weight" placeholder="e.g. 1 kg, 500 g"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-800 focus:bg-slate-50/50">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Measuring Unit</label>
                <select name="unit"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-750 focus:bg-slate-50/50">
                    <option value="pcs">pcs (Pieces)</option>
                    <option value="kg">kg (Kilograms)</option>
                    <option value="pack">pack (Packets)</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Product Image</label>
                <input type="file" name="image" accept="image/*" onchange="checkFileSize(this)"
                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-700 focus:bg-slate-50/50">
            </div>

            <div class="col-span-2 pt-3">
                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-3 shadow-md shadow-emerald-600/10">
                    Add Product to Stock
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT PRODUCT MODAL DIALOG -->
<div id="edit-product-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="max-w-lg w-full bg-white border border-slate-250 rounded-3xl p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="font-bold text-base text-slate-900">Modify Registered Product</h3>
            <button onclick="toggleModal('edit-product-modal', false)" class="text-slate-400 hover:text-slate-800"><i class="fas fa-times"></i></button>
        </div>

        <form action="products.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-4 text-xs">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit-id" name="id">
            
            <div class="col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Barcode</label>
                <input type="text" id="edit-barcode" name="barcode" autocomplete="off"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-sm text-slate-900 font-bold focus:bg-slate-50/50">
            </div>

            <div class="col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Product Name</label>
                <input type="text" id="edit-name" name="name"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-900 font-bold focus:bg-slate-50/50">
            </div>

            <div class="col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Description</label>
                <textarea id="edit-description" name="description" rows="2"
                          class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-700 focus:bg-slate-50/50"></textarea>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Purchase Price (Cost)</label>
                <input type="number" step="any" id="edit-purchase-price" name="purchase_price" min="0" placeholder="0.00"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-emerald-600 font-bold focus:bg-emerald-50/20">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Selling Price</label>
                <input type="number" step="any" id="edit-price" name="price" min="0" required
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-emerald-600 font-bold focus:bg-emerald-50/20">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Original Price (Old Price)</label>
                <input type="number" step="any" id="edit-old-price" name="old_price" min="0" placeholder="e.g. 1000.00"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-slate-600 focus:bg-slate-50/50">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Discount Percentage (%)</label>
                <input type="number" id="edit-discount-percentage" name="discount_percentage" min="0" max="100" placeholder="e.g. 15"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-slate-600 focus:bg-slate-50/50">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Stock Quantity</label>
                <input type="number" id="edit-stock" name="stock_quantity" min="0"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 font-mono text-slate-900 font-bold focus:bg-slate-50/50">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Category</label>
                <select id="edit-category" name="category"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-700 focus:bg-slate-50/50">
                    <?php foreach ($CATEGORIES as $key => $cat): ?>
                        <option value="<?php echo $key; ?>"><?php echo $cat['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Unit Weight (e.g. 1 kg)</label>
                <input type="text" id="edit-weight" name="weight" placeholder="e.g. 1 kg, 500 g"
                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-800 focus:bg-slate-50/50">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Unit</label>
                <select id="edit-unit" name="unit"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-slate-750 focus:bg-slate-50/50">
                    <option value="pcs">pcs (Pieces)</option>
                    <option value="kg">kg (Kilograms)</option>
                    <option value="pack">pack (Packets)</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Product Image</label>
                <div id="edit-image-preview-container" class="mb-2 hidden flex items-center gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                    <img id="edit-image-preview" src="" alt="Product Preview" class="w-12 h-12 object-cover rounded-lg border border-slate-300">
                    <span class="text-[10px] text-slate-500">Current uploaded image</span>
                </div>
                <input type="file" name="image" accept="image/*" onchange="checkFileSize(this)"
                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 text-xs text-slate-700 focus:bg-slate-50/50">
            </div>

            <div class="col-span-2 pt-3">
                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-colors uppercase tracking-widest pt-3 shadow-md shadow-emerald-600/10">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FLOATING ACTION BAR FOR BULK ACTIONS -->
<div id="bulk-actions-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-white border border-slate-200 shadow-2xl rounded-2xl px-6 py-4 flex items-center justify-between gap-6 transition-all duration-300 translate-y-24 opacity-0 pointer-events-none z-30 max-w-xl w-full">
    <div class="text-xs text-slate-700 font-semibold flex items-center">
        <span id="selected-count" class="bg-emerald-50 text-emerald-700 border border-emerald-255 px-2.5 py-1 rounded-lg mr-2 font-bold">0</span> Selected
    </div>
    <div class="flex items-center gap-3">
        <button id="bulk-export-btn" type="button" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-205 text-slate-700 text-xs rounded-xl font-bold border border-slate-300 transition-all flex items-center gap-1.5 shadow-sm">
            <i class="fas fa-file-csv text-emerald-600 text-sm"></i> Export CSV
        </button>
        <?php if (is_owner()): ?>
        <button id="bulk-delete-btn" type="button" class="px-4 py-2.5 bg-rose-50 border border-rose-200 hover:bg-rose-600 hover:text-white hover:border-rose-600 text-rose-600 text-xs rounded-xl font-bold transition-all flex items-center gap-1.5 shadow-sm">
            <i class="fas fa-trash-can text-sm"></i> Delete Selected
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Bulk Action Hidden Form -->
<form id="bulk-action-form" action="products.php" method="POST" class="hidden">
    <input type="hidden" name="action" id="bulk-form-action" value="">
    <div id="bulk-form-ids-container"></div>
</form>

<!-- Scripts -->
<script>
const BASE_URL = "<?php echo BASE_URL; ?>";

function checkFileSize(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            alert("Sizing Warning: Yeh image bohot barri hai (" + (file.size / (1024 * 1024)).toFixed(2) + " MB). 10MB se barri images upload hone me time le sakti hain ya network issue se fail ho sakti hain. Koshish karein ke image compress kar ke upload karein.");
        }
    }
}

function toggleModal(modalId, show) {
    const modal = document.getElementById(modalId);
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

function openEditModal(product) {
    document.getElementById('edit-id').value = product.id;
    document.getElementById('edit-barcode').value = product.barcode;
    document.getElementById('edit-name').value = product.name;
    document.getElementById('edit-description').value = product.description;
    document.getElementById('edit-purchase-price').value = (parseFloat(product.purchase_price) > 0) ? product.purchase_price : '';
    document.getElementById('edit-price').value = product.price;
    document.getElementById('edit-old-price').value = product.old_price ? product.old_price : '';
    document.getElementById('edit-discount-percentage').value = product.discount_percentage ? product.discount_percentage : '';
    document.getElementById('edit-stock').value = product.stock_quantity;
    document.getElementById('edit-category').value = product.category;
    document.getElementById('edit-weight').value = product.weight ? product.weight : '';
    document.getElementById('edit-unit').value = product.unit;

    const imgPreviewContainer = document.getElementById('edit-image-preview-container');
    const imgPreview = document.getElementById('edit-image-preview');
    if (product.image) {
        imgPreview.src = BASE_URL + product.image;
        imgPreviewContainer.classList.remove('hidden');
    } else {
        imgPreview.src = "";
        imgPreviewContainer.classList.add('hidden');
    }

    toggleModal('edit-product-modal', true);
}

// Bulk Actions JS Logic
document.addEventListener('DOMContentLoaded', () => {
    const selectAllCheckbox = document.getElementById('select-all');
    const productCheckboxes = document.querySelectorAll('.product-select-checkbox');
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkExportBtn = document.getElementById('bulk-export-btn');
    const bulkActionForm = document.getElementById('bulk-action-form');
    const bulkFormActionInput = document.getElementById('bulk-form-action');
    const bulkFormIdsContainer = document.getElementById('bulk-form-ids-container');

    // Function to update the visibility and stats of the Bulk Actions Bar
    function updateBulkActionsBar() {
        const checkedBoxes = document.querySelectorAll('.product-select-checkbox:checked');
        const checkedCount = checkedBoxes.length;

        if (checkedCount > 0) {
            selectedCountSpan.innerText = checkedCount;
            bulkActionsBar.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
        } else {
            bulkActionsBar.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
        }

        // Keep Select All checkbox state in sync
        if (checkedCount === productCheckboxes.length && productCheckboxes.length > 0) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount > 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }

    // Event listener for Select All
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
            productCheckboxes.forEach(cb => {
                cb.checked = e.target.checked;
            });
            updateBulkActionsBar();
        });
    }

    // Event listener for individual checkboxes
    productCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            updateBulkActionsBar();
        });
    });

    // Populate dynamic IDs into form container
    function populateFormIds() {
        bulkFormIdsContainer.innerHTML = '';
        const checkedBoxes = document.querySelectorAll('.product-select-checkbox:checked');
        checkedBoxes.forEach(cb => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'ids[]';
            hiddenInput.value = cb.value;
            bulkFormIdsContainer.appendChild(hiddenInput);
        });
    }

    // Bulk Delete Click Handler
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', () => {
            const checkedCount = document.querySelectorAll('.product-select-checkbox:checked').length;
            if (confirm(`Are you sure you want to delete the ${checkedCount} selected products? This will also remove their images from disk and cannot be undone.`)) {
                bulkFormActionInput.value = 'bulk_delete';
                populateFormIds();
                bulkActionForm.submit();
            }
        });
    }

    // Bulk Export Click Handler
    if (bulkExportBtn) {
        bulkExportBtn.addEventListener('click', () => {
            bulkFormActionInput.value = 'bulk_export';
            populateFormIds();
            bulkActionForm.submit();
        });
    }

    // Admin Live Autocomplete Search implementation
    const adminSearchInput = document.getElementById('admin-search');
    const adminSuggestionsBox = document.getElementById('admin-search-results');
    
    if (adminSearchInput && adminSuggestionsBox) {
        let debounceTimer;
        let abortController = null;

        adminSearchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();

            if (query.length < 1) {
                adminSuggestionsBox.innerHTML = '';
                adminSuggestionsBox.classList.add('hidden');
                if (abortController) {
                    abortController.abort();
                }
                filterTableRows('');
                return;
            }

            // Real-time client-side filter
            filterTableRows(query);

            debounceTimer = setTimeout(() => {
                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();
                const signal = abortController.signal;

                fetch(BASE_URL + `api/live_search.php?q=${encodeURIComponent(query)}`, { signal })
                    .then(res => res.json())
                    .then(products => {
                        if (products.length === 0) {
                            adminSuggestionsBox.innerHTML = `<div class="p-3 text-xs text-slate-500 text-center">No catalog matches found</div>`;
                            adminSuggestionsBox.classList.remove('hidden');
                            return;
                        }

                        let html = '';
                        products.forEach(p => {
                            const safeName = p.name.replace(/['"\\]/g, '\\$&');
                            html += `
                                <div onclick="selectAdminSearch('${safeName}')" 
                                     class="p-2.5 hover:bg-slate-50 flex items-center justify-between gap-3 border-b border-slate-100 last:border-0 cursor-pointer transition-colors">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <img src="${BASE_URL + p.image}" class="w-7 h-7 object-cover rounded-md border border-slate-200 bg-slate-50 flex-shrink-0" loading="lazy">
                                        <div class="min-w-0 text-left">
                                            <h5 class="text-xs font-semibold text-slate-800 leading-tight truncate">${p.name}</h5>
                                            <p class="text-[10px] text-slate-400 font-mono">${p.barcode ? p.barcode : 'No Barcode'}</p>
                                        </div>
                                    </div>
                                    <span class="px-1.5 py-0.5 rounded text-[8px] uppercase font-bold bg-slate-100 text-slate-600 border border-slate-200 flex-shrink-0">
                                        Rs. ${p.price.toFixed(0)}
                                    </span>
                                </div>
                            `;
                        });
                        adminSuggestionsBox.innerHTML = html;
                        adminSuggestionsBox.classList.remove('hidden');
                    })
                    .catch(err => {
                        if (err.name !== 'AbortError') {
                            console.error(err);
                        }
                    });
            }, 250);
        });

        document.addEventListener('click', (e) => {
            if (!adminSearchInput.contains(e.target) && !adminSuggestionsBox.contains(e.target)) {
                adminSuggestionsBox.classList.add('hidden');
            }
        });
    }

    window.selectAdminSearch = function(name) {
        if (adminSearchInput) {
            adminSearchInput.value = name;
            adminSuggestionsBox.classList.add('hidden');
            adminSearchInput.form.submit();
        }
    };

    function filterTableRows(query) {
        query = query.toLowerCase().trim();
        const rows = document.querySelectorAll('table tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.id === 'no-match-row' || (row.cells.length === 1 && row.cells[0].colSpan === 9)) {
                return;
            }
            
            const barcodeCell = row.querySelector('td:nth-child(2)');
            const nameCell = row.querySelector('td:nth-child(3) strong');
            
            const barcode = barcodeCell ? barcodeCell.textContent.toLowerCase() : '';
            const name = nameCell ? nameCell.textContent.toLowerCase() : '';
            
            if (name.includes(query) || barcode.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        let noMatchRow = document.getElementById('no-match-row');
        if (visibleCount === 0 && query !== '') {
            if (!noMatchRow) {
                const tbody = document.querySelector('table tbody');
                noMatchRow = document.createElement('tr');
                noMatchRow.id = 'no-match-row';
                noMatchRow.className = 'bg-white';
                noMatchRow.innerHTML = `<td colspan="9" class="p-16 text-center text-slate-400 font-semibold">No products matching "${query}" found in current inventory.</td>`;
                tbody.appendChild(noMatchRow);
            }
        } else {
            if (noMatchRow) {
                noMatchRow.remove();
            }
        }
    }
});
</script>
</body>
</html>
