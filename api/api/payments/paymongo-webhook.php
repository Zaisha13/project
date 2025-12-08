<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get raw request body
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Verify webhook signature (PayMongo sends signature in headers)
$signature = $headers['Paymongo-Signature'] ?? '';

// For production, verify the signature
// For now, we'll log and process (you should implement signature verification)
if (!empty(PAYMONGO_WEBHOOK_SECRET) && $signature) {
    // TODO: Implement signature verification
    // $expectedSignature = hash_hmac('sha256', $payload, PAYMONGO_WEBHOOK_SECRET);
    // if (!hash_equals($expectedSignature, $signature)) {
    //     http_response_code(401);
    //     echo json_encode(['error' => 'Invalid signature']);
    //     exit;
    // }
}

$data = json_decode($payload, true);

if (!$data || !isset($data['data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$event = $data['data'];
$eventType = $event['type'] ?? '';
$attributes = $event['attributes'] ?? [];

// Log webhook for debugging
error_log('PayMongo Webhook: ' . $eventType . ' - ' . json_encode($data));

$database = new Database();
$db = $database->getConnection();

try {
    // Handle different event types
    switch ($eventType) {
        case 'payment_intent.succeeded':
        case 'payment.paid':
            // Payment was successful
            $paymentIntentId = $event['id'] ?? null;
            $metadata = $attributes['metadata'] ?? [];
            $orderId = $metadata['order_id'] ?? null;
            $orderDbId = $metadata['order_db_id'] ?? null;
            
            // If metadata doesn't have order_db_id, try to find it from payment_intent_id
            if (!$orderDbId && $paymentIntentId) {
                $query = "SELECT order_id FROM payments WHERE paymongo_intent_id = :intent_id LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':intent_id', $paymentIntentId);
                $stmt->execute();
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($payment) {
                    $orderDbId = $payment['order_id'];
                }
            }
            
            if ($orderDbId) {
                $db->beginTransaction();
                
                // Update order payment status to 'Paid' for all online GCash orders
                $query = "UPDATE orders SET payment_status = 'Paid', order_status = 'Approved' WHERE id = :order_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':order_id', $orderDbId);
                $stmt->execute();
                
                // Update payments table if it exists
                try {
                    $checkTable = $db->query("SHOW TABLES LIKE 'payments'");
                    if ($checkTable && $checkTable->rowCount() > 0) {
                        $query = "UPDATE payments SET status = 'Paid', updated_at = NOW() WHERE order_id = :order_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':order_id', $orderDbId);
                        $stmt->execute();
                    }
                } catch (Exception $e) {
                    error_log('Error updating payments table: ' . $e->getMessage());
                }
                
                $db->commit();
                error_log("PayMongo payment updated: Intent {$paymentIntentId}, Order {$orderDbId}");
            }
            break;
            
        case 'payment_intent.payment_failed':
        case 'payment.failed':
            // Payment failed
            $metadata = $attributes['metadata'] ?? [];
            $orderDbId = $metadata['order_db_id'] ?? null;
            
            if ($orderDbId) {
                $query = "UPDATE orders SET payment_status = 'Failed' WHERE id = :order_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':order_id', $orderDbId);
                $stmt->execute();
            }
            break;
            
        case 'payment_intent.awaiting_payment_method':
            // Payment is awaiting payment method
            $metadata = $attributes['metadata'] ?? [];
            $orderDbId = $metadata['order_db_id'] ?? null;
            
            if ($orderDbId) {
                $query = "UPDATE orders SET payment_status = 'Pending' WHERE id = :order_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':order_id', $orderDbId);
                $stmt->execute();
            }
            break;
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processed']);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('PayMongo Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>

