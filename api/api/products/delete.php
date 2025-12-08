<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
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

// Handle both DELETE and POST (for better compatibility)
$id = $_GET['id'] ?? $_POST['id'] ?? null;

// Try to get from JSON body if available
if (!$id) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
}

if (!$id) {
    sendError('Product ID is required');
}

$database = new Database();
$db = $database->getConnection();

// Check if product exists
$query = "SELECT * FROM products WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

$product = $stmt->fetch();

if (!$product) {
    sendError('Product not found', 404);
}

// Delete product
$query = "DELETE FROM products WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);

if ($stmt->execute()) {
    sendResponse(true, 'Product deleted successfully');
} else {
    sendError('Failed to delete product');
}
?>

