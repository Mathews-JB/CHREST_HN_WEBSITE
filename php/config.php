<?php
/**
 * CREST HN TECH AND INNOVATIONS
 */
// Facebook API Settings
define('FB_PAGE_ID', '848707248318831');
define('FB_ACCESS_TOKEN', 'EAAT1mpTpGOoBQWHz6xGZBc4UkMrURXzqBUeCg8VtGSvNhENP6uBdEAcqZAmlF7GJLTS3YsAJ5x1Ymc9KvS86B25HyTKieFFWvsndXwiAtX3lJvyQmMe6Ra2wTrEeX197KYm5y1WYohkNgdEvjrIMDW8ZCPnI6EkAEuPMRZALFQA4QzNIHjLNStgZAATGQ5ztN6ZC33iPvvN9r0FiQM3bHzWegrX8zFbQv5ZAWsTm3N6o0CONE7edZAfEamesAyojd1ictbkx5og5JIPstMZAmgc8t0awB');
define('SITE_URL', 'http://localhost/CHREST_HN_WEBSITE'); // Used for Facebook post links

/**
 * Database Connection
 */

// Database credentials - UPDATE THESE WITH YOUR ACTUAL DATABASE DETAILS
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'PROJECTEVENTS');

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
