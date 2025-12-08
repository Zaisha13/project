<?php
// Set headers first to ensure JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Suppress any output that might interfere with JSON response
ob_start();

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../utils/response.php';
    require_once __DIR__ . '/../../utils/auth-helpers.php';
    require_once __DIR__ . '/../../config.php';
    
    // Clear any output buffer
    ob_clean();
    
    // Check if Google OAuth credentials are configured
    if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID' || empty(GOOGLE_CLIENT_ID)) {
        sendError('Google OAuth is not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in api/config.php', 500);
    }
    
    if (!defined('GOOGLE_CLIENT_SECRET') || GOOGLE_CLIENT_SECRET === 'YOUR_GOOGLE_CLIENT_SECRET' || empty(GOOGLE_CLIENT_SECRET)) {
        sendError('Google OAuth is not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in api/config.php', 500);
    }
    
    if (!defined('GOOGLE_REDIRECT_URI') || empty(GOOGLE_REDIRECT_URI)) {
        sendError('Google OAuth redirect URI is not configured. Please check api/config.php', 500);
    }
    
    // Generate Google OAuth URL
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]);
    
    sendResponse(true, 'Google auth URL generated', [
        'auth_url' => $authUrl,
        'redirect_uri' => GOOGLE_REDIRECT_URI
    ]);
    
} catch (Exception $e) {
    ob_clean();
    sendError('Failed to initiate Google login: ' . $e->getMessage(), 500);
} catch (Error $e) {
    ob_clean();
    sendError('Failed to initiate Google login: ' . $e->getMessage(), 500);
}
?>

