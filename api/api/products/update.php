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
require_once __DIR__ . '/../../utils/auth-helpers.php';

// Require admin authentication
requireAuth('admin');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    sendError('Product ID is required');
}

$id = intval($data['id']);
$name = isset($data['name']) ? trim($data['name']) : null;
$description = isset($data['description']) ? trim($data['description']) : null;
$image = isset($data['image']) ? trim($data['image']) : null;
$priceRegular = isset($data['price_regular']) ? floatval($data['price_regular']) : null;
$priceTall = isset($data['price_tall']) ? floatval($data['price_tall']) : null;

$database = new Database();
$db = $database->getConnection();

// Build update query dynamically based on provided fields
$updateFields = [];
$params = [':id' => $id];

if ($name !== null) {
    $updateFields[] = "name = :name";
    $params[':name'] = $name;
}
if ($description !== null) {
    $updateFields[] = "description = :description";
    $params[':description'] = $description;
}
if ($image !== null) {
    $updateFields[] = "image = :image";
    $params[':image'] = $image;
}
if ($priceRegular !== null) {
    $updateFields[] = "price_regular = :price_regular";
    $params[':price_regular'] = $priceRegular;
}
if ($priceTall !== null) {
    $updateFields[] = "price_tall = :price_tall";
    $params[':price_tall'] = $priceTall;
}

if (empty($updateFields)) {
    sendError('No fields to update');
}

$query = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = :id";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

if ($stmt->execute()) {
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $product = $stmt->fetch();
    
    if (!$product) {
        sendError('Product not found', 404);
    }
    
    sendResponse(true, 'Product updated successfully', $product);
} else {
    sendError('Failed to update product');
}
?>

