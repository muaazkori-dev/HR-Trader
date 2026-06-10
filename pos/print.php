<?php
// HR Traders POS Thermal Invoice Receipt Generator
// Formats receipt strictly for 58mm or 80mm roll size thermal printers

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure staff access
require_role(['owner', 'manager']);

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
$sale = null;
$sale_items = [];

if ($sale_id > 0) {
    try {
        // Fetch sale details
        $stmt = $pdo->prepare("SELECT s.*, u.name as cashier_name 
                               FROM sales s 
                               LEFT JOIN users u ON s.cashier_id = u.id 
                               WHERE s.id = :id");
        $stmt->execute(['id' => $sale_id]);
        $sale = $stmt->fetch();

        if ($sale) {
            // Fetch sale items
            $stmt_items = $pdo->prepare("SELECT si.*, p.name as prod_name, p.weight as prod_weight 
                                         FROM sale_items si 
                                         JOIN products p ON si.product_id = p.id 
                                         WHERE si.sale_id = :sale_id");
            $stmt_items->execute(['sale_id' => $sale_id]);
            $sale_items = $stmt_items->fetchAll();
        }
    } catch (PDOException $e) {
        $sale = null;
    }
}

if (!$sale) {
    die("Invoice Receipt Not Found.");
}

// Calculate pre-discount subtotal
$subtotal = 0;
foreach ($sale_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Net profit is stored directly, but here we calculate discount amount if any
$discount_amount = $subtotal - $sale['total_amount'];
$discount_percent = $subtotal > 0 ? round(($discount_amount / $subtotal) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Receipt #HRT-POS-<?php echo $sale['id']; ?></title>
    <style>
        /* CSS resets for standard receipts styles */
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 10px;
            width: 280px; /* Standard 80mm roll print boundary */
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        
        .header {
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
        }

        .meta-table, .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .meta-table td {
            padding: 2px 0;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .items-table th {
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            font-weight: bold;
        }
        .items-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .totals-section {
            font-size: 11px;
            margin-left: auto;
            width: 80%;
        }
        .totals-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-section td {
            padding: 2px 0;
        }

        .footer {
            margin-top: 15px;
            font-size: 10px;
            line-height: 1.4;
        }

        /* Print formatting */
        @page {
            size: auto;
            margin: 0; /* Hides browser header title and page margins */
        }
        @media print {
            body {
                padding: 5px;
                width: 100%;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Header Details -->
    <div class="header text-center">
        <h3><?php echo STORE_NAME; ?></h3>
        <p>Main Bazaar, Lahore, Pakistan</p>
        <p>Ph: +92 300 1234567 | WhatsApp: 0300 1234567</p>
    </div>

    <div class="divider"></div>

    <!-- Metadata Details -->
    <table class="meta-table">
        <tr>
            <td><strong>Invoice ID:</strong></td>
            <td class="text-right">#HRT-POS-<?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></td>
        </tr>
        <tr>
            <td><strong>Date:</strong></td>
            <td class="text-right"><?php echo date('d-M-Y h:i A', strtotime($sale['created_at'])); ?></td>
        </tr>
        <tr>
            <td><strong>Cashier:</strong></td>
            <td class="text-right"><?php echo sanitize($sale['cashier_name'] ? $sale['cashier_name'] : 'System'); ?></td>
        </tr>
        <tr>
            <td><strong>Type:</strong></td>
            <td class="text-right"><?php echo $sale['transaction_type'] === 'POS' ? 'In-Store POS' : 'Online Delivery'; ?></td>
        </tr>
        <tr>
            <td><strong>Payment:</strong></td>
            <td class="text-right"><?php echo sanitize($sale['payment_method']); ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Purchase Grid -->
    <table class="items-table">
        <thead>
            <tr>
                <th align="left">Item (Weight)</th>
                <th align="center">Qty</th>
                <th align="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale_items as $item): ?>
                <tr>
                    <td align="left">
                        <?php echo sanitize($item['prod_name']); ?>
                        <div style="font-size:9px; color:#555;"><?php echo !empty($item['prod_weight']) ? sanitize($item['prod_weight']) . ' @ ' : ''; ?><?php echo number_format($item['price'], 2); ?></div>
                    </td>
                    <td align="center"><?php echo $item['quantity']; ?></td>
                    <td align="right"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <!-- Financial calculations -->
    <div class="totals-section">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right"><?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <?php if ($discount_amount > 0): ?>
                <tr>
                    <td>Discount (<?php echo $discount_percent; ?>%):</td>
                    <td class="text-right">-<?php echo number_format($discount_amount, 2); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="bold">
                <td>Net Total:</td>
                <td class="text-right"><?php echo number_format($sale['total_amount'], 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    <div class="footer text-center">
        <p class="bold">Thank you for shopping!</p>
        <p>Software Powered by HR Traders POS</p>
        <p><?php echo date('d/m/Y h:i:s A'); ?></p>
    </div>

    <!-- Print Action Controller -->
    <script>
        window.onload = function() {
            window.print();
            // Automatically close the popup window after print dialog closes
            setTimeout(function() {
                window.close();
            }, 1000);
        }
    </script>
</body>
</html>
