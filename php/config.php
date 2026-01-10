<?php
/**
 * CREST HN TECH AND INNOVATIONS
 */
// Facebook API Settings
define('FB_PAGE_ID', '848707248318831');
define('FB_ACCESS_TOKEN', 'EAAT1mpTpGOoBQfDdwF5mtikSIXs4jsyIdIZCwQwBxcgZAg1BVoZBqZACVb9R9zVYt830WOU4jv3xJS0sUtqOHGefZBDslZCzpXnkZBI6ZCDZAoyrjh2gkm9QPnwTF4OwMuE2kZACcSZAgjetL9tmakbDnHpkPOZBFxH6QPLuNiMbyukWklTEZAYOgplXmhZAkxYVZBq9wJkLQGcBMHuZBC06i5ZAsDJ7vcTRWI3YmBXwbJAw4DD4DEMeC');
define('SITE_URL', 'http://localhost/CREST'); // Used for Facebook post links and email invitations

/**
 * Database Connection
 */

// Database credentials - UPDATE THESE WITH YOUR ACTUAL DATABASE DETAILS
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'PROJECTEVENTS');

/**
 * Email Configuration (SMTP)
 */
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'bmathews679@gmail.com');
define('SMTP_PASS', 'qjhu omcx mjeb gxhy');
define('SMTP_FROM', 'bmathews679@gmail.com');
define('SMTP_FROM_NAME', 'CREST HN Admin');


// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Helper function to send JSON response
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Start session for admin authentication
session_start();
?>
