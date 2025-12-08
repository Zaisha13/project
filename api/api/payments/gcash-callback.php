<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

define('XENDIT_WEBHOOK_TOKEN', 'YOUR_WEBHOOK_TOKEN_HERE');

// Get webhook data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verify webhook token
$webhookToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
if ($webhookToken !== XENDIT_WEBHOOK_TOKEN) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle different event types
$event = $data['event'] ?? '';

if ($event === 'invoice.paid') {
    $invoiceId = $data['data']['id'] ?? '';
    $status = 'Paid';
    
    try {
        // Get payment record
        $query = "SELECT * FROM payments WHERE xendit_id = :xendit_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':xendit_id', $invoiceId);
        $stmt->execute();
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            $db->beginTransaction();
            
            // Update payment status
            $updateQuery = "UPDATE payments SET status = 'Paid', updated_at = NOW() WHERE xendit_id = :xendit_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':xendit_id', $invoiceId);
            $updateStmt->execute();
            
            // Update order payment status to 'Paid' for all online GCash orders
            $orderQuery = "UPDATE orders SET payment_status = 'Paid', order_status = 'Approved' WHERE id = :order_id";
            $orderStmt = $db->prepare($orderQuery);
            $orderStmt->bindParam(':order_id', $payment['order_id']);
            $orderStmt->execute();
            
            $db->commit();
            
            error_log("GCash payment updated: Invoice {$invoiceId}, Order {$payment['order_id']}");
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error updating GCash payment: " . $e->getMessage());
    }
} elseif ($event === 'invoice.expired' || $event === 'invoice.failed') {
    $invoiceId = $data['data']['id'] ?? '';
    $status = 'Failed';
    
    $query = "UPDATE payments SET status = 'Failed' WHERE xendit_id = :xendit_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':xendit_id', $invoiceId);
    $stmt->execute();
}

http_response_code(200);
echo json_encode(['success' => true]);
?>

