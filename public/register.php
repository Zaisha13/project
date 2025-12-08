<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

// Include database connection
include 'db_connect.php';

// Make sure connection works
if (!$conn || $conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . ($conn ? $conn->connect_error : 'Connection object is null')]);
  exit;
}

// Collect form data safely
$firstname = $_POST['firstname'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$username = $_POST['username'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($firstname) || empty($lastname) || empty($username) || empty($email) || empty($password)) {
  echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
  exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
  exit;
}

// Hash password for security
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
  // Check for duplicates (email or username)
  $check = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
  if (!$check) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
    exit;
  }
  
  $check->bind_param("ss", $email, $username);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email or username already exists.']);
    $check->close();
    $conn->close();
    exit;
  }
  $check->close();

  // Insert new user (user_id is AUTO_INCREMENT, so we don't need to specify it)
  $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, username, phone, email, address, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'customer')");
  if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
    $conn->close();
    exit;
  }
  
  $stmt->bind_param("sssssss", $firstname, $lastname, $username, $phone, $email, $address, $hashedPassword);

  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Database insert failed: ' . $stmt->error]);
  }

  $stmt->close();
  $conn->close();

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
  if (isset($conn) && $conn) {
    $conn->close();
  }
}
?>
