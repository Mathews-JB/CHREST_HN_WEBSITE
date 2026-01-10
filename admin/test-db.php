<?php
/**
 * Database Connection Test
 * This file tests if the database connection is working properly
 */

require_once '../php/config.php';

header('Content-Type: application/json');

$response = [
    'database_connection' => false,
    'tables_exist' => false,
    'admin_user_exists' => false,
    'errors' => [],
    'details' => []
];

try {
    // Test database connection
    $conn = getDBConnection();
    
    if ($conn === null) {
        $response['errors'][] = 'Failed to connect to database';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    $response['database_connection'] = true;
    $response['details'][] = 'Database connection successful';
    
    // Check if required tables exist
    $tables = ['admin_users', 'events', 'projects'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $existingTables[] = $table;
        }
    }
    
    if (count($existingTables) === 3) {
        $response['tables_exist'] = true;
        $response['details'][] = 'All required tables exist: ' . implode(', ', $existingTables);
    } else {
        $response['errors'][] = 'Missing tables: ' . implode(', ', array_diff($tables, $existingTables));
    }
    
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT id, username FROM admin_users WHERE username = ?");
    $username = 'admin';
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['admin_user_exists'] = true;
        $response['details'][] = 'Admin user exists';
    } else {
        $response['errors'][] = 'Admin user not found - you may need to re-import the SQL file';
    }
    
    $stmt->close();
    
    // Get counts
    $eventsCount = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
    $projectsCount = $conn->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc()['count'];
    
    $response['details'][] = "Events in database: $eventsCount";
    $response['details'][] = "Projects in database: $projectsCount";
    
    $conn->close();
    
} catch (Exception $e) {
    $response['errors'][] = 'Exception: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
