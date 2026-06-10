<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $stmt = $pdo->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category");
    $rows = $stmt->fetchAll();
    echo "CATEGORIES IN DATABASE:\n";
    foreach ($rows as $row) {
        echo "- " . $row['category'] . ": " . $row['cnt'] . "\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
