<?php
/**
 * Sales Report API
 * Fetches order data from the database for the admin dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../db_connect.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$action = $_GET['action'] ?? 'all';

try {
    switch ($action) {
        case 'daily':
            $date = $_GET['date'] ?? date('Y-m-d');
            $stmt = $conn->prepare("
                SELECT o.*, b.branch_name, b.branch_code,
                       GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.size, ' x', oi.quantity, ')') SEPARATOR ', ') as items_list,
                       SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN branches b ON o.branch_id = b.branch_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE DATE(o.order_date) = ? AND o.status = 'completed'
                GROUP BY o.order_id
                ORDER BY o.order_date DESC
            ");
            $stmt->bind_param("s", $date);
            break;

        case 'weekly':
            $stmt = $conn->prepare("
                SELECT o.*, b.branch_name, b.branch_code,
                       GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.size, ' x', oi.quantity, ')') SEPARATOR ', ') as items_list,
                       SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN branches b ON o.branch_id = b.branch_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE YEARWEEK(o.order_date, 1) = YEARWEEK(CURDATE(), 1) AND o.status = 'completed'
                GROUP BY o.order_id
                ORDER BY o.order_date DESC
            ");
            break;

        case 'monthly':
            $stmt = $conn->prepare("
                SELECT o.*, b.branch_name, b.branch_code,
                       GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.size, ' x', oi.quantity, ')') SEPARATOR ', ') as items_list,
                       SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN branches b ON o.branch_id = b.branch_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE YEAR(o.order_date) = YEAR(CURDATE()) AND MONTH(o.order_date) = MONTH(CURDATE()) AND o.status = 'completed'
                GROUP BY o.order_id
                ORDER BY o.order_date DESC
            ");
            break;

        case 'walkin':
            $stmt = $conn->prepare("
                SELECT o.*, b.branch_name, b.branch_code,
                       GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.size, ' x', oi.quantity, ')') SEPARATOR ', ') as items_list,
                       SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN branches b ON o.branch_id = b.branch_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.order_type = 'dine-in' AND o.status = 'completed'
                GROUP BY o.order_id
                ORDER BY o.order_date DESC
                LIMIT 100
            ");
            break;

        case 'online':
            $stmt = $conn->prepare("
                SELECT o.*, b.branch_name, b.branch_code, u.firstname, u.lastname, u.email as customer_email,
                       GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.size, ' x', oi.quantity, ')') SEPARATOR ', ') as items_list,
                       SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN branches b ON o.branch_id = b.branch_id
                LEFT JOIN users u ON o.user_id = u.user_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.order_type IN ('takeout', 'delivery') AND o.status = 'completed'
                GROUP BY o.order_id
                ORDER BY o.order_date DESC
                LIMIT 100
            ");
            break;

        case 'best_sellers':
            $stmt = $conn->prepare("
                SELECT oi.product_name, SUM(oi.quantity) as total_sold, SUM(oi.total_price) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE o.status = 'completed'
                GROUP BY oi.product_name
                ORDER BY total_sold DESC
                LIMIT 10
            ");
            break;

        case 'today_stats':
            // Get today's order counts by branch
            $result = [];
            
            // SM Fairview count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
                FROM orders o
                JOIN branches b ON o.branch_id = b.branch_id
                WHERE DATE(o.order_date) = CURDATE() AND b.branch_code = 'fairview'
            ");
            $stmt->execute();
            $fairview = $stmt->get_result()->fetch_assoc();
            
            // SM SJDM count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
                FROM orders o
                JOIN branches b ON o.branch_id = b.branch_id
                WHERE DATE(o.order_date) = CURDATE() AND b.branch_code = 'sjdm'
            ");
            $stmt->execute();
            $sjdm = $stmt->get_result()->fetch_assoc();
            
            // Total today
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
                FROM orders
                WHERE DATE(order_date) = CURDATE()
            ");
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'fairview' => $fairview,
                    'sjdm' => $sjdm,
                    'total' => $total
                ]
            ]);
            exit;

        default: // 'all'
            $stmt = $conn->prepare("
                SELECT o.*, b.branch_name, b.branch_code,
                       GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.size, ' x', oi.quantity, ')') SEPARATOR ', ') as items_list,
                       SUM(oi.quantity) as total_items
                FROM orders o
                LEFT JOIN branches b ON o.branch_id = b.branch_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.status = 'completed'
                GROUP BY o.order_id
                ORDER BY o.order_date DESC
                LIMIT 100
            ");
            break;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Calculate totals for branch-specific reports
    $totals = [
        'fairview' => 0,
        'sjdm' => 0,
        'all' => 0
    ];
    
    foreach ($orders as $order) {
        $amount = floatval($order['total_amount'] ?? 0);
        $totals['all'] += $amount;
        
        $branchCode = strtolower($order['branch_code'] ?? '');
        if (strpos($branchCode, 'fairview') !== false) {
            $totals['fairview'] += $amount;
        } elseif (strpos($branchCode, 'sjdm') !== false) {
            $totals['sjdm'] += $amount;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $orders,
        'totals' => $totals,
        'count' => count($orders)
    ]);
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

