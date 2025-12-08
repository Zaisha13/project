<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    sendError('Product ID is required');
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM products WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

$product = $stmt->fetch();

if (!$product) {
    sendError('Product not found', 404);
}

// Update image path
if ($product['image'] && !str_starts_with($product['image'], 'http://') && !str_starts_with($product['image'], 'https://')) {
    $product['image'] = '../../assets/images/' . $product['image'];
}

sendResponse(true, 'Product retrieved successfully', $product);
?>

