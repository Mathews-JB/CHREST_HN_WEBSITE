<?php
require_once 'config.php';

$conn = getDBConnection();

if (!$conn) {
    die("Connection failed");
}

echo "Starting migrations...<br>";

function addColumnIfNeeded($conn, $table, $column, $after) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` VARCHAR(500) AFTER `$after`";
        if ($conn->query($sql)) {
            echo "Successfully added '$column' to '$table' table.<br>";
        } else {
            echo "Error updating '$table' table: " . $conn->error . "<br>";
        }
    } else {
        echo "'$column' already exists in '$table' table.<br>";
    }
}

addColumnIfNeeded($conn, 'events', 'video_url', 'image_url');
addColumnIfNeeded($conn, 'projects', 'video_url', 'image_url');

echo "Migrations complete.";
?>
