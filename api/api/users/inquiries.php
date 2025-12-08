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
require_once __DIR__ . '/../../utils/auth-helpers.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create inquiry
    $data = json_decode(file_get_contents('php://input'), true);
    
    $user = getAuthUser();
    $userId = $user ? $user['id'] : null;
    
    if (!isset($data['name']) || !isset($data['email']) || !isset($data['message'])) {
        sendError('Name, email, and message are required');
    }
    
    $name = trim($data['name']);
    $email = trim($data['email']);
    $phone = isset($data['phone']) ? trim($data['phone']) : null;
    $eventType = isset($data['event_type']) ? trim($data['event_type']) : null;
    $eventDate = isset($data['event_date']) ? trim($data['event_date']) : null;
    $guestCount = isset($data['guest_count']) ? intval($data['guest_count']) : null;
    $location = isset($data['location']) ? trim($data['location']) : null;
    $message = trim($data['message']);
    
    if (!validateEmail($email)) {
        sendError('Invalid email format');
    }
    
    $query = "INSERT INTO event_inquiries (user_id, name, email, phone, event_type, event_date, guest_count, location, message) 
              VALUES (:user_id, :name, :email, :phone, :event_type, :event_date, :guest_count, :location, :message)";
    $stmt = $db->prepare($query);
    
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':event_type', $eventType);
    $stmt->bindParam(':event_date', $eventDate);
    $stmt->bindValue(':guest_count', $guestCount, PDO::PARAM_INT);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':message', $message);
    
    if ($stmt->execute()) {
        $inquiryId = $db->lastInsertId();
        sendResponse(true, 'Inquiry submitted successfully', ['id' => $inquiryId]);
    } else {
        sendError('Failed to submit inquiry');
    }
} else {
    // Get inquiries (admin only)
    $user = requireAuth('admin');
    
    $query = "SELECT ei.*, u.name as user_name, u.email as user_email 
              FROM event_inquiries ei
              LEFT JOIN users u ON ei.user_id = u.id
              ORDER BY ei.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $inquiries = $stmt->fetchAll();
    sendResponse(true, 'Inquiries retrieved successfully', $inquiries);
}
?>

