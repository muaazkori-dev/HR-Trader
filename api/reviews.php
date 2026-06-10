<?php
// HR Traders Product Reviews & Ratings API
// Handles GET (fetch reviews & calculate averages) and POST (submit new review)

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Product ID.']);
        exit();
    }

    try {
        // Fetch product details first
        $stmt_prod = $pdo->prepare("SELECT id, name, description, category, price, weight, barcode, stock_quantity, image FROM products WHERE id = :pid");
        $stmt_prod->execute(['pid' => $product_id]);
        $product = $stmt_prod->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit();
        }

        // Fetch reviews
        $stmt = $pdo->prepare("SELECT reviewer_name, rating, comment, created_at FROM reviews WHERE product_id = :pid ORDER BY id DESC");
        $stmt->execute(['pid' => $product_id]);
        $reviews = $stmt->fetchAll();

        // Calculate average and counts
        $total_reviews = count($reviews);
        $average_rating = 0.0;
        if ($total_reviews > 0) {
            $sum = 0;
            foreach ($reviews as $rev) {
                $sum += (int)$rev['rating'];
            }
            $average_rating = round($sum / $total_reviews, 1);
        }

        // Sanitize output strings
        $sanitized_reviews = [];
        foreach ($reviews as $rev) {
            $sanitized_reviews[] = [
                'reviewer_name' => sanitize($rev['reviewer_name']),
                'rating' => (int)$rev['rating'],
                'comment' => sanitize($rev['comment']),
                'created_at' => $rev['created_at']
            ];
        }

        echo json_encode([
            'success' => true,
            'product_id' => $product_id,
            'product' => [
                'id' => (int)$product['id'],
                'name' => sanitize($product['name']),
                'description' => sanitize($product['description']),
                'category' => sanitize($product['category']),
                'price' => (float)$product['price'],
                'weight' => sanitize($product['weight']),
                'barcode' => sanitize($product['barcode']),
                'stock_quantity' => (int)$product['stock_quantity'],
                'image' => $product['image'] ? $product['image'] : 'assets/images/placeholder.svg'
            ],
            'average_rating' => (float)$average_rating,
            'total_reviews' => $total_reviews,
            'reviews' => $sanitized_reviews
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database query failed.']);
    }
    exit();

} elseif ($method === 'POST') {
    // Read input data
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $reviewer_name = isset($_POST['reviewer_name']) ? trim($_POST['reviewer_name']) : '';
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // If application/json is sent
    if ($product_id === 0) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if ($data) {
            $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
            $reviewer_name = isset($data['reviewer_name']) ? trim($data['reviewer_name']) : '';
            $rating = isset($data['rating']) ? (int)$data['rating'] : 0;
            $comment = isset($data['comment']) ? trim($data['comment']) : '';
        }
    }

    // Validations
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product reference.']);
        exit();
    }
    if (empty($reviewer_name)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your name.']);
        exit();
    }
    if (strlen($reviewer_name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Name must be less than 100 characters.']);
        exit();
    }
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Please select a rating between 1 and 5 stars.']);
        exit();
    }
    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Please write a comment.']);
        exit();
    }

    try {
        // Verify product exists
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = :pid");
        $stmt->execute(['pid' => $product_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Product does not exist.']);
            exit();
        }

        // Insert review
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, reviewer_name, rating, comment) VALUES (:pid, :name, :rating, :comment)");
        $stmt->execute([
            'pid' => $product_id,
            'name' => $reviewer_name,
            'rating' => $rating,
            'comment' => $comment
        ]);

        echo json_encode(['success' => true, 'message' => 'Ap ka review submit ho gaya hai! Shukriya.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save review in database.']);
    }
    exit();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}
