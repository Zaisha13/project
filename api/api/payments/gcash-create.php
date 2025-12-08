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

// Xendit Configuration
// TODO: Replace with your actual Xendit API key
define('XENDIT_API_URL', 'https://api.xendit.co/v2/invoices');
define('XENDIT_SECRET_KEY', 'YOUR_XENDIT_SECRET_KEY_HERE');
define('BASE_URL', 'http://localhost:8080/project');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['amount'])) {
    sendError('Order ID and amount are required');
}

$orderDbId = intval($data['order_id']);
$amount = floatval($data['amount']);

$database = new Database();
$db = $database->getConnection();

// Get order details
$query = "SELECT * FROM orders WHERE id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $orderDbId);
$stmt->execute();
$order = $stmt->fetch();

if (!$order) {
    sendError('Order not found');
}

// Prepare Xendit payment request
$paymentData = [
    'external_id' => $order['order_id'],
    'amount' => $amount,
    'description' => 'Jessie Cane Juice Bar - Order ' . $order['order_id'],
    'invoice_duration' => 3600, // 1 hour
    'success_redirect_url' => BASE_URL . '/public/customer/thank-you.php?order=' . $order['order_id'],
    'failure_redirect_url' => BASE_URL . '/public/customer/drinks.php?payment=failed',
    'customer' => [
        'given_names' => $order['customer_name'],
        'email' => $order['customer_email'] ?? '',
        'mobile_number' => $order['customer_phone'] ?? ''
    ],
    'payment_methods' => ['GCASH'],
    'currency' => 'PHP'
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, XENDIT_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
curl_setopt($ch, CURLOPT_USERPWD, XENDIT_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    sendError('Payment gateway error: ' . $curlError, 500);
}

if ($httpCode === 200 || $httpCode === 201) {
    $paymentResponse = json_decode($response, true);
    
    // Save payment record
    $query = "INSERT INTO payments (order_id, xendit_id, xendit_reference, amount, status, metadata) 
              VALUES (:order_id, :xendit_id, :reference, :amount, 'Pending', :metadata)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $orderDbId);
    $stmt->bindParam(':xendit_id', $paymentResponse['id']);
    $stmt->bindParam(':reference', $order['order_id']);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':metadata', json_encode($paymentResponse));
    $stmt->execute();
    
    sendResponse(true, 'Xendit GCash payment link created', [
        'payment_url' => $paymentResponse['invoice_url'],
        'invoice_id' => $paymentResponse['id'],
        'reference' => $order['order_id']
    ]);
} else {
    $errorResponse = json_decode($response, true);
    sendError('Failed to create payment: ' . ($errorResponse['message'] ?? 'Unknown error'), 500);
}
?>

