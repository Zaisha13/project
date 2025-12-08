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

if (!isset($data['email']) || !isset($data['password'])) {
    sendError('Email and password are required');
}

$email = trim($data['email']);
$password = $data['password'];
$role = $data['role'] ?? null; // Optional role filter for admin/cashier login

$database = new Database();
$db = $database->getConnection();

$query = "SELECT user_id, firstname, lastname, username, email, password, role, phone, address FROM users WHERE (email = :email OR username = :username)";
$params = [
    ':email' => $email,
    ':username' => $email
];
if ($role) {
    $query .= " AND role = :role";
    $params[':role'] = $role;
}
$stmt = $db->prepare($query);
$stmt->execute($params);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendError('Invalid email or password', 401);
}

if (!verifyPassword($password, $user['password'])) {
    sendError('Invalid email or password', 401);
}

// Build name from firstname and lastname
$name = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));

// Remove password from response and format user data
$userData = [
    'id' => $user['user_id'], // For compatibility with frontend expecting 'id'
    'user_id' => $user['user_id'], // Also include user_id explicitly
    'name' => $name ?: ($user['username'] ?? ''),
    'firstname' => $user['firstname'] ?? '',
    'lastname' => $user['lastname'] ?? '',
    'username' => $user['username'] ?? '',
    'email' => $user['email'] ?? '',
    'role' => $user['role'] ?? 'customer',
    'phone' => $user['phone'] ?? '',
    'address' => $user['address'] ?? ''
];

$token = generateToken($userData['user_id'], $userData['role']);

sendResponse(true, 'Login successful', [
    'user' => $userData,
    'token' => $token
]);
?>

