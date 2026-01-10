<?php
/**
 * Admin Signup Handler
 */

require_once '../config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method'], 405);
}

$username = sanitizeInput($_POST['username'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    sendJSON(['success' => false, 'message' => 'All fields are required'], 400);
}

if ($password !== $confirm_password) {
    sendJSON(['success' => false, 'message' => 'Passwords do not match'], 400);
}

if (strlen($password) < 6) {
    sendJSON(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
}

$conn = getDBConnection();

if (!$conn) {
    sendJSON(['success' => false, 'message' => 'Database connection failed'], 500);
}

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    sendJSON(['success' => false, 'message' => 'Username already exists'], 409);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Create new user
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, status) VALUES (?, ?, ?, 'pending')");
$stmt->bind_param("sss", $username, $email, $password_hash);

if ($stmt->execute()) {
    sendJSON(['success' => true, 'message' => 'Account created and pending approval']);
} else {
    sendJSON(['success' => false, 'message' => 'Error creating account: ' . $conn->error], 500);
}

$stmt->close();
$conn->close();
?>
