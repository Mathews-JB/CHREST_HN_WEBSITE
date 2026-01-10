<?php
require_once '../config.php';

header('Content-Type: text/plain');

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed.\n");
}

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Truncate tables
$tables = ['projects', 'events'];

foreach ($tables as $table) {
    $sql = "TRUNCATE TABLE $table";
    if ($conn->query($sql) === TRUE) {
        echo "Table '$table' cleared successfully.\n";
    } else {
        echo "Error clearing table '$table': " . $conn->error . "\n";
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$conn->close();

echo "Cleanup completed.\n";
?>
