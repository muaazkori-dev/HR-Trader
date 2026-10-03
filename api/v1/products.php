<?php
// HR Traders Products & Categories REST API Endpoint (v1)
// Serves categories, product catalog, search, and detail data to mobile apps

require_once __DIR__ . '/helpers.php';

$data = get_request_data();
$action = $data['action'] ?? ($_GET['action'] ?? 'list');

switch ($action) {
    case 'categories':
        try {
            // Group by category with item count
            $stmt = $pdo->query("
                SELECT category, COUNT(*) as product_count 
                FROM products 
                WHERE category IS NOT NULL AND category != '' 
                GROUP BY category 
                ORDER BY product_count DESC
            ");
            $categories_raw = $stmt->fetchAll();

            $category_metadata = [
                'anaj' => ['title' => 'Anaj & Grains', 'image' => 'assets/images/categories/anaj.png'],
                'pulses_rice' => ['title' => 'Daalein & Rice', 'image' => 'assets/images/categories/pulses_rice.png'],
                'shampoo' => ['title' => 'Shampoo & Hair Care', 'image' => 'assets/images/categories/shampoo.png'],
                'soap' => ['title' => 'Soaps & Hygiene', 'image' => 'assets/images/categories/soap.png'],
                'cold_drinks' => ['title' => 'Cold Drinks & Beverages', 'image' => 'assets/images/categories/cold_drinks.png'],
                'beverages' => ['title' => 'Beverages & Juices', 'image' => 'assets/images/categories/beverages.png'],
                'water' => ['title' => 'Mineral Water', 'image' => 'assets/images/categories/water.png'],
                'ice_cream' => ['title' => 'Ice Cream & Desserts', 'image' => 'assets/images/categories/ice_cream.png'],
                'frozen_icecream' => ['title' => 'Frozen & Ice Creams', 'image' => 'assets/images/categories/frozen_icecream.png'],
                'milk' => ['title' => 'Dairy & Fresh Milk', 'image' => 'assets/images/categories/milk.png'],
                'cosmetics' => ['title' => 'Cosmetics & Beauty', 'image' => 'assets/images/categories/cosmetics.png'],
                'snacks_chips' => ['title' => 'Snacks & Biscuits', 'image' => 'assets/images/categories/snacks_chips.png']
            ];

            $formatted_categories = [];
            foreach ($categories_raw as $c) {
                $slug = $c['category'];
                $meta = $category_metadata[$slug] ?? [
                    'title' => ucwords(str_replace(['_', '-'], ' ', $slug)),
                    'image' => 'assets/images/categories/default.png'
                ];

                $formatted_categories[] = [
                    'slug' => $slug,
                    'name' => $meta['title'],
                    'product_count' => (int)$c['product_count'],
                    'image' => get_full_asset_url($meta['image'])
                ];
            }

            send_success($formatted_categories);
        } catch (PDOException $e) {
            send_error('Failed to load categories: ' . $e->getMessage(), 500);
        }
        break;

    case 'list':
        $category = trim($data['category'] ?? '');
        $search = trim($data['search'] ?? '');
        $sort = trim($data['sort'] ?? 'latest');
        $page = max(1, (int)($data['page'] ?? 1));
        $limit = min(50, max(5, (int)($data['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($category) && $category !== 'all') {
            $where[] = "category = :category";
            $params['category'] = $category;
        }

        if (!empty($search)) {
            $where[] = "(name LIKE :search OR description LIKE :search OR barcode = :exact_code)";
            $params['search'] = '%' . $search . '%';
            $params['exact_code'] = $search;
        }

        $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Sort order
        $order_sql = "ORDER BY id DESC";
        if ($sort === 'price_asc') {
            $order_sql = "ORDER BY price ASC";
        } elseif ($sort === 'price_desc') {
            $order_sql = "ORDER BY price DESC";
        } elseif ($sort === 'name_asc') {
            $order_sql = "ORDER BY name ASC";
        }

        try {
            // Count total items
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products {$where_sql}");
            $count_stmt->execute($params);
            $total_items = (int)$count_stmt->fetchColumn();

            // Fetch page items
            $stmt = $pdo->prepare("SELECT id, barcode, name, description, price, stock_quantity, weight, unit, category, image 
                                   FROM products 
                                   {$where_sql} 
                                   {$order_sql} 
                                   LIMIT :limit OFFSET :offset");

            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll();

            $products = [];
            foreach ($items as $item) {
                $item['price'] = (float)$item['price'];
                $item['stock_quantity'] = (int)$item['stock_quantity'];
                $item['in_stock'] = $item['stock_quantity'] > 0;
                $item['image_url'] = get_full_asset_url($item['image']);
                $products[] = $item;
            }

            send_success([
                'products' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'limit' => $limit,
                    'total_items' => $total_items,
                    'total_pages' => ceil($total_items / $limit)
                ]
            ]);
        } catch (PDOException $e) {
            send_error('Failed to load products: ' . $e->getMessage(), 500);
        }
        break;

    case 'detail':
        $product_id = (int)($data['id'] ?? 0);
        if ($product_id <= 0) {
            send_error('Valid product ID is required.');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, barcode, name, description, price, stock_quantity, weight, unit, category, image FROM products WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                send_error('Product not found.', 404);
            }

            $product['price'] = (float)$product['price'];
            $product['stock_quantity'] = (int)$product['stock_quantity'];
            $product['in_stock'] = $product['stock_quantity'] > 0;
            $product['image_url'] = get_full_asset_url($product['image']);

            // Fetch related products in the same category
            $rel_stmt = $pdo->prepare("SELECT id, name, price, weight, unit, image, stock_quantity 
                                      FROM products 
                                      WHERE category = :cat AND id != :id 
                                      ORDER BY id DESC LIMIT 6");
            $rel_stmt->execute(['cat' => $product['category'], 'id' => $product_id]);
            $related = $rel_stmt->fetchAll();

            foreach ($related as &$rel) {
                $rel['price'] = (float)$rel['price'];
                $rel['image_url'] = get_full_asset_url($rel['image']);
                $rel['in_stock'] = (int)$rel['stock_quantity'] > 0;
            }

            $product['related_products'] = $related;

            send_success($product);
        } catch (PDOException $e) {
            send_error('Failed to load product detail: ' . $e->getMessage(), 500);
        }
        break;

    case 'featured':
        try {
            // Get 10 popular/in-stock products across categories
            $stmt = $pdo->query("SELECT id, barcode, name, description, price, stock_quantity, weight, unit, category, image 
                                 FROM products 
                                 WHERE stock_quantity > 0 
                                 ORDER BY id DESC LIMIT 10");
            $items = $stmt->fetchAll();

            $products = [];
            foreach ($items as $item) {
                $item['price'] = (float)$item['price'];
                $item['stock_quantity'] = (int)$item['stock_quantity'];
                $item['in_stock'] = true;
                $item['image_url'] = get_full_asset_url($item['image']);
                $products[] = $item;
            }

            // Promotional banners
            $banners = [
                [
                    'id' => 1,
                    'title' => 'Fresh Groceries Delivered Fast',
                    'subtitle' => 'Premium grains, pulses, dairy & household brands',
                    'image' => get_full_asset_url('assets/images/hero_grocery_banner.png'),
                    'tag' => 'Premium Quality'
                ]
            ];

            send_success([
                'banners' => $banners,
                'featured_products' => $products
            ]);
        } catch (PDOException $e) {
            send_error('Failed to load featured data: ' . $e->getMessage(), 500);
        }
        break;

    default:
        send_error('Invalid action requested.');
}
