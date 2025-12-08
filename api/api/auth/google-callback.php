<?php
session_start();

// Set headers to prevent any output issues
header('Content-Type: text/html; charset=UTF-8');

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../utils/response.php';
    require_once __DIR__ . '/../../utils/auth-helpers.php';
    require_once __DIR__ . '/../../config.php';
    
    // Check if Google OAuth credentials are configured
    if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID' || empty(GOOGLE_CLIENT_ID)) {
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode('Google OAuth is not configured. Please set GOOGLE_CLIENT_ID in api/config.php'));
        exit;
    }
    
    if (!defined('GOOGLE_CLIENT_SECRET') || GOOGLE_CLIENT_SECRET === 'YOUR_GOOGLE_CLIENT_SECRET' || empty(GOOGLE_CLIENT_SECRET)) {
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode('Google OAuth is not configured. Please set GOOGLE_CLIENT_SECRET in api/config.php'));
        exit;
    }
    
    if (!defined('FRONTEND_DOMAIN') || empty(FRONTEND_DOMAIN)) {
        header('Location: ' . BASE_URL . '/public/customer/customer_dashboard.php?auth=error&message=' . urlencode('Frontend domain is not configured'));
        exit;
    }
    
    // `config.php` defines `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`,
    // `GOOGLE_REDIRECT_URI` (constructed from `BASE_URL`) and `FRONTEND_DOMAIN`.
    // Avoid re-defining those here so they adapt to the current host/port.
    
    if (!isset($_GET['code'])) {
        $errorMsg = $_GET['error'] ?? 'Authorization failed';
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode($errorMsg));
        exit;
    }

    $code = $_GET['code'];
    
    // Exchange code for token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $tokenResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || !empty($curlError)) {
        $errorMsg = 'Failed to exchange authorization code for token';
        if (!empty($curlError)) {
            $errorMsg .= ': ' . $curlError;
        } else {
            $errorData = json_decode($tokenResponse, true);
            $errorMsg .= ': ' . ($errorData['error_description'] ?? $errorData['error'] ?? 'Unknown error');
        }
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode($errorMsg));
        exit;
    }
    
    $tokenData = json_decode($tokenResponse, true);
    $accessToken = $tokenData['access_token'] ?? null;
    
    if (!$accessToken) {
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode('Failed to get access token from Google'));
        exit;
    }
    
    // Get user info from Google
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    
    $userInfoResponse = curl_exec($ch);
    $userInfoHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $userInfoCurlError = curl_error($ch);
    curl_close($ch);
    
    if ($userInfoHttpCode !== 200 || !empty($userInfoCurlError)) {
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode('Failed to get user information from Google'));
        exit;
    }
    
    $userInfo = json_decode($userInfoResponse, true);
    
    if (!$userInfo || !isset($userInfo['email'])) {
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode('Failed to get email from Google account'));
        exit;
    }
    
    // Check if user exists
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM users WHERE google_id = :google_id OR email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':google_id', $userInfo['id']);
    $stmt->bindParam(':email', $userInfo['email']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Create new user
        $fullName = $userInfo['name'] ?? $userInfo['email'];
        $nameParts = explode(' ', $fullName, 2);
        $firstname = $nameParts[0] ?? '';
        $lastname = $nameParts[1] ?? '';
        
        $username = strtolower(str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9\s]/', '', $fullName))) . '_' . substr($userInfo['id'], 0, 8);
        
        // Ensure username is unique
        $checkUsername = $db->prepare("SELECT user_id FROM users WHERE username = :username");
        $checkUsername->bindParam(':username', $username);
        $checkUsername->execute();
        $counter = 1;
        $originalUsername = $username;
        while ($checkUsername->fetch()) {
            $username = $originalUsername . '_' . $counter;
            $checkUsername->execute();
            $counter++;
        }
        
        $query = "INSERT INTO users (firstname, lastname, username, email, google_id, role, password) 
                  VALUES (:firstname, :lastname, :username, :email, :google_id, 'customer', :password)";
        $stmt = $db->prepare($query);
        $password = hashPassword(uniqid()); // Random password for Google users
        $stmt->bindParam(':firstname', $firstname);
        $stmt->bindParam(':lastname', $lastname);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $userInfo['email']);
        $stmt->bindParam(':google_id', $userInfo['id']);
        $stmt->bindParam(':password', $password);
        
        if (!$stmt->execute()) {
            header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode('Failed to create user account'));
            exit;
        }
        
        $userId = $db->lastInsertId();
        // Ensure both id and user_id are set for compatibility
        $user = [
            'id' => $userId, 
            'user_id' => $userId,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'name' => trim($firstname . ' ' . $lastname),
            'username' => $username,
            'email' => $userInfo['email'], 
            'role' => 'customer'
        ];
    } else {
        // Ensure id field exists for compatibility
        if (!isset($user['id']) && isset($user['user_id'])) {
            $user['id'] = $user['user_id'];
        }
        // Ensure name field exists
        if (!isset($user['name'])) {
            $user['name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        }
        
        // Update google_id if not set
        if (empty($user['google_id'])) {
            $updateQuery = "UPDATE users SET google_id = :google_id WHERE user_id = :user_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':google_id', $userInfo['id']);
            $updateStmt->bindParam(':user_id', $user['user_id']);
            $updateStmt->execute();
            $user['google_id'] = $userInfo['id'];
        }
    }
    
    // Generate token and redirect - use user_id for token generation
    $userIdForToken = $user['user_id'] ?? $user['id'] ?? null;
    if (!$userIdForToken) {
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=error&message=' . urlencode('Failed to get user ID'));
        exit;
    }
    $token = generateToken($userIdForToken, $user['role'] ?? 'customer');
    
    header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.php?auth=success&token=' . urlencode($token) . '&user=' . urlencode(json_encode($user)));
    exit;
    
} catch (Exception $e) {
    $errorMsg = 'An error occurred during Google authentication: ' . $e->getMessage();
    $redirectUrl = (defined('FRONTEND_DOMAIN') ? FRONTEND_DOMAIN : (defined('BASE_URL') ? BASE_URL . '/public/customer' : '')) . '/customer_dashboard.php?auth=error&message=' . urlencode($errorMsg);
    header('Location: ' . $redirectUrl);
    exit;
} catch (Error $e) {
    $errorMsg = 'An error occurred during Google authentication: ' . $e->getMessage();
    $redirectUrl = (defined('FRONTEND_DOMAIN') ? FRONTEND_DOMAIN : (defined('BASE_URL') ? BASE_URL . '/public/customer' : '')) . '/customer_dashboard.php?auth=error&message=' . urlencode($errorMsg);
    header('Location: ' . $redirectUrl);
    exit;
}
?>

