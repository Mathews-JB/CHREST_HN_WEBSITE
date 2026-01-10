<?php
/**
 * Admin Management CRUD
 */

require_once '../config.php';
require_once 'auth.php';

// Require authentication
requireAuth();

$conn = getDBConnection();
if (!$conn) {
    sendJSON(['success' => false, 'message' => 'Database connection failed'], 500);
}

// Handle GET - List all admins
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT id, username, email, status, created_at FROM admin_users ORDER BY created_at DESC");
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    sendJSON(['success' => true, 'admins' => $admins]);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if (!$id && $action !== 'send_link') {
        sendJSON(['success' => false, 'message' => 'Invalid ID'], 400);
    }

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE admin_users SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            sendJSON(['success' => true, 'message' => 'Admin approved successfully']);
        } else {
            sendJSON(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
        }
        $stmt->close();
    } 
    elseif ($action === 'reject') {
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ? AND username != 'admin'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            sendJSON(['success' => true, 'message' => 'Admin removed successfully']);
        } else {
            sendJSON(['success' => false, 'message' => 'Delete failed: ' . $conn->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'reset_password') {
        $new_password = 'password123';
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $id);
        if ($stmt->execute()) {
            sendJSON(['success' => true, 'message' => "Password reset to $new_password"]);
        } else {
            sendJSON(['success' => false, 'message' => 'Reset failed: ' . $conn->error]);
        }
        $stmt->close();
    }
    else {
        sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

$conn->close();
?>
