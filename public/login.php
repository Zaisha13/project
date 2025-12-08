<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Include database connection
include 'db_connect.php';

// Make sure connection works
if (!$conn || $conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
  exit;
}

// Get user input
$email_or_username = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email_or_username) || empty($password)) {
  echo json_encode(['success' => false, 'message' => 'Email/Username and password are required.']);
  exit;
}

try {
  // Check if user exists by email or username
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
  $stmt->bind_param("ss", $email_or_username, $email_or_username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Account not found.']);
    exit;
  }

  $user = $result->fetch_assoc();

  // Verify password
  if (password_verify($password, $user['password'])) {
    // Optional: start a session
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    echo json_encode([
      'success' => true,
      'message' => 'Login successful!',
      'user' => [
        'id' => $user['user_id'],
        'user_id' => $user['user_id'],
        'name' => trim($user['firstname'] . ' ' . $user['lastname']),
        'firstname' => $user['firstname'] ?? '',
        'lastname' => $user['lastname'] ?? '',
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'phone' => $user['phone'] ?? '',
        'address' => $user['address'] ?? ''
      ]
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Invalid password.']);
  }

  $stmt->close();
  $conn->close();

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
