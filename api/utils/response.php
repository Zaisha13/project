<?php
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    // Clear any output buffer to ensure clean JSON response
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
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

