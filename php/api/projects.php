<?php
/**
 * Projects API Endpoint
 * Returns all active projects ordered by completion date
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../config.php';

$conn = getDBConnection();

if (!$conn) {
    sendJSON(['error' => 'Database connection failed'], 500);
}

// Fetch all active projects
$sql = "SELECT id, title, description, category, technologies, image_url, video_url, 
        project_url, completion_date, created_at 
        FROM projects 
        WHERE status = 'active' 
        ORDER BY completion_date DESC";

$result = $conn->query($sql);

if ($result === false) {
    sendJSON(['error' => 'Query failed: ' . $conn->error], 500);
}

$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

$conn->close();

sendJSON([
    'success' => true,
    'count' => count($projects),
    'projects' => $projects
]);
?>
