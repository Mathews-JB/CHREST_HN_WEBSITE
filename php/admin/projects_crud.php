<?php
/**
 * Projects CRUD Operations
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

// GET - Fetch all projects
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM projects ORDER BY completion_date DESC";
    $result = $conn->query($sql);
    
    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    
    sendJSON(['success' => true, 'projects' => $projects]);
}

// POST - Create, Update, or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $title = sanitizeInput($_POST['title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $category = sanitizeInput($_POST['category'] ?? '');
            $technologies = sanitizeInput($_POST['technologies'] ?? '');
            $project_url = sanitizeInput($_POST['project_url'] ?? '');
            $completion_date = sanitizeInput($_POST['completion_date'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            // Handle image upload
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/projects/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = 'project_img_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = 'assets/projects/' . $file_name;
                }
            }

            // Handle video upload
            $video_url = '';
            if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/projects/videos/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                $file_name = 'project_vid_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                    $video_url = 'assets/projects/videos/' . $file_name;
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO projects (title, description, category, technologies, image_url, video_url, project_url, completion_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $title, $description, $category, $technologies, $image_url, $video_url, $project_url, $completion_date, $status);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;

                // Auto-post to Facebook with media
                $siteUrl = defined('SITE_URL') ? SITE_URL : '';
                $fbMessage = "💼 New Project: " . $title . " (" . $category . ")\n\n" . mb_strimwidth($description, 0, 200, "...");
                $fbLink = $siteUrl . "/projects.html";
                
                // Convert relative paths to absolute file paths for Facebook upload
                $absoluteImagePath = !empty($image_url) ? realpath('../../' . $image_url) : '';
                $absoluteVideoPath = !empty($video_url) ? realpath('../../' . $video_url) : '';
                
                // Post to Facebook
                FacebookService::postToPage($fbMessage, $fbLink, $absoluteImagePath, $absoluteVideoPath);

                sendJSON(['success' => true, 'message' => 'Project created successfully', 'id' => $newId]);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to create project: ' . $conn->error], 500);
            }
            $stmt->close();

        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $title = sanitizeInput($_POST['title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $category = sanitizeInput($_POST['category'] ?? '');
            $technologies = sanitizeInput($_POST['technologies'] ?? '');
            $project_url = sanitizeInput($_POST['project_url'] ?? '');
            $completion_date = sanitizeInput($_POST['completion_date'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            // Handle images
            $image_url = sanitizeInput($_POST['existing_image'] ?? '');
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/projects/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = 'project_img_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    if (!empty($image_url) && file_exists('../../' . $image_url)) unlink('../../' . $image_url);
                    $image_url = 'assets/projects/' . $file_name;
                }
            }

            // Handle videos
            $video_url = sanitizeInput($_POST['existing_video'] ?? '');
            if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/projects/videos/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                $file_name = 'project_vid_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                    if (!empty($video_url) && file_exists('../../' . $video_url)) unlink('../../' . $video_url);
                    $video_url = 'assets/projects/videos/' . $file_name;
                }
            }
            
            $stmt = $conn->prepare("UPDATE projects SET title=?, description=?, category=?, technologies=?, image_url=?, video_url=?, project_url=?, completion_date=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssssi", $title, $description, $category, $technologies, $image_url, $video_url, $project_url, $completion_date, $status, $id);
            
            if ($stmt->execute()) {
                sendJSON(['success' => true, 'message' => 'Project updated successfully']);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to update project: ' . $conn->error], 500);
            }
            $stmt->close();

        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            $stmt = $conn->prepare("SELECT image_url, video_url FROM projects WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $project = $result->fetch_assoc();
            
            $stmt = $conn->prepare("DELETE FROM projects WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                if (!empty($project['image_url']) && file_exists('../../' . $project['image_url'])) unlink('../../' . $project['image_url']);
                if (!empty($project['video_url']) && file_exists('../../' . $project['video_url'])) unlink('../../' . $project['video_url']);
                sendJSON(['success' => true, 'message' => 'Project deleted successfully']);
            } else {
                sendJSON(['success' => false, 'message' => 'Failed to delete project'], 500);
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
