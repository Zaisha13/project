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

$id = $_GET['id'] ?? null;
$order_id = $_GET['order_id'] ?? null; // Order ID string (e.g., ORD-20240101-0001)

if (!$id && !$order_id) {
    sendError('Order ID is required');
}

$database = new Database();
$db = $database->getConnection();

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

// Get order items
$itemsQuery = "SELECT * FROM order_items WHERE order_id = :order_id";
$itemsStmt = $db->prepare($itemsQuery);
$itemsStmt->bindParam(':order_id', $order['id']);
$itemsStmt->execute();
$order['items'] = $itemsStmt->fetchAll();

sendResponse(true, 'Order retrieved successfully', $order);
?>

