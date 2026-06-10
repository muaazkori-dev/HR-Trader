<?php
// HR Traders Financial Reports Exporter API
// Restrict access strictly to the owner role. Generates transactional CSV margins report.

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Strict Role restriction
require_role(['owner']);

// Set CSV download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=HR_Traders_Financials_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

// UTF-8 BOM header for Microsoft Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Headers matching detailed spreadsheet requirements
fputcsv($output, [
    'Transaction ID',
    'Date & Time',
    'Transaction Type',
    'Reference ID',
    'Gross Revenue',
    'Cost of Goods Sold (COGS)',
    'Net Profit',
    'Profit Margin (%)',
    'Payment Method',
    'Cashier / Operator'
]);

try {
    // Fetch consolidated sales logs joined with user records for cashier activity mapping
    $stmt = $pdo->query("SELECT s.*, u.name as cashier_name 
                         FROM sales s 
                         LEFT JOIN users u ON s.cashier_id = u.id 
                         ORDER BY s.id DESC");
    
    while ($row = $stmt->fetch()) {
        $total = (float)$row['total_amount'];
        $profit = (float)$row['total_profit'];
        $cogs = $total - $profit;
        
        $margin_pct = $total > 0 ? round(($profit / $total) * 100, 2) : 0.00;
        
        $ref_id = ($row['transaction_type'] === 'Online') 
            ? 'HRT-' . str_pad($row['order_id'], 5, '0', STR_PAD_LEFT) 
            : 'POS-' . str_pad($row['id'], 5, '0', STR_PAD_LEFT);

        fputcsv($output, [
            $row['id'],
            date('Y-m-d H:i:s', strtotime($row['created_at'])),
            $row['transaction_type'],
            $ref_id,
            number_format($total, 2, '.', ''),
            number_format($cogs, 2, '.', ''),
            number_format($profit, 2, '.', ''),
            number_format($margin_pct, 2, '.', '') . '%',
            $row['payment_method'],
            $row['cashier_name'] ? $row['cashier_name'] : 'System Auto'
        ]);
    }
} catch (PDOException $e) {
    // Write error if query fails
    fputcsv($output, ['Error: Failed to query database sales logs.', $e->getMessage()]);
}

fclose($output);
exit();
