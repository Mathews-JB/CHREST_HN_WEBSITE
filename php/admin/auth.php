<?php
/**
 * Admin Authentication Handler
 */

require_once '../config.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        sendJSON(['success' => false, 'message' => 'Username and password are required'], 400);
    }
    
    $conn = getDBConnection();
    if (!$conn) {
        sendJSON(['success' => false, 'message' => 'Database connection failed'], 500);
    }
    
    $stmt = $conn->prepare("SELECT id, username, password_hash, status FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['status'] !== 'approved') {
            sendJSON(['success' => false, 'message' => 'Your account is pending approval'], 403);
        }
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            
            sendJSON(['success' => true, 'message' => 'Login successful']);
        } else {
            sendJSON(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid credentials'], 401);
    }
    
    $stmt->close();
    $conn->close();
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_destroy();
    sendJSON(['success' => true, 'message' => 'Logged out successfully']);
}

// Check authentication status
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        sendJSON(['authenticated' => true, 'username' => $_SESSION['admin_username']]);
    } else {
        sendJSON(['authenticated' => false]);
    }
}

// Helper function to check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Helper function to require authentication
function requireAuth() {
    if (!isAuthenticated()) {
        error_log("Auth Failed. Session ID: " . session_id() . ", Logged in: " . (isset($_SESSION['admin_logged_in']) ? 'YES' : 'NO'));
        sendJSON(['success' => false, 'message' => 'Authentication required'], 401);
    }
}
?>
