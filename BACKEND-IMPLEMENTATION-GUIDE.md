# Backend Implementation Guide for Jessie Cane Juice Bar

This guide provides complete instructions for PHP backend developers to implement the backend API, MySQL database, Xendit GCash payment integration, and Google Authentication for the Jessie Cane Juice Bar web application.

---

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Database Setup](#database-setup)
3. [Backend API Structure](#backend-api-structure)
4. [Xendit GCash Payment Integration](#xendit-gcash-payment-integration)
5. [Google Authentication Integration](#google-authentication-integration)
6. [XAMPP Configuration](#xampp-configuration)
7. [Frontend Integration](#frontend-integration)
8. [Testing](#testing)

---

## Prerequisites

### Required Software
- **XAMPP** (Apache + MySQL + PHP) - [Download](https://www.apachefriends.org/)
- **phpMyAdmin** (included with XAMPP)
- **Visual Studio Code** or any PHP editor
- **Google Cloud Console** account (for Google Auth)
- **Xendit Account** (for GCash payment integration) - [Sign Up](https://www.xendit.co/)

### PHP Extensions Required
```php
- PDO
- PDO_MySQL
- OpenSSL (for password hashing)
- JSON
- CURL (for API calls)
- mbstring
```

---

## Database Setup

### Step 1: Create Database

Open phpMyAdmin (usually at `http://localhost/phpmyadmin`) and create a new database:

```sql
CREATE DATABASE jessie_cane_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jessie_cane_db;
```

### Step 2: Create Tables

Execute the following SQL scripts to create all necessary tables:

#### Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'cashier', 'customer') DEFAULT 'customer',
    google_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Products Table
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(500),
    price_regular DECIMAL(10, 2) NOT NULL,
    price_tall DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Orders Table
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_username VARCHAR(100) DEFAULT '',
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    customer_notes TEXT,
    branch VARCHAR(100) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    tax DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('GCash', 'Cash', 'Other') DEFAULT 'GCash',
    payment_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    order_status ENUM('Pending', 'Approved', 'Processing', 'Ready', 'Out for Delivery', 'Completed', 'Cancelled') DEFAULT 'Pending',
    order_type ENUM('Digital', 'Walk-in') DEFAULT 'Digital',
    is_guest TINYINT(1) DEFAULT 0,
    timestamp BIGINT,
    order_date VARCHAR(50),
    order_time VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### Order Items Table
```sql
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    size VARCHAR(50) NOT NULL,
    special VARCHAR(100),
    notes TEXT,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

#### Payments Table (for Xendit GCash transactions)
```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    xendit_id VARCHAR(255) UNIQUE,
    xendit_reference VARCHAR(255),
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Paid', 'Failed', 'Expired', 'Cancelled') DEFAULT 'Pending',
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

#### Event Inquiries Table
```sql
CREATE TABLE event_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    event_type VARCHAR(100),
    event_date DATE,
    guest_count INT,
    location VARCHAR(255),
    message TEXT,
    status ENUM('New', 'Contacted', 'Confirmed', 'Cancelled') DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### Step 3: Insert Default Data

#### Insert Default Admin User
```sql
-- Password hash for 'admin123' using PHP password_hash()
INSERT INTO users (name, username, email, password, role) 
VALUES ('Main Admin', 'admin', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
```

#### Insert Sample Products
```sql
INSERT INTO products (name, description, image, price_regular, price_tall) VALUES
('Pure Sugarcane', 'Freshly pressed sugarcane juice in its purest form — naturally sweet, refreshing, and energizing with no added sugar or preservatives.', 'pure-sugarcane.png', 79.00, 109.00),
('Calamansi Cane', 'A zesty twist on classic sugarcane juice, blended with the tangy freshness of calamansi for a perfectly balanced sweet and citrusy drink.', 'calamansi-cane.png', 89.00, 119.00),
('Lemon Cane', 'Freshly squeezed lemon combined with pure sugarcane juice, creating a crisp and revitalizing drink that awakens your senses.', 'lemon-cane.png', 89.00, 119.00),
('Yakult Cane', 'A delightful mix of sugarcane juice and Yakult — smooth, creamy, and packed with probiotics for a unique sweet-tangy flavor.', 'yakult-cane.png', 89.00, 119.00),
('Calamansi Yakult Cane', 'A refreshing blend of calamansi, Yakult, and sugarcane juice — the perfect harmony of sweet, sour, and creamy goodness.', 'calamansi-yakult-cane.png', 99.00, 129.00),
('Lemon Yakult Cane', 'Experience a fusion of lemon''s zesty tang with Yakult''s smooth creaminess, all complemented by naturally sweet sugarcane.', 'lemon-yakult-cane.png', 99.00, 129.00),
('Lychee Cane', 'A fragrant and fruity treat made with the exotic sweetness of lychee and the crisp freshness of sugarcane juice.', 'lychee-cane.png', 99.00, 129.00),
('Orange Cane', 'Fresh orange juice blended with pure sugarcane extract for a bright, citrusy burst of sunshine in every sip.', 'orange-cane.png', 109.00, 139.00),
('Passion Fruit Cane', 'A tropical blend of tangy passion fruit and naturally sweet sugarcane — vibrant, juicy, and irresistibly refreshing.', 'passion-fruit-cane.png', 109.00, 139.00),
('Watermelon Cane', 'A hydrating fusion of freshly pressed watermelon and sugarcane juice, offering a light, cooling sweetness that''s perfect for hot days.', 'watermelon-cane.png', 109.00, 139.00),
('Dragon Fruit Cane', 'A vibrant blend of dragon fruit and pure sugarcane juice — visually stunning, naturally sweet, and loaded with antioxidants.', 'dragon-fruit-cane.png', 119.00, 149.00),
('Strawberry Yogurt Cane', 'Creamy strawberry yogurt meets sweet sugarcane for a smooth, fruity, and indulgent drink that''s both refreshing and satisfying.', 'strawberry-yogurt-cane.png', 119.00, 149.00);
```

---

## Backend API Structure

Create the following folder structure in your XAMPP `htdocs` folder:

```
htdocs/
├── jessie-cane-api/
│   ├── config/
│   │   └── database.php
│   ├── api/
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── logout.php
│   │   │   └── google-auth.php
│   │   ├── products/
│   │   │   ├── get-all.php
│   │   │   ├── get.php
│   │   │   ├── create.php
│   │   │   ├── update.php
│   │   │   └── delete.php
│   │   ├── orders/
│   │   │   ├── create.php
│   │   │   ├── get-all.php
│   │   │   ├── get.php
│   │   │   └── update-status.php
│   │   ├── payments/
│   │   │   ├── gcash-create.php
│   │   │   ├── gcash-callback.php
│   │   │   └── gcash-verify.php
│   │   └── users/
│   │       ├── profile.php
│   │       ├── update.php
│   │       └── inquiries.php
│   └── utils/
│       ├── response.php
│       └── auth-helpers.php
```

### Database Configuration

**File: `config/database.php`**
```php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "jessie_cane_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
```

### Response Helper

**File: `utils/response.php`**
```php
<?php
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

function sendError($message, $statusCode = 400) {
    sendResponse(false, $message, null, $statusCode);
}
?>
```

### Authentication Helper

**File: `utils/auth-helpers.php`**
```php
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
    return 'ORD-' . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
}

function verifyToken($token) {
    // Implement JWT token verification here
    // For now, return user ID from token
    return null;
}
?>
```

### Login API

**File: `api/auth/login.php`**
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

$email = $data['email'];
$password = $data['password'];

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, username, email, password, role, phone, address FROM users WHERE email = :email OR username = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

$user = $stmt->fetch();

if (!$user) {
    sendError('Invalid email or password', 401);
}

if (!verifyPassword($password, $user['password'])) {
    sendError('Invalid email or password', 401);
}

// Remove password from response
unset($user['password']);

sendResponse(true, 'Login successful', [
    'user' => $user,
    'token' => base64_encode(json_encode(['user_id' => $user['id'], 'role' => $user['role']]))
]);
?>
```

### Register API

**File: `api/auth/register.php`**
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

$name = $data['name'];
$username = $data['username'];
$email = $data['email'];
$password = $data['password'];
$phone = $data['phone'] ?? '';
$address = $data['address'] ?? '';

if (!validateEmail($email)) {
    sendError('Invalid email format');
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
    
    sendResponse(true, 'Registration successful', [
        'user' => $user,
        'token' => base64_encode(json_encode(['user_id' => $userId, 'role' => 'customer']))
    ]);
} else {
    sendError('Registration failed');
}
?>
```

### Products Get All API

**File: `api/products/get-all.php`**
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM products ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute();

$products = $stmt->fetchAll();

// Update image paths to full URLs
foreach ($products as &$product) {
    if ($product['image']) {
        $product['image'] = 'http://localhost/Project-sa-SOFE-main/assets/images/' . $product['image'];
    }
}

sendResponse(true, 'Products retrieved successfully', $products);
?>
```

### Create Order API

**File: `api/orders/create.php`**
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['items']) || !isset($data['customer_name']) || !isset($data['branch'])) {
    sendError('Items, customer name, and branch are required');
}

$userId = $data['user_id'] ?? null;
$customerName = $data['customer_name'];
$customerUsername = $data['customer_username'] ?? '';
$customerEmail = $data['customer_email'] ?? '';
$customerPhone = $data['customer_phone'] ?? '';
$customerNotes = $data['customer_notes'] ?? '';
$branch = $data['branch'];
$items = $data['items'];
$subtotal = $data['subtotal'];
$total = $data['total'];
$orderType = 'Digital';
$isGuest = !$userId || $userId === 0;

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    $orderId = generateOrderId();
    $timestamp = time();
    $orderDate = date('m/d/Y');
    $orderTime = date('g:i A');
    
    // Insert order
    $query = "INSERT INTO orders (order_id, user_id, customer_name, customer_username, customer_email, customer_phone, customer_notes, branch, subtotal, total, order_type, is_guest, timestamp, order_date, order_time, payment_method, payment_status, order_status) 
              VALUES (:order_id, :user_id, :customer_name, :customer_username, :customer_email, :customer_phone, :customer_notes, :branch, :subtotal, :total, :order_type, :is_guest, :timestamp, :order_date, :order_time, 'GCash', 'Pending', 'Pending')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $orderId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':customer_name', $customerName);
    $stmt->bindParam(':customer_username', $customerUsername);
    $stmt->bindParam(':customer_email', $customerEmail);
    $stmt->bindParam(':customer_phone', $customerPhone);
    $stmt->bindParam(':customer_notes', $customerNotes);
    $stmt->bindParam(':branch', $branch);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':order_type', $orderType);
    $stmt->bindParam(':is_guest', $isGuest);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->bindParam(':order_date', $orderDate);
    $stmt->bindParam(':order_time', $orderTime);
    
    $stmt->execute();
    
    $orderDbId = $db->lastInsertId();
    
    // Insert order items
    foreach ($items as $item) {
        $itemQuery = "INSERT INTO order_items (order_id, product_name, size, special, notes, quantity, price) 
                      VALUES (:order_id, :name, :size, :special, :notes, :qty, :price)";
        $itemStmt = $db->prepare($itemQuery);
        $itemStmt->bindParam(':order_id', $orderDbId);
        $itemStmt->bindParam(':name', $item['name']);
        $itemStmt->bindParam(':size', $item['size']);
        $itemStmt->bindParam(':special', $item['special']);
        $itemStmt->bindParam(':notes', $item['notes']);
        $itemStmt->bindParam(':qty', $item['qty']);
        $itemStmt->bindParam(':price', $item['price']);
        $itemStmt->execute();
    }
    
    $db->commit();
    
    sendResponse(true, 'Order created successfully', [
        'order_id' => $orderId,
        'order_db_id' => $orderDbId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    sendError('Failed to create order: ' . $e->getMessage());
}
?>
```

---

## Xendit GCash Payment Integration

### Step 1: Register for Xendit Account

1. Visit [Xendit Portal](https://www.xendit.co/)
2. Create an account and register your application
3. Get your **Secret API Key** from Settings → API Keys
4. Enable **GCash** payment method in your dashboard

**Important Note:** This guide uses **Xendit Sandbox** for testing purposes. Real production integration requires:
- Business registration and verification
- Tax identification documents
- Bank account setup
- Compliance requirements

For development and testing, use Xendit Sandbox credentials which require minimal setup.

### Step 2: Install Xendit PHP Library (Optional)

You can use cURL directly or install via Composer:
```bash
composer require xendit/xendit-php
```

### Step 3: Create Xendit GCash Payment

**File: `api/payments/xendit-create.php`**
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

// Xendit Configuration
// For testing: Use Xendit Sandbox API URL
define('XENDIT_API_URL', 'https://api.xendit.co/v2/invoices'); // Same URL for sandbox
define('XENDIT_SECRET_KEY', 'YOUR_XENDIT_SECRET_KEY_HERE'); // Use Sandbox key for testing
// Production: Change to production secret key when going live

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['amount'])) {
    sendError('Order ID and amount are required');
}

$orderDbId = $data['order_id'];
$amount = floatval($data['amount']);

$database = new Database();
$db = $database->getConnection();

// Get order details
$query = "SELECT * FROM orders WHERE id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $orderDbId);
$stmt->execute();
$order = $stmt->fetch();

if (!$order) {
    sendError('Order not found');
}

// Prepare Xendit payment request
$paymentData = [
    'external_id' => $order['order_id'],
    'amount' => $amount,
    'description' => 'Jessie Cane Juice Bar - Order ' . $order['order_id'],
    'invoice_duration' => 3600, // 1 hour
    'success_redirect_url' => 'http://localhost/Project-sa-SOFE-main/public/customer/thank-you.html?order=' . $order['order_id'],
    'failure_redirect_url' => 'http://localhost/Project-sa-SOFE-main/public/customer/drinks.html?payment=failed',
    'customer' => [
        'given_names' => $order['customer_name'],
        'email' => $order['customer_email'] ?? '',
        'mobile_number' => $order['customer_phone'] ?? ''
    ],
    'payment_methods' => ['GCASH'],
    'currency' => 'PHP'
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, XENDIT_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
curl_setopt($ch, CURLOPT_USERPWD, XENDIT_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $paymentResponse = json_decode($response, true);
    
    // Save payment record
    $query = "INSERT INTO payments (order_id, xendit_id, xendit_reference, amount, status, metadata) 
              VALUES (:order_id, :xendit_id, :reference, :amount, 'Pending', :metadata)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $orderDbId);
    $stmt->bindParam(':xendit_id', $paymentResponse['id']);
    $stmt->bindParam(':reference', $order['order_id']);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':metadata', json_encode($paymentResponse));
    $stmt->execute();
    
    sendResponse(true, 'Xendit GCash payment link created', [
        'payment_url' => $paymentResponse['invoice_url'],
        'invoice_id' => $paymentResponse['id'],
        'reference' => $order['order_id']
    ]);
} else {
    $errorResponse = json_decode($response, true);
    sendError('Failed to create payment: ' . ($errorResponse['message'] ?? 'Unknown error'), 500);
}
?>
```

### Step 4: Xendit Webhook Handler

**File: `api/payments/xendit-webhook.php`**
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

define('XENDIT_WEBHOOK_TOKEN', 'YOUR_WEBHOOK_TOKEN_HERE');

// Get webhook data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verify webhook token
$webhookToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
if ($webhookToken !== XENDIT_WEBHOOK_TOKEN) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle different event types
$event = $data['event'] ?? '';

if ($event === 'invoice.paid') {
    $invoiceId = $data['data']['id'] ?? '';
    $status = 'Paid';
    
    // Get payment record
    $query = "SELECT * FROM payments WHERE xendit_id = :xendit_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':xendit_id', $invoiceId);
    $stmt->execute();
    $payment = $stmt->fetch();
    
    if ($payment) {
        // Update payment status
        $updateQuery = "UPDATE payments SET status = 'Paid' WHERE xendit_id = :xendit_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':xendit_id', $invoiceId);
        $updateStmt->execute();
        
        // Update order status
        $orderQuery = "UPDATE orders SET payment_status = 'Paid', order_status = 'Approved' WHERE id = :order_id";
        $orderStmt = $db->prepare($orderQuery);
        $orderStmt->bindParam(':order_id', $payment['order_id']);
        $orderStmt->execute();
    }
} elseif ($event === 'invoice.expired' || $event === 'invoice.failed') {
    $invoiceId = $data['data']['id'] ?? '';
    $status = 'Failed';
    
    $query = "UPDATE payments SET status = 'Failed' WHERE xendit_id = :xendit_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':xendit_id', $invoiceId);
    $stmt->execute();
}

http_response_code(200);
echo json_encode(['success' => true]);
?>
```

### Step 5: Testing with Xendit Sandbox

**Testing Payments in Sandbox Mode:**

1. **Sandbox Account Setup:**
   - Xendit automatically provides sandbox credentials when you sign up
   - Use sandbox API key for testing (starts with `xnd_public_development_` for public key)
   - Sandbox doesn't process real payments

2. **Simulate GCash Payment:**
   - When customer clicks checkout, they'll be redirected to Xendit sandbox payment page
   - Use test GCash numbers provided by Xendit documentation
   - Common test numbers: `09123456789` or as per Xendit's current sandbox testing guide
   - Complete the payment flow to test successful payment

3. **Test Payment States:**
   - **Success:** Complete payment with valid test credentials
   - **Failed:** Use invalid credentials or let payment expire
   - **Expired:** Wait for invoice duration to expire (default 1 hour)

4. **Webhook Testing:**
   - Use Xendit's webhook simulation tool or ngrok for local testing
   - Configure webhook URL in Xendit dashboard: `http://your-ngrok-url/jessie-cane-api/api/payments/xendit-webhook.php`
   - Test different webhook events: `invoice.paid`, `invoice.expired`, `invoice.failed`

**Important:** When ready for production, switch to production API key and complete Xendit's verification process.

---

## Google Authentication Integration

### Step 1: Set Up Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable **Google+ API** or **Google Identity Platform**
4. Create **OAuth 2.0 credentials**:
   - Application type: Web application
   - Authorized redirect URIs: `http://localhost/jessie-cane-api/api/auth/google-callback.php`
5. Copy **Client ID** and **Client Secret**

### Step 2: Install Google OAuth PHP Library

Install via Composer:
```bash
composer require google/apiclient
```

Or download from: https://github.com/googleapis/google-api-php-client

### Step 3: Google Login Handler

**File: `api/auth/google-auth.php`**
```php
<?php
session_start();

require_once __DIR__ . '/../../../vendor/autoload.php'; // Adjust path to Google API client
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/jessie-cane-api/api/auth/google-callback.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

// Generate and return auth URL
$authUrl = $client->createAuthUrl();

sendResponse(true, 'Google auth URL generated', ['auth_url' => $authUrl]);
?>
```

### Step 4: Google Callback Handler

**File: `api/auth/google-callback.php`**
```php
<?php
session_start();

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/jessie-cane-api/api/auth/google-callback.php');
define('FRONTEND_DOMAIN', 'http://localhost/Project-sa-SOFE-main/public/customer');

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (isset($token['error'])) {
        header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.html?auth=error');
        exit;
    }
    
    $client->setAccessToken($token);
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();
    
    // Check if user exists
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM users WHERE google_id = :google_id OR email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':google_id', $userInfo->id);
    $stmt->bindParam(':email', $userInfo->email);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create new user
        $query = "INSERT INTO users (name, email, google_id, role) VALUES (:name, :email, :google_id, 'customer')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $userInfo->name);
        $stmt->bindParam(':email', $userInfo->email);
        $stmt->bindParam(':google_id', $userInfo->id);
        $stmt->execute();
        
        $userId = $db->lastInsertId();
        $user = ['id' => $userId, 'name' => $userInfo->name, 'email' => $userInfo->email, 'role' => 'customer'];
    }
    
    // Generate token and redirect
    $token = base64_encode(json_encode(['user_id' => $user['id'], 'role' => $user['role']]));
    
    header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.html?auth=success&token=' . $token);
    exit;
} else {
    header('Location: ' . FRONTEND_DOMAIN . '/customer_dashboard.html?auth=error');
    exit;
}
?>
```

---

## XAMPP Configuration

### Step 1: Enable CORS

Add to your Apache `httpd.conf` or create `.htaccess` in `api` folder:

**File: `.htaccess`**
```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>
```

### Step 2: Enable PHP Extensions

In `php.ini`, uncomment or add:
```ini
extension=pdo_mysql
extension=openssl
extension=curl
extension=mbstring
```

### Step 3: Enable mod_rewrite

In `httpd.conf`, uncomment:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

### Step 4: Set Up Virtual Host (Optional)

Add to `httpd-vhosts.conf`:
```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/Project-sa-SOFE-main/public"
    ServerName jessie-cane.local
    <Directory "C:/xampp/htdocs/Project-sa-SOFE-main/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add to `hosts` file (`C:\Windows\System32\drivers\etc\hosts`):
```
127.0.0.1   jessie-cane.local
```

---

## Frontend Integration

### Update JavaScript Files

#### Update `public/customer/js/auth.js`

Replace localStorage login with API call:

```javascript
// Replace the login form handler
loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const emailInput = document.getElementById("email").value.trim();
    const passwordInput = document.getElementById("password").value;

    try {
        const response = await fetch('http://localhost/jessie-cane-api/api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: emailInput,
                password: passwordInput
            })
        });

        const data = await response.json();

        if (data.success) {
            localStorage.setItem("isLoggedIn", "true");
            localStorage.setItem("currentUser", JSON.stringify(data.data.user));
            localStorage.setItem("token", data.data.token);
            
            showToast('success', 'Welcome!', `Logged in as ${data.data.user.name}`);
            
            setTimeout(() => {
                window.location.href = 'customer_dashboard.html';
            }, 1000);
        } else {
            showPopup('error', { message: data.message });
        }
    } catch (error) {
        console.error('Login error:', error);
        showPopup('error', { message: 'Login failed. Please try again.' });
    }
});
```

#### Update Google Auth Button

```javascript
const googleLoginBtn = document.getElementById("googleLoginBtn");
if (googleLoginBtn) {
    googleLoginBtn.addEventListener("click", async () => {
        try {
            const response = await fetch('http://localhost/jessie-cane-api/api/auth/google-auth.php');
            const data = await response.json();
            
            if (data.success) {
                window.location.href = data.data.auth_url;
            }
        } catch (error) {
            console.error('Google auth error:', error);
        }
    });
}
```

#### Update `public/customer/js/drinks.js`

Replace checkout with GCash integration:

```javascript
async function confirmOrder() {
    const customerName = document.getElementById('customer-name-input').value.trim();
    const customerPhone = document.getElementById('customer-phone-input').value.trim();
    const branch = document.getElementById('branch-select-input').value;

    if (!customerName || !branch) {
        alert('Please fill in all required fields');
        return;
    }

    const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
    const orderData = {
        user_id: currentUser ? currentUser.id : null,
        customer_name: customerName,
        customer_username: currentUser ? currentUser.username : '',
        customer_email: currentUser ? currentUser.email : '',
        customer_phone: customerPhone,
        customer_notes: '',
        branch: branch,
        items: cart,
        subtotal: total,
        total: total
    };

    try {
        // Create order
        const orderResponse = await fetch('http://localhost/jessie-cane-api/api/orders/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });

        const orderResult = await orderResponse.json();

        if (!orderResult.success) {
            alert('Failed to create order: ' + orderResult.message);
            return;
        }

        // Initiate Xendit GCash payment
        const paymentResponse = await fetch('http://localhost/jessie-cane-api/api/payments/xendit-create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderResult.data.order_db_id,
                amount: total
            })
        });

        const paymentResult = await paymentResponse.json();

        if (paymentResult.success) {
            // Redirect to Xendit GCash payment page
            window.location.href = paymentResult.data.payment_url;
        } else {
            alert('Failed to initiate payment: ' + paymentResult.message);
        }
    } catch (error) {
        console.error('Checkout error:', error);
        alert('An error occurred during checkout');
    }
}
```

---

## Testing

### Test Checklist

1. **Database Connection**
   - ✓ phpMyAdmin accessible
   - ✓ Tables created successfully
   - ✓ Default data inserted

2. **Authentication**
   - ✓ Admin login works
   - ✓ Customer registration works
   - ✓ Customer login works
   - ✓ Google authentication works

3. **Products**
   - ✓ Fetch all products
   - ✓ Create product (admin)
   - ✓ Update product (admin)
   - ✓ Delete product (admin)

4. **Orders**
   - ✓ Create order
   - ✓ Fetch orders
   - ✓ Update order status

5. **Payments**
   - ✓ Xendit GCash payment link created
   - ✓ Xendit webhook received
   - ✓ Payment status updated

6. **Frontend Integration**
   - ✓ All API calls working
   - ✓ Error handling working
   - ✓ Loading states working

### Test URLs

- **Frontend:** `http://localhost/Project-sa-SOFE-main/public/customer/customer_dashboard.html`
- **API Base:** `http://localhost/jessie-cane-api/api/`
- **phpMyAdmin:** `http://localhost/phpmyadmin`

---

## Important Notes

1. **Security:**
   - Change default database password
   - Use environment variables for API keys
   - Implement JWT tokens for better security
   - Add input validation and sanitization
   - Use HTTPS in production

2. **Production Deployment:**
   - Update CORS origins to your domain
   - Use proper error logging
   - Implement rate limiting
   - Set up monitoring and alerts

3. **Backup:**
   - Regularly backup MySQL database
   - Keep environment configuration files secure

---

## Support

For issues or questions, contact the development team.

**Last Updated:** 2024

