<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $code = $data['code'] ?? '';

    // The backend code for admin access redirection
    $ADMIN_ACCESS_CODE = "160901350006"; 

    if ($code === $ADMIN_ACCESS_CODE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid access code']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
