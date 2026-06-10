<?php
// HR Traders Admin Live Order Alerts & Dashboard API
// Dynamically fetches the latest order ID, sales statistics, comparison bar metrics, and HTML for both desktop and mobile views.

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Restrict to logged-in staff (owner or manager)
if (!is_logged_in() || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // 1. Get latest order ID
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM orders");
    $latest_order_id = (int)($stmt->fetch()['max_id'] ?? 0);

    // 2. Fetch today's sales (POS & Online combined)
    $stmt = $pdo->query("SELECT SUM(total_amount) as today_sales FROM sales WHERE DATE(created_at) = CURRENT_DATE");
    $today_sales = (float)($stmt->fetch()['today_sales'] ?? 0.0);

    // 3. Fetch monthly sales (POS & Online combined)
    $stmt = $pdo->query("SELECT SUM(total_amount) as month_sales FROM sales WHERE MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)");
    $month_sales = (float)($stmt->fetch()['month_sales'] ?? 0.0);

    // 4. Cumulative Net Profit (only for owner)
    $total_profit = 0.0;
    if (is_owner()) {
        $stmt = $pdo->query("SELECT SUM(total_profit) as total_profit FROM sales");
        $total_profit = (float)($stmt->fetch()['total_profit'] ?? 0.0);
    }

    // 5. Volume counters
    $stmt = $pdo->query("SELECT COUNT(*) as online_count FROM orders");
    $online_count = (int)($stmt->fetch()['online_count'] ?? 0);

    $stmt = $pdo->query("SELECT COUNT(*) as pos_count FROM sales WHERE transaction_type = 'POS'");
    $pos_count = (int)($stmt->fetch()['pos_count'] ?? 0);

    // 6. Share values
    $stmt = $pdo->query("SELECT transaction_type, SUM(total_amount) as type_total FROM sales GROUP BY transaction_type");
    $shares = $stmt->fetchAll();
    $pos_share_val = 0;
    $online_share_val = 0;
    foreach ($shares as $sh) {
        if ($sh['transaction_type'] === 'POS') $pos_share_val = (float)$sh['type_total'];
        if ($sh['transaction_type'] === 'Online') $online_share_val = (float)$sh['type_total'];
    }

    $grand_sales = $pos_share_val + $online_share_val;
    $pos_pct = $grand_sales > 0 ? round(($pos_share_val / $grand_sales) * 100) : 0;
    $online_pct = $grand_sales > 0 ? round(($online_share_val / $grand_sales) * 100) : 0;

    // 7. Recent Orders list
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll();

    // Compile desktop HTML
    $orders_html = "";
    if (empty($recent_orders)) {
        $orders_html = '<tr><td colspan="5" class="py-6 text-center text-slate-400">No customer orders placed yet.</td></tr>';
    } else {
        foreach ($recent_orders as $ord) {
            $ref = "#HRT-" . str_pad($ord['id'], 5, '0', STR_PAD_LEFT);
            $customer_name = sanitize($ord['customer_name']);
            $customer_phone = sanitize($ord['customer_phone']);
            $customer_address = sanitize($ord['customer_address']);
            $formatted_total = format_price($ord['total_amount']);
            
            $status_class = "";
            switch($ord['status']) {
                case 'pending': $status_class = 'bg-amber-50 text-amber-700 border-amber-200'; break;
                case 'packaging': $status_class = 'bg-blue-50 text-blue-700 border-blue-200'; break;
                case 'out_for_delivery': $status_class = 'bg-purple-50 text-purple-700 border-purple-200'; break;
                case 'delivered': $status_class = 'bg-emerald-50 text-emerald-700 border-emerald-200'; break;
                case 'cancelled': $status_class = 'bg-rose-50 text-rose-700 border-rose-200'; break;
            }
            
            $orders_html .= '<tr>';
            $orders_html .= '<td class="py-3 pr-2 font-mono font-bold">' . $ref . '</td>';
            $orders_html .= '<td class="py-3">';
            $orders_html .= '<span class="block font-bold text-slate-805">' . $customer_name . '</span>';
            $orders_html .= '<span class="block text-[10px] text-slate-400 font-mono">' . $customer_phone . '</span>';
            $orders_html .= '</td>';
            $orders_html .= '<td class="py-3 max-w-[150px] truncate" title="' . $customer_address . '">' . $customer_address . '</td>';
            $orders_html .= '<td class="py-3 text-right font-bold text-emerald-600">' . $formatted_total . '</td>';
            $orders_html .= '<td class="py-3 text-center">';
            $orders_html .= '<span class="px-2 py-0.5 rounded-[4px] text-[8px] font-bold uppercase border ' . $status_class . '">' . $ord['status'] . '</span>';
            $orders_html .= '</td>';
            $orders_html .= '</tr>';
        }
    }

    // Compile mobile HTML
    $orders_mobile_html = "";
    if (empty($recent_orders)) {
        $orders_mobile_html = '<div class="py-6 text-center text-slate-400 text-xs">No customer orders placed yet.</div>';
    } else {
        foreach ($recent_orders as $ord) {
            $ref = "#HRT-" . str_pad($ord['id'], 5, '0', STR_PAD_LEFT);
            $customer_name = sanitize($ord['customer_name']);
            $customer_phone = sanitize($ord['customer_phone']);
            $customer_address = sanitize($ord['customer_address']);
            $formatted_total = format_price($ord['total_amount']);
            
            $status_class = "";
            switch($ord['status']) {
                case 'pending': $status_class = 'bg-amber-50 text-amber-700 border-amber-200'; break;
                case 'packaging': $status_class = 'bg-blue-50 text-blue-700 border-blue-200'; break;
                case 'out_for_delivery': $status_class = 'bg-purple-50 text-purple-700 border-purple-200'; break;
                case 'delivered': $status_class = 'bg-emerald-50 text-emerald-700 border-emerald-200'; break;
                case 'cancelled': $status_class = 'bg-rose-50 text-rose-700 border-rose-200'; break;
            }
            
            $orders_mobile_html .= '<div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">';
            $orders_mobile_html .= '  <div class="flex items-center justify-between text-xs">';
            $orders_mobile_html .= '    <span class="font-mono font-bold text-slate-800">' . $ref . '</span>';
            $orders_mobile_html .= '    <span class="px-2 py-0.5 rounded text-[8px] font-bold uppercase border ' . $status_class . '">' . $ord['status'] . '</span>';
            $orders_mobile_html .= '  </div>';
            $orders_mobile_html .= '  <div class="flex justify-between text-xs">';
            $orders_mobile_html .= '    <div>';
            $orders_mobile_html .= '      <strong class="block text-slate-808">' . $customer_name . '</strong>';
            $orders_mobile_html .= '      <span class="text-[10px] text-slate-400 font-mono">' . $customer_phone . '</span>';
            $orders_mobile_html .= '    </div>';
            $orders_mobile_html .= '    <strong class="text-emerald-650 font-bold">' . $formatted_total . '</strong>';
            $orders_mobile_html .= '  </div>';
            $orders_mobile_html .= '  <p class="text-[10px] text-slate-500 truncate" title="' . $customer_address . '">' . $customer_address . '</p>';
            $orders_mobile_html .= '</div>';
        }
    }

    echo json_encode([
        'success' => true,
        'latest_order_id' => $latest_order_id,
        'today_sales' => format_price($today_sales),
        'month_sales' => format_price($month_sales),
        'total_profit' => is_owner() ? format_price($total_profit) : null,
        'total_volume' => ($pos_count + $online_count) . " Total",
        'volume_subtitle' => "POS: " . $pos_count . " | Online: " . $online_count,
        'pos_pct' => $pos_pct,
        'online_pct' => $online_pct,
        'pos_share_val' => format_price($pos_share_val),
        'online_share_val' => format_price($online_share_val),
        'orders_html' => $orders_html,
        'orders_mobile_html' => $orders_mobile_html
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit();
