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

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM products ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute();

$products = $stmt->fetchAll();

// Update image paths to full URLs (relative to project root)
foreach ($products as &$product) {
    if ($product['image']) {
        // If already a full URL, keep it; otherwise make it relative
        if (!str_starts_with($product['image'], 'http://') && !str_starts_with($product['image'], 'https://')) {
            $product['image'] = '../../assets/images/' . $product['image'];
        }
    }
}

sendResponse(true, 'Products retrieved successfully', $products);
?>

