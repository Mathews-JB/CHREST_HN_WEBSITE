<?php
/**
 * Settings CRUD Handler
 */

require_once '../config.php';
require_once 'auth.php';
require_once '../services/FacebookService.php';

// Require authentication
requireAuth();

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_facebook_settings') {
        $settings = [
            'fb_page_id' => FacebookService::getSetting('fb_page_id'),
            'fb_access_token' => FacebookService::getSetting('fb_access_token')
        ];
        sendJSON(['success' => true, 'settings' => $settings]);
    } elseif ($action === 'test_connection') {
        $pageId = FacebookService::getSetting('fb_page_id');
        $accessToken = FacebookService::getSetting('fb_access_token');
        
        if (empty($pageId) || empty($accessToken)) {
            sendJSON(['success' => false, 'message' => 'Credentials not set']);
        }
        
        $url = "https://graph.facebook.com/v18.0/{$pageId}?fields=id,name,fan_count&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($httpCode === 200 && isset($data['id'])) {
            sendJSON([
                'success' => true, 
                'message' => 'Successfully connected to Facebook: ' . $data['name'],
                'data' => $data
            ]);
        } else {
            sendJSON([
                'success' => false, 
                'message' => 'Facebook API Error: ' . ($data['error']['message'] ?? 'Unknown error'),
                'raw' => $data
            ], 400);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_facebook_settings') {
        $pageId = sanitizeInput($_POST['fb_page_id'] ?? '');
        $accessToken = sanitizeInput($_POST['fb_access_token'] ?? '');
        
        if (empty($pageId) || empty($accessToken)) {
            sendJSON(['success' => false, 'message' => 'Both Page ID and Access Token are required'], 400);
        }
        
        $s1 = FacebookService::updateSetting('fb_page_id', $pageId);
        $s2 = FacebookService::updateSetting('fb_access_token', $accessToken);
        
        if ($s1 && $s2) {
            sendJSON(['success' => true, 'message' => 'Facebook settings updated successfully']);
        } else {
            sendJSON(['success' => false, 'message' => 'Failed to update settings in database'], 500);
        }
    }
}
?>
