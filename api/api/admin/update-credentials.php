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

// Only allow admin to update credentials
// Check session first (for admin login)
// Set session cookie parameters to ensure session is accessible
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$isAdmin = false;

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $isAdmin = true;
} else {
    // Also check if there's a token-based auth (for API calls)
    require_once __DIR__ . '/../../utils/auth-helpers.php';
    $user = getAuthUser();
    if ($user && $user['role'] === 'admin') {
        $isAdmin = true;
    }
}

if (!$isAdmin) {
    sendError('Unauthorized: Admin access required. Please ensure you are logged in as admin.', 401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$target = $data['target'] ?? null; // 'admin', 'cashier_fairview', 'cashier_sjdm'
$email = isset($data['email']) ? trim($data['email']) : null;
$password = $data['password'] ?? null;

if (!$target) {
    sendError('Target account is required');
    exit;
}

if (!$email && !$password) {
    sendError('Email or password must be provided');
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Determine which account to update based on target
    $role = null;
    $targetEmail = null;
    
    if ($target === 'admin') {
        $role = 'admin';
        $targetEmail = 'admin@gmail.com'; // Default admin email
    } elseif ($target === 'cashier_fairview') {
        $role = 'cashier';
        $targetEmail = 'cashierfairview@gmail.com'; // Default fairview email
    } elseif ($target === 'cashier_sjdm') {
        $role = 'cashier';
        $targetEmail = 'cashiersjdm@gmail.com'; // Default sjdm email
    } else {
        sendError('Invalid target account');
        exit;
    }
    
    // For cashiers, update both users and cashiers tables
    if ($role === 'cashier') {
        // Determine branch_id based on target
        $branchId = ($target === 'cashier_fairview') ? 1 : 2;
        $defaultEmail = ($target === 'cashier_fairview') ? 'cashierfairview@gmail.com' : 'cashiersjdm@gmail.com';
        $defaultPassword = ($target === 'cashier_fairview') ? 'cashierfairview' : 'cashiersjdm';
        
        // Check cashiers table first - try to find by default email or by branch_id
        // If email is being updated, we need to find the cashier first before updating
        $cashierQuery = "SELECT cashier_id, email FROM cashiers WHERE (email = :default_email OR email = :new_email OR branch_id = :branch_id) LIMIT 1";
        $cashierStmt = $db->prepare($cashierQuery);
        $cashierStmt->execute([
            ':default_email' => $defaultEmail,
            ':new_email' => $email ?: $defaultEmail,
            ':branch_id' => $branchId
        ]);
        $cashier = $cashierStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update or create in cashiers table
        if ($cashier) {
            $cashierUpdateFields = [];
            $cashierUpdateParams = [':cashier_id' => $cashier['cashier_id']];
            
            if ($email) {
                $cashierUpdateFields[] = "email = :email";
                $cashierUpdateParams[':email'] = $email;
            }
            
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $cashierUpdateFields[] = "password = :password";
                $cashierUpdateParams[':password'] = $hashedPassword;
            }
            
            if (!empty($cashierUpdateFields)) {
                $cashierUpdateQuery = "UPDATE cashiers SET " . implode(', ', $cashierUpdateFields) . " WHERE cashier_id = :cashier_id";
                $cashierUpdateStmt = $db->prepare($cashierUpdateQuery);
                
                try {
                    $updateSuccess = $cashierUpdateStmt->execute($cashierUpdateParams);
                    
                    if (!$updateSuccess) {
                        $errorInfo = $cashierUpdateStmt->errorInfo();
                        sendError('Failed to update cashier credentials: ' . ($errorInfo[2] ?? 'Unknown error'), 500);
                        exit;
                    }
                    
                    // Note: rowCount() might return 0 if data is unchanged, but that's okay
                    // The update still succeeded if execute() returned true
                } catch (PDOException $e) {
                    sendError('Database error updating cashier: ' . $e->getMessage(), 500);
                    exit;
                }
            } else {
                sendError('No fields provided to update');
                exit;
            }
        } else {
            // Create new cashier
            $firstname = 'Cashier';
            $lastname = ($target === 'cashier_fairview') ? 'Fairview' : 'SJDM';
            $insertEmail = $email ?: $defaultEmail;
            $insertPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            $cashierInsertQuery = "INSERT INTO cashiers (email, password, firstname, lastname, branch_id, is_active, date_created) 
                                   VALUES (:email, :password, :firstname, :lastname, :branch_id, 1, NOW())";
            $cashierInsertStmt = $db->prepare($cashierInsertQuery);
            $cashierInsertStmt->execute([
                ':email' => $insertEmail,
                ':password' => $insertPassword,
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':branch_id' => $branchId
            ]);
        }
        
        // Also update users table for consistency - search by current email or default email
        $userQuery = "SELECT user_id, email FROM users WHERE role = 'cashier' AND (email = :default_email OR email = :new_email OR email LIKE :email_like)";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute([
            ':default_email' => $defaultEmail,
            ':new_email' => $email ?: $defaultEmail,
            ':email_like' => ($target === 'cashier_fairview') ? '%fairview%' : '%sjdm%'
        ]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userUpdateFields = [];
            $userUpdateParams = [':user_id' => $user['user_id']];
            
            if ($email) {
                $userUpdateFields[] = "email = :email";
                $userUpdateParams[':email'] = $email;
            }
            
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userUpdateFields[] = "password = :password";
                $userUpdateParams[':password'] = $hashedPassword;
            }
            
            if (!empty($userUpdateFields)) {
                $userUpdateQuery = "UPDATE users SET " . implode(', ', $userUpdateFields) . " WHERE user_id = :user_id";
                $userUpdateStmt = $db->prepare($userUpdateQuery);
                $userUpdateStmt->execute($userUpdateParams);
            }
        }
        
        // Verify the update by fetching the updated record
        $verifyQuery = "SELECT cashier_id, email, password FROM cashiers WHERE cashier_id = :cashier_id";
        $verifyStmt = $db->prepare($verifyQuery);
        $verifyStmt->execute([':cashier_id' => $cashier['cashier_id']]);
        $updatedCashier = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password was updated if password was provided
        $passwordUpdated = true;
        if ($password && $updatedCashier) {
            // Check if the stored password matches what we just set (either hashed or plain)
            if (strpos($updatedCashier['password'], '$2') === 0) {
                // Password is hashed, verify it matches
                $passwordUpdated = password_verify($password, $updatedCashier['password']);
            } else {
                // Password is plain text, compare directly
                $passwordUpdated = ($updatedCashier['password'] === $password);
            }
        }
        
        if (!$passwordUpdated && $password) {
            sendError('Password update may have failed. Please verify in database.', 500);
            exit;
        }
        
        sendResponse(true, 'Cashier credentials updated successfully', [
            'cashier_id' => $cashier['cashier_id'],
            'email' => $updatedCashier['email'] ?: ($email ?: $defaultEmail),
            'password_updated' => $password ? true : false,
            'message' => 'Credentials have been updated in the database. You can now login with the new password.'
        ]);
        exit;
    }
    
    // For admin, update users table
    $query = "SELECT user_id, email, role FROM users WHERE role = 'admin' AND (email = :target_email OR email LIKE :target_email_like)";
    $params = [
        ':target_email' => 'admin@gmail.com',
        ':target_email_like' => '%admin%'
    ];
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If admin doesn't exist, create it
    if (!$user) {
        $insertQuery = "INSERT INTO users (firstname, lastname, username, email, password, role, is_active, date_created) 
                        VALUES (:firstname, :lastname, :username, :email, :password, 'admin', 1, NOW())";
        $insertStmt = $db->prepare($insertQuery);
        
        $insertEmail = $email ?: 'admin@gmail.com';
        $insertPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : password_hash('admin123', PASSWORD_DEFAULT);
        
        $insertParams = [
            ':firstname' => 'Admin',
            ':lastname' => 'User',
            ':username' => 'admin',
            ':email' => $insertEmail,
            ':password' => $insertPassword
        ];
        
        $insertStmt->execute($insertParams);
        $userId = $db->lastInsertId();
        
        sendResponse(true, 'Admin account created and updated successfully', [
            'user_id' => $userId,
            'email' => $insertEmail
        ]);
        exit;
    }
    
    // Update existing admin
    $updateFields = [];
    $updateParams = [':user_id' => $user['user_id']];
    
    if ($email) {
        $updateFields[] = "email = :email";
        $updateParams[':email'] = $email;
    }
    
    if ($password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateFields[] = "password = :password";
        $updateParams[':password'] = $hashedPassword;
    }
    
    if (empty($updateFields)) {
        sendError('No fields to update');
        exit;
    }
    
    $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = :user_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute($updateParams);
    
    sendResponse(true, 'Admin credentials updated successfully', [
        'user_id' => $user['user_id'],
        'email' => $email ?: $user['email']
    ]);
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}

