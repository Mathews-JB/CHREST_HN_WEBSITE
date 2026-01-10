<?php
/**
 * Events API Endpoint
 * Returns all active events ordered by event date
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../config.php';

$conn = getDBConnection();

if (!$conn) {
    sendJSON(['error' => 'Database connection failed'], 500);
}

// Check if we should show all or only active
$showAll = isset($_GET['all']) && $_GET['all'] === 'true';
$whereClause = $showAll ? "" : "WHERE status = 'active'";

// Fetch events
$sql = "SELECT id, title, description, event_date, location, image_url, video_url, status, created_at 
        FROM events 
        $whereClause
        ORDER BY event_date ASC";

$result = $conn->query($sql);

if ($result === false) {
    sendJSON(['error' => 'Query failed: ' . $conn->error], 500);
}

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

$conn->close();

sendJSON([
    'success' => true,
    'count' => count($events),
    'events' => $events
]);
?>
