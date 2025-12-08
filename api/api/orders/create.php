<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['items']) || !isset($data['customer_name']) || !isset($data['branch'])) {
    sendError('Items, customer name, and branch are required');
}

$userId = isset($data['user_id']) && $data['user_id'] ? intval($data['user_id']) : null;
$customerName = trim($data['customer_name']);
$customerUsername = isset($data['customer_username']) ? trim($data['customer_username']) : '';
$customerEmail = isset($data['customer_email']) ? trim($data['customer_email']) : '';
$customerPhone = isset($data['customer_phone']) ? trim($data['customer_phone']) : '';
$customerNotes = isset($data['customer_notes']) ? trim($data['customer_notes']) : '';
$branch = trim($data['branch']);
$items = $data['items'];
$subtotal = floatval($data['subtotal']);
$tax = isset($data['tax']) ? floatval($data['tax']) : 0;
$total = floatval($data['total']);
$paymentMethod = isset($data['payment_method']) ? trim($data['payment_method']) : 'GCash';
$orderType = isset($data['order_type']) ? trim($data['order_type']) : 'Digital';
$isGuest = !$userId || $userId === 0 ? 1 : 0;

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Generate unique order ID
    $orderId = generateOrderId();
    $timestamp = time();
    $orderDate = date('m/d/Y');
    $orderTime = date('g:i A');
    
    // Insert order
    $query = "INSERT INTO orders (order_id, user_id, customer_name, customer_username, customer_email, customer_phone, customer_notes, branch, subtotal, tax, total, order_type, is_guest, timestamp, order_date, order_time, payment_method, payment_status, order_status) 
              VALUES (:order_id, :user_id, :customer_name, :customer_username, :customer_email, :customer_phone, :customer_notes, :branch, :subtotal, :tax, :total, :order_type, :is_guest, :timestamp, :order_date, :order_time, :payment_method, 'Pending', 'Pending')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $orderId);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':customer_name', $customerName);
    $stmt->bindParam(':customer_username', $customerUsername);
    $stmt->bindParam(':customer_email', $customerEmail);
    $stmt->bindParam(':customer_phone', $customerPhone);
    $stmt->bindParam(':customer_notes', $customerNotes);
    $stmt->bindParam(':branch', $branch);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->bindParam(':tax', $tax);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':order_type', $orderType);
    $stmt->bindParam(':is_guest', $isGuest);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->bindParam(':order_date', $orderDate);
    $stmt->bindParam(':order_time', $orderTime);
    $stmt->bindParam(':payment_method', $paymentMethod);
    
    $stmt->execute();
    
    $orderDbId = $db->lastInsertId();
    
    // Insert order items
    foreach ($items as $item) {
        $productName = $item['name'] ?? $item['product_name'] ?? '';
        $size = $item['size'] ?? 'Regular';
        $special = isset($item['special']) ? trim($item['special']) : null;
        $notes = isset($item['notes']) ? trim($item['notes']) : null;
        $quantity = isset($item['qty']) ? intval($item['qty']) : (isset($item['quantity']) ? intval($item['quantity']) : 1);
        $price = floatval($item['price']);
        
        $itemQuery = "INSERT INTO order_items (order_id, product_name, size, special, notes, quantity, price) 
                      VALUES (:order_id, :name, :size, :special, :notes, :qty, :price)";
        $itemStmt = $db->prepare($itemQuery);
        $itemStmt->bindParam(':order_id', $orderDbId);
        $itemStmt->bindParam(':name', $productName);
        $itemStmt->bindParam(':size', $size);
        $itemStmt->bindParam(':special', $special);
        $itemStmt->bindParam(':notes', $notes);
        $itemStmt->bindParam(':qty', $quantity);
        $itemStmt->bindParam(':price', $price);
        $itemStmt->execute();
    }
    
    $db->commit();
    
    sendResponse(true, 'Order created successfully', [
        'order_id' => $orderId,
        'order_db_id' => $orderDbId,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    sendError('Failed to create order: ' . $e->getMessage());
}
?>

