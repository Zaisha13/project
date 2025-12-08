<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../utils/response.php';

// In a real implementation, you might want to invalidate the token on the server
// For now, we'll just return success and let the frontend clear the token

sendResponse(true, 'Logout successful');
?>

