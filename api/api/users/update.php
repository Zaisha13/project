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

// Read input first before requireAuth (in case token is in body)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Ensure we have a token for authentication
// Priority: 1) Check if Authorization header exists (trim it), 2) Check body _token, 3) Set it
$token = null;

// First, try to get token from Authorization header
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = trim($_SERVER['HTTP_AUTHORIZATION']);
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $token = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
} else {
    // Try getallheaders() as fallback
    $headers = getallheaders();
    if ($headers) {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $token = trim($value);
                break;
            }
        }
    }
}

// If no token in headers, try to get from request body
if (!$token && isset($data['_token'])) {
    $token = trim($data['_token']);
}

// Set the token in $_SERVER for requireAuth() to find it
if ($token && !empty(trim($token))) {
    // Remove "Bearer " prefix if present
    if (stripos($token, 'Bearer ') === 0) {
        $token = trim(substr($token, 7));
    }
    // Final trim
    $token = trim($token);
    if (!empty($token)) {
        $_SERVER['HTTP_AUTHORIZATION'] = $token;
    }
    // Remove _token from data so it doesn't interfere with update
    if (isset($data['_token'])) {
        unset($data['_token']);
    }
}

$user = requireAuth();

// Data is already parsed above, no need to read php://input again

$name = isset($data['name']) ? trim($data['name']) : null;
$phone = isset($data['phone']) ? trim($data['phone']) : null;
$address = isset($data['address']) ? trim($data['address']) : null;
$password = $data['password'] ?? null;

$database = new Database();
$db = $database->getConnection();

$updateFields = [];
// Use user_id if id doesn't exist (for database compatibility)
$userId = $user['id'] ?? $user['user_id'] ?? null;
if (!$userId) {
    sendError('User ID not found', 400);
}
$params = [':id' => $userId];

if ($name !== null) {
    $updateFields[] = "name = :name";
    $params[':name'] = $name;
}

if ($phone !== null) {
    $updateFields[] = "phone = :phone";
    $params[':phone'] = $phone;
}

if ($address !== null) {
    $updateFields[] = "address = :address";
    $params[':address'] = $address;
}

if ($password !== null && strlen($password) >= 6) {
    $updateFields[] = "password = :password";
    $params[':password'] = hashPassword($password);
}

if (empty($updateFields)) {
    sendError('No fields to update');
}

// Use user_id if available, otherwise use id
$whereField = isset($user['user_id']) ? 'user_id' : 'id';
$query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE $whereField = :id";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

if ($stmt->execute()) {
    $query = "SELECT id, user_id, name, username, email, role, phone, address FROM users WHERE $whereField = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $updatedUser = $stmt->fetch();
    
    sendResponse(true, 'Profile updated successfully', $updatedUser);
} else {
    sendError('Failed to update profile');
}
?>

