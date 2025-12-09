<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth-helpers.php';

$database = new Database();
$db = $database->getConnection();

// Get query parameters for filtering
$branch = $_GET['branch'] ?? null;
$status = $_GET['status'] ?? null;
$user_id = $_GET['user_id'] ?? null;
$order_type = $_GET['order_type'] ?? null;

$query = "SELECT o.*, 
          MAX(u.google_id) as google_id,
          GROUP_CONCAT(
              CONCAT(oi.product_name, ' (', oi.size, ') x', oi.quantity) 
              SEPARATOR ', '
          ) as items_summary
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id
          LEFT JOIN users u ON o.user_id = u.user_id";

$conditions = [];
$params = [];

if ($branch) {
    $conditions[] = "o.branch = :branch";
    $params[':branch'] = $branch;
}

if ($status) {
    $conditions[] = "o.order_status = :status";
    $params[':status'] = $status;
}

// Get customer email/username from query params for fallback matching
$customer_email = $_GET['customer_email'] ?? null;
$customer_username = $_GET['customer_username'] ?? null;

if ($user_id) {
    $userConditions = [];
    $userConditions[] = "o.user_id = :user_id";
    $params[':user_id'] = intval($user_id);
    
    // Also match by email/username as fallback if provided (for cases where user_id might be NULL)
    if ($customer_email) {
        $userConditions[] = "(o.customer_email = :customer_email)";
        $params[':customer_email'] = $customer_email;
    }
    if ($customer_username) {
        $userConditions[] = "(o.customer_username = :customer_username)";
        $params[':customer_username'] = $customer_username;
    }
    
    // Use OR logic for user matching (match by user_id OR email OR username)
    if (count($userConditions) > 1) {
        $conditions[] = "(" . implode(" OR ", $userConditions) . ")";
    } else {
        $conditions[] = $userConditions[0];
    }
} else if ($customer_email || $customer_username) {
    // If no user_id but email/username provided, match by those
    $userConditions = [];
    if ($customer_email) {
        $userConditions[] = "o.customer_email = :customer_email";
        $params[':customer_email'] = $customer_email;
    }
    if ($customer_username) {
        $userConditions[] = "o.customer_username = :customer_username";
        $params[':customer_username'] = $customer_username;
    }
    if (count($userConditions) > 1) {
        $conditions[] = "(" . implode(" OR ", $userConditions) . ")";
    } else {
        $conditions[] = $userConditions[0];
    }
}

if ($order_type) {
    $conditions[] = "o.order_type = :order_type";
    $params[':order_type'] = $order_type;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$orders = $stmt->fetchAll();

// Get order items for each order
foreach ($orders as &$order) {
    $itemsQuery = "SELECT * FROM order_items WHERE order_id = :order_id";
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->bindParam(':order_id', $order['id']);
    $itemsStmt->execute();
    $order['items'] = $itemsStmt->fetchAll();
}

sendResponse(true, 'Orders retrieved successfully', $orders);
?>

