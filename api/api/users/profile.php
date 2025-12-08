<?php
// Suppress any output that might interfere with JSON response
ob_start();

// Set headers first to ensure JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../utils/response.php';
    require_once __DIR__ . '/../../utils/auth-helpers.php';
    
    // Clear any output buffer
    ob_clean();
    
    $user = getAuthUser();
    
    // Check if user_id is provided in query string (for Google auth callback)
    $requestedUserId = $_GET['user_id'] ?? $_GET['id'] ?? null;
    
    if (!$user && $requestedUserId) {
        // Allow getting profile by user_id when provided (for Google auth callback)
        $database = new Database();
        $db = $database->getConnection();
        
        // Don't select 'name' column - it doesn't exist, use firstname and lastname
        $query = "SELECT user_id, firstname, lastname, username, email, role, phone, address, date_created as created_at FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $requestedUserId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            sendError('User not found', 404);
        }
        
        // Ensure id field exists for compatibility
        if (!isset($user['id']) && isset($user['user_id'])) {
            $user['id'] = $user['user_id'];
        }
        // Create name field from firstname and lastname
        $user['name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        
        sendResponse(true, 'User profile retrieved successfully', $user);
    } else if (!$user) {
        // Allow getting profile by ID for admin users
        $id = $_GET['id'] ?? null;
        $currentUser = getAuthUser();
        
        if ($id && $currentUser && $currentUser['role'] === 'admin') {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT user_id, firstname, lastname, username, email, role, phone, address, date_created as created_at FROM users WHERE user_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                sendError('User not found', 404);
            }
            
            // Ensure id field exists
            if (!isset($user['id']) && isset($user['user_id'])) {
                $user['id'] = $user['user_id'];
            }
            // Create name field from firstname and lastname
            $user['name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
            
            sendResponse(true, 'User profile retrieved successfully', $user);
        } else {
            sendError('Authentication required', 401);
        }
    } else {
        // Ensure id field exists for consistency
        if (!isset($user['id']) && isset($user['user_id'])) {
            $user['id'] = $user['user_id'];
        }
        // Create name field from firstname and lastname if not exists
        if (!isset($user['name'])) {
            $user['name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        }
        sendResponse(true, 'Profile retrieved successfully', $user);
    }
} catch (Exception $e) {
    ob_clean();
    sendError('An error occurred: ' . $e->getMessage(), 500);
} catch (Error $e) {
    ob_clean();
    sendError('An error occurred: ' . $e->getMessage(), 500);
}
?>

