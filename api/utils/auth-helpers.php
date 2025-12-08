<?php
require_once __DIR__ . '/../config/database.php';

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateOrderId() {
    return 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function getAuthUser() {
    // Get token from header or request
    // Try multiple ways to get the Authorization header (case-insensitive)
    $token = null;
    
    // Debug: Log all available headers and server vars
    error_log('DEBUG: Checking for Authorization token...');
    error_log('DEBUG: $_SERVER keys: ' . implode(', ', array_keys($_SERVER)));
    
    // Method 1: Try $_SERVER['HTTP_AUTHORIZATION'] (most reliable)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim($_SERVER['HTTP_AUTHORIZATION']);
        error_log('DEBUG: Found token in HTTP_AUTHORIZATION');
    }
    
    // Method 1b: Try REDIRECT_HTTP_AUTHORIZATION (Apache sometimes uses this)
    if (!$token && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $token = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        error_log('DEBUG: Found token in REDIRECT_HTTP_AUTHORIZATION');
    }
    
    // Method 2: Try getallheaders() with case-insensitive search
    if (!$token) {
        $headers = getallheaders();
        if ($headers) {
            error_log('DEBUG: getallheaders() returned: ' . json_encode(array_keys($headers)));
            // Search case-insensitively
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    $token = trim($value);
                    error_log('DEBUG: Found token in getallheaders()');
                    break;
                }
            }
        }
    }
    
    // Method 3: Try from request body (for PUT requests) - check _token field
    // Note: We can't read php://input here as it will be consumed. 
    // Instead, we'll check if the endpoint passes it to us via a global or we'll handle it in the endpoint itself.
    // For now, skip this method to avoid consuming the input stream
    
    // Method 4: Try from request parameters (GET/POST)
    if (!$token) {
        $token = $_GET['token'] ?? $_POST['token'] ?? null;
        if ($token) {
            $token = trim($token);
            error_log('DEBUG: Found token in request parameters');
        }
    }
    
    // Method 5: Try from cookie
    if (!$token) {
        $token = $_COOKIE['auth_token'] ?? null;
        if ($token) {
            $token = trim($token);
            error_log('DEBUG: Found token in cookie');
        }
    }
    
    if (!$token) {
        error_log('DEBUG: No token found in any location');
        return null;
    }
    
    // Remove "Bearer " prefix if present
    if (strpos($token, 'Bearer ') === 0) {
        $token = trim(substr($token, 7));
    }
    
    // Final trim to ensure no whitespace
    $token = trim($token);
    
    if (empty($token)) {
        error_log('DEBUG: Token is empty after trimming');
        return null;
    }
    
    error_log('DEBUG: Token found: ' . substr($token, 0, 20) . '...');
    
    // Decode token (simple base64 for now, in production use JWT)
    try {
        $decoded = json_decode(base64_decode($token), true);
        if (isset($decoded['user_id']) && isset($decoded['role'])) {
            $database = new Database();
            $db = $database->getConnection();
            
            $user = null;
            
            // Check cashiers table if role is 'cashier'
            if ($decoded['role'] === 'cashier') {
                $query = "SELECT cashier_id, email, firstname, lastname, branch_id, is_active FROM cashiers WHERE cashier_id = :cashier_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':cashier_id', $decoded['user_id']);
                $stmt->execute();
                $cashier = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($cashier) {
                    // Convert cashier to user format for compatibility
                    $user = [
                        'user_id' => $cashier['cashier_id'],
                        'id' => $cashier['cashier_id'],
                        'email' => $cashier['email'],
                        'firstname' => $cashier['firstname'] ?? '',
                        'lastname' => $cashier['lastname'] ?? '',
                        'username' => $cashier['email'],
                        'role' => 'cashier',
                        'name' => trim(($cashier['firstname'] ?? '') . ' ' . ($cashier['lastname'] ?? ''))
                    ];
                }
            } else {
                // Check users table for other roles
                $query = "SELECT user_id, firstname, lastname, username, email, role, phone, address FROM users WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $decoded['user_id']);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Ensure id field exists (use user_id if id doesn't exist)
                if ($user && !isset($user['id']) && isset($user['user_id'])) {
                    $user['id'] = $user['user_id'];
                }
                // Create name field from firstname and lastname
                if ($user && !isset($user['name'])) {
                    $user['name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
                }
            }
            
            return $user;
        }
    } catch (Exception $e) {
        error_log('Error in getAuthUser: ' . $e->getMessage());
        return null;
    }
    
    return null;
}

function requireAuth($requiredRole = null) {
    $user = getAuthUser();
    
    if (!$user) {
        sendError('Authentication required', 401);
    }
    
    if ($requiredRole && $user['role'] !== $requiredRole) {
        sendError('Insufficient permissions', 403);
    }
    
    return $user;
}

function generateToken($userId, $role) {
    return base64_encode(json_encode(['user_id' => $userId, 'role' => $role]));
}
?>

