<?php
/**
 * Events CRUD Operations
 */

require_once '../config.php';
require_once 'auth.php';
require_once '../services/FacebookService.php';

// Require authentication for all operations
requireAuth();

$conn = getDBConnection();
if (!$conn) {
    sendJSON(['success' => false, 'message' => 'Database connection failed'], 500);
}

// GET - Fetch all events
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM events ORDER BY event_date DESC";
    $result = $conn->query($sql);
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    sendJSON(['success' => true, 'events' => $events]);
}

// POST - Create, Update, or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $title = sanitizeInput($_POST['title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $event_date = sanitizeInput($_POST['event_date'] ?? '');
            $location = sanitizeInput($_POST['location'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            // Handle image upload
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/events/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = 'event_img_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = 'assets/events/' . $file_name;
                }
            }

            // Handle video upload
            $video_url = '';
            if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/events/videos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                $file_name = 'event_vid_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                    $video_url = 'assets/events/videos/' . $file_name;
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, image_url, video_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $title, $description, $event_date, $location, $image_url, $video_url, $status);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                
                // Auto-post to Facebook with media
                $siteUrl = defined('SITE_URL') ? SITE_URL : '';
                $fbMessage = "🎉 New Event: " . $title . "\n\n" . mb_strimwidth($description, 0, 200, "...");
                $fbLink = $siteUrl . "/events.html";
                
                // Convert relative paths to absolute file paths for Facebook upload
                $absoluteImagePath = !empty($image_url) ? realpath('../../' . $image_url) : '';
                $absoluteVideoPath = !empty($video_url) ? realpath('../../' . $video_url) : '';
                
                // Post to Facebook
                FacebookService::postToPage($fbMessage, $fbLink, $absoluteImagePath, $absoluteVideoPath);

                sendJSON(['success' => true, 'message' => 'Event created successfully', 'id' => $newId]);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to create event: ' . $conn->error], 500);
            }
            $stmt->close();

        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $title = sanitizeInput($_POST['title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $event_date = sanitizeInput($_POST['event_date'] ?? '');
            $location = sanitizeInput($_POST['location'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            // Handle images
            $image_url = sanitizeInput($_POST['existing_image'] ?? '');
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/events/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = 'event_img_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    if (!empty($image_url) && file_exists('../../' . $image_url)) unlink('../../' . $image_url);
                    $image_url = 'assets/events/' . $file_name;
                }
            }

            // Handle videos
            $video_url = sanitizeInput($_POST['existing_video'] ?? '');
            if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/events/videos/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                $file_name = 'event_vid_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                    if (!empty($video_url) && file_exists('../../' . $video_url)) unlink('../../' . $video_url);
                    $video_url = 'assets/events/videos/' . $file_name;
                }
            }
            
            $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=?, location=?, image_url=?, video_url=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssi", $title, $description, $event_date, $location, $image_url, $video_url, $status, $id);
            
            if ($stmt->execute()) {
                sendJSON(['success' => true, 'message' => 'Event updated successfully']);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to update event: ' . $conn->error], 500);
            }
            $stmt->close();

        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            $stmt = $conn->prepare("SELECT image_url, video_url FROM events WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $event = $result->fetch_assoc();
            
            $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                if (!empty($event['image_url']) && file_exists('../../' . $event['image_url'])) unlink('../../' . $event['image_url']);
                if (!empty($event['video_url']) && file_exists('../../' . $event['video_url'])) unlink('../../' . $event['video_url']);
                sendJSON(['success' => true, 'message' => 'Event deleted successfully']);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to delete event'], 500);
            }
            $stmt->close();

        } else {
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
        }
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Unknown column \'video_url\'') !== false) {
            $msg = "Database update required. Please visit: http://localhost/CHREST_HN_WEBSITE/php/migrate_video.php";
        }
        sendJSON(['success' => false, 'message' => 'Error: ' . $msg], 500);
    }
}

$conn->close();
?>
