<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$data = $_SERVER['REQUEST_METHOD'] === 'POST' 
    ? json_decode(file_get_contents('php://input'), true) 
    : $_GET;

$orderId = $data['order_id'] ?? null;
$paymentIntentId = $data['payment_intent_id'] ?? null;
$paymentLinkId = $data['payment_link_id'] ?? null;

if (!$orderId && !$paymentIntentId && !$paymentLinkId) {
    sendError('Order ID, Payment Intent ID, or Payment Link ID is required');
}

$database = new Database();
$db = $database->getConnection();

// First, get payment record from database
$payment = null;
if ($paymentIntentId) {
    $query = "SELECT p.*, o.order_id as order_ref, o.payment_status, o.id as order_db_id 
              FROM payments p 
              JOIN orders o ON p.order_id = o.id 
              WHERE p.paymongo_intent_id = :intent_id
              ORDER BY p.id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':intent_id', $paymentIntentId);
} elseif ($paymentLinkId) {
    $query = "SELECT p.*, o.order_id as order_ref, o.payment_status, o.id as order_db_id 
              FROM payments p 
              JOIN orders o ON p.order_id = o.id 
              WHERE p.paymongo_link_id = :link_id
              ORDER BY p.id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':link_id', $paymentLinkId);
} else {
    $query = "SELECT p.*, o.order_id as order_ref, o.payment_status, o.id as order_db_id 
              FROM payments p 
              JOIN orders o ON p.order_id = o.id 
              WHERE o.order_id = :order_id 
              ORDER BY p.id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $orderId);
}

$stmt->execute();
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    sendError('Payment not found', 404);
}

// If payment is already marked as Paid, return current status
if ($payment['status'] === 'Paid' && $payment['payment_status'] === 'Paid') {
    sendResponse(true, 'Payment already verified', [
        'status' => 'Paid',
        'payment_status' => 'Paid',
        'order_id' => $payment['order_ref']
    ]);
}

// Verify payment status with PayMongo
$paymongoIntentId = $payment['paymongo_intent_id'];
if (!$paymongoIntentId) {
    sendError('PayMongo payment intent ID not found');
}

// Get payment intent status from PayMongo
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYMONGO_API_URL . '/payment_intents/' . $paymongoIntentId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, PAYMONGO_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    sendError('Failed to verify payment with PayMongo', 500);
}

$paymentIntentData = json_decode($response, true);
$paymentStatus = $paymentIntentData['data']['attributes']['status'] ?? '';
$paymentMethod = $paymentIntentData['data']['attributes']['payment_method'] ?? null;
$payments = $paymentIntentData['data']['attributes']['payments'] ?? [];

// Check if payment is successful
$isPaid = false;
if ($paymentStatus === 'succeeded' || $paymentStatus === 'awaiting_payment_method') {
    // Check if any payment is successful
    foreach ($payments as $pmt) {
        if (isset($pmt['attributes']['status']) && $pmt['attributes']['status'] === 'paid') {
            $isPaid = true;
            break;
        }
    }
}

// Update payment status if payment is paid
if ($isPaid || $paymentStatus === 'succeeded') {
    try {
        $db->beginTransaction();
        
        // Update payment status
        $updatePaymentQuery = "UPDATE payments SET status = 'Paid', updated_at = NOW() WHERE paymongo_intent_id = :intent_id";
        $updatePaymentStmt = $db->prepare($updatePaymentQuery);
        $updatePaymentStmt->bindParam(':intent_id', $paymongoIntentId);
        $updatePaymentStmt->execute();
        
        // Update order payment status to 'Paid' for all online GCash orders
        $updateOrderQuery = "UPDATE orders SET payment_status = 'Paid', order_status = 'Approved' WHERE id = :order_id";
        $updateOrderStmt = $db->prepare($updateOrderQuery);
        $updateOrderStmt->bindParam(':order_id', $payment['order_db_id']);
        $updateOrderStmt->execute();
        
        $db->commit();
        
        sendResponse(true, 'Payment verified and updated', [
            'status' => 'Paid',
            'payment_status' => 'Paid',
            'order_id' => $payment['order_ref'],
            'payment_intent_status' => $paymentStatus
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Failed to update payment status: ' . $e->getMessage(), 500);
    }
} else {
    // Payment is not yet paid
    sendResponse(true, 'Payment status retrieved', [
        'status' => $payment['status'],
        'payment_status' => $payment['payment_status'],
        'order_id' => $payment['order_ref'],
        'payment_intent_status' => $paymentStatus
    ]);
}
?>

