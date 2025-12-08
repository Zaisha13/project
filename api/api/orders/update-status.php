<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth-helpers.php';

// Require authentication (admin or cashier)
$user = getAuthUser();
if (!$user || !in_array($user['role'], ['admin', 'cashier'])) {
    sendError('Authentication required', 401);
}

$data = json_decode(file_get_contents('php://input'), true);

$id = $_GET['id'] ?? $data['id'] ?? null;
$order_id = $_GET['order_id'] ?? $data['order_id'] ?? null; // Order ID string (e.g., ORD-20240101-0001)
$order_status = $data['order_status'] ?? null;
$payment_status = $data['payment_status'] ?? null;

if (!$id && !$order_id) {
    sendError('Order ID is required');
}

$database = new Database();
$db = $database->getConnection();

// Check if order exists - support both numeric ID and order_id string
if ($id) {
    $query = "SELECT * FROM orders WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
} else {
    $query = "SELECT * FROM orders WHERE order_id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
}
$stmt->execute();
$order = $stmt->fetch();

if (!$order) {
    sendError('Order not found', 404);
}

// Build update query - use the actual database ID for the update
$updateFields = [];
$dbId = $order['id']; // Use the numeric ID from the database
$params = [':id' => $dbId];

if ($order_status) {
    $validStatuses = ['Pending', 'Approved', 'Processing', 'Ready', 'Out for Delivery', 'Completed', 'Cancelled'];
    if (in_array($order_status, $validStatuses)) {
        $updateFields[] = "order_status = :order_status";
        $params[':order_status'] = $order_status;
    }
}

if ($payment_status) {
    $validPaymentStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];
    if (in_array($payment_status, $validPaymentStatuses)) {
        $updateFields[] = "payment_status = :payment_status";
        $params[':payment_status'] = $payment_status;
    }
}

if (empty($updateFields)) {
    sendError('No valid status provided');
}

$query = "UPDATE orders SET " . implode(', ', $updateFields) . " WHERE id = :id";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

if ($stmt->execute()) {
    // Get updated order
    $query = "SELECT * FROM orders WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $updatedOrder = $stmt->fetch();
    
    sendResponse(true, 'Order status updated successfully', $updatedOrder);
} else {
    sendError('Failed to update order status');
}
?>

