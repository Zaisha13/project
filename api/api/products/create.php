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

// Require admin authentication
requireAuth('admin');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || !isset($data['price_regular']) || !isset($data['price_tall'])) {
    sendError('Name, price_regular, and price_tall are required');
}

$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : '';
$image = isset($data['image']) ? trim($data['image']) : '';
$priceRegular = floatval($data['price_regular']);
$priceTall = floatval($data['price_tall']);

$database = new Database();
$db = $database->getConnection();

$query = "INSERT INTO products (name, description, image, price_regular, price_tall) 
          VALUES (:name, :description, :image, :price_regular, :price_tall)";
$stmt = $db->prepare($query);

$stmt->bindParam(':name', $name);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':image', $image);
$stmt->bindParam(':price_regular', $priceRegular);
$stmt->bindParam(':price_tall', $priceTall);

if ($stmt->execute()) {
    $productId = $db->lastInsertId();
    
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    $product = $stmt->fetch();
    
    sendResponse(true, 'Product created successfully', $product);
} else {
    sendError('Failed to create product');
}
?>

