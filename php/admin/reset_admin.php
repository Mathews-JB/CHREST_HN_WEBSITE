<?php
/**
 * Admin Password Reset Script
 * 
 * This script will:
 * 1. Check if the 'admin' user exists
 * 2. If yes, update password to 'admin123'
 * 3. If no, create the user with password 'admin123'
 */

require_once '../config.php';

header('Content-Type: text/plain');

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed.\n");
}

$username = 'admin';
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Check if user exists
$checkSql = "SELECT id FROM admin_users WHERE username = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing user
    $updateSql = "UPDATE admin_users SET password_hash = ? WHERE username = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ss", $hash, $username);
    
    if ($updateStmt->execute()) {
        echo "SUCCESS: Password for user '$username' has been reset to '$password'.\n";
    } else {
        echo "ERROR: Failed to update password. " . $conn->error . "\n";
    }
    $updateStmt->close();
} else {
    // Create new user
    $insertSql = "INSERT INTO admin_users (username, password_hash) VALUES (?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("ss", $username, $hash);
    
    if ($insertStmt->execute()) {
        echo "SUCCESS: User '$username' was created with password '$password'.\n";
    } else {
        echo "ERROR: Failed to create user. " . $conn->error . "\n";
    }
    $insertStmt->close();
}

$stmt->close();
$conn->close();

echo "\nYou can now delete this script and log in at admin/login.html";
?>
