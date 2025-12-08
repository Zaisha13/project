<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || !isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    sendError('Name, username, email, and password are required');
}

$name = trim($data['name']);
$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$address = isset($data['address']) ? trim($data['address']) : '';

if (!validateEmail($email)) {
    sendError('Invalid email format');
}

if (strlen($password) < 6) {
    sendError('Password must be at least 6 characters');
}

$database = new Database();
$db = $database->getConnection();

// Check if user already exists
$checkQuery = "SELECT id FROM users WHERE email = :email OR username = :username";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':email', $email);
$checkStmt->bindParam(':username', $username);
$checkStmt->execute();

if ($checkStmt->fetch()) {
    sendError('User with this email or username already exists');
}

// Create new user
$query = "INSERT INTO users (name, username, email, password, phone, address, role) 
          VALUES (:name, :username, :email, :password, :phone, :address, 'customer')";
$stmt = $db->prepare($query);

$hashedPassword = hashPassword($password);

$stmt->bindParam(':name', $name);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':password', $hashedPassword);
$stmt->bindParam(':phone', $phone);
$stmt->bindParam(':address', $address);

if ($stmt->execute()) {
    $userId = $db->lastInsertId();
    
    $query = "SELECT id, name, username, email, role, phone, address FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch();
    
    $token = generateToken($userId, 'customer');
    
    sendResponse(true, 'Registration successful', [
        'user' => $user,
        'token' => $token
    ]);
} else {
    sendError('Registration failed');
}
?>

