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
require_once __DIR__ . '/../../config.php';

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
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    sendError('Order not found');
}

// Build success and failure URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/project';
$successUrl = $baseUrl . '/public/customer/thank-you.php?order=' . urlencode($order['order_id']);
$cancelUrl = $baseUrl . '/public/customer/drinks.php?payment=cancelled';

// Convert amount to cents (PayMongo uses smallest currency unit)
$amountInCents = intval($amount * 100);

// Step 1: Create Payment Intent
$paymentIntentData = [
    'data' => [
        'attributes' => [
            'amount' => $amountInCents,
            'payment_method_allowed' => [
                'paymaya',
                'gcash',
                'grab_pay',
                'card'
            ],
            'currency' => 'PHP',
            'description' => 'Jessie Cane Juice Bar - Order ' . $order['order_id'],
            'statement_descriptor' => 'JESSIE CANE',
            'metadata' => [
                'order_id' => $order['order_id'],
                'order_db_id' => $orderDbId,
                'customer_name' => $order['customer_name'],
                'branch' => $order['branch']
            ]
        ]
    ]
];

// Create Payment Intent
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYMONGO_API_URL . '/payment_intents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentIntentData));
curl_setopt($ch, CURLOPT_USERPWD, PAYMONGO_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    $errorResponse = json_decode($response, true);
    $errorMsg = $errorResponse['errors'][0]['detail'] ?? 'Unknown error';
    sendError('Failed to create payment intent: ' . $errorMsg, 500);
}

$paymentIntentResponse = json_decode($response, true);

if (!isset($paymentIntentResponse['data']['id'])) {
    sendError('Invalid response from PayMongo', 500);
}

$paymentIntentId = $paymentIntentResponse['data']['id'];
$clientKey = $paymentIntentResponse['data']['attributes']['client_key'];

// Step 2: Create Payment Link (for QR code and redirect)
$paymentLinkData = [
    'data' => [
        'attributes' => [
            'amount' => $amountInCents,
            'currency' => 'PHP',
            'description' => 'Jessie Cane Juice Bar - Order ' . $order['order_id'],
            'remarks' => 'Order ' . $order['order_id'],
            'metadata' => [
                'order_id' => $order['order_id'],
                'order_db_id' => $orderDbId,
                'customer_name' => $order['customer_name'],
                'branch' => $order['branch']
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYMONGO_API_URL . '/links');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentLinkData));
curl_setopt($ch, CURLOPT_USERPWD, PAYMONGO_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$linkResponse = curl_exec($ch);
$linkHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$linkCurlError = curl_error($ch);
curl_close($ch);

if ($linkHttpCode === 200 || $linkHttpCode === 201) {
    $linkData = json_decode($linkResponse, true);
    $paymentLinkId = $linkData['data']['id'] ?? null;
    $checkoutUrl = $linkData['data']['attributes']['checkout_url'] ?? null;
    
    if (!$checkoutUrl) {
        error_log('PayMongo: Payment link created but no checkout_url in response: ' . json_encode($linkData));
        sendError('Payment link created but checkout URL is missing', 500);
    }
} else {
    $linkErrorData = json_decode($linkResponse, true);
    $linkErrorMsg = $linkErrorData['errors'][0]['detail'] ?? ($linkCurlError ?: 'Unknown error');
    error_log('PayMongo: Failed to create payment link. HTTP Code: ' . $linkHttpCode . ', Error: ' . $linkErrorMsg);
    sendError('Failed to create payment link: ' . $linkErrorMsg, $linkHttpCode);
}

// Save payment record to database
try {
    // Check if payments table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'payments'");
    if ($checkTable && $checkTable->rowCount() > 0) {
        $query = "INSERT INTO payments (order_id, paymongo_intent_id, paymongo_link_id, amount, status, metadata, created_at) 
                  VALUES (:order_id, :intent_id, :link_id, :amount, 'Pending', :metadata, NOW())
                  ON DUPLICATE KEY UPDATE 
                  paymongo_intent_id = VALUES(paymongo_intent_id),
                  paymongo_link_id = VALUES(paymongo_link_id),
                  metadata = VALUES(metadata)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $orderDbId);
        $stmt->bindParam(':intent_id', $paymentIntentId);
        $stmt->bindParam(':link_id', $paymentLinkId);
        $stmt->bindParam(':amount', $amount);
        $metadata = json_encode([
            'payment_intent' => $paymentIntentResponse,
            'payment_link' => $linkData ?? null
        ]);
        $stmt->bindParam(':metadata', $metadata);
        $stmt->execute();
    }
} catch (Exception $e) {
    // Table might not exist, continue anyway
    error_log('Payments table not found or error: ' . $e->getMessage());
}

// Return payment information
sendResponse(true, 'PayMongo payment created successfully', [
    'payment_intent_id' => $paymentIntentId,
    'client_key' => $clientKey,
    'checkout_url' => $checkoutUrl,
    'payment_url' => $checkoutUrl, // Alias for compatibility
    'public_key' => PAYMONGO_PUBLIC_KEY,
    'amount' => $amount,
    'order_id' => $order['order_id'],
    'payment_methods' => ['gcash', 'paymaya', 'grab_pay', 'card']
]);
?>

