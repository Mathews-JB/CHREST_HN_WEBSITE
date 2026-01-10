<?php
/**
 * Facebook Graph API Service
 * Handles automatic posting to Facebook Pages with media support
 */

class FacebookService {
    /**
     * Post a message and link to a Facebook Page
     * 
     * @param string $message The content of the post
     * @param string $link Optional link to include in the post
     * @param string $imagePath Optional local path to image file
     * @param string $videoPath Optional local path to video file
     * @return array Response from Facebook API
     */
    public static function postToPage($message, $link = '', $imagePath = '', $videoPath = '') {
        // Try to get from database first, fallback to config.php
        $pageId = self::getSetting('fb_page_id') ?: (defined('FB_PAGE_ID') ? FB_PAGE_ID : '');
        $accessToken = self::getSetting('fb_access_token') ?: (defined('FB_ACCESS_TOKEN') ? FB_ACCESS_TOKEN : '');

        // Check if credentials are set
        if (empty($pageId) || empty($accessToken) || $accessToken === 'YOUR_ACCESS_TOKEN_HERE') {
            error_log("Facebook Auto-Post skipped: Credentials not configured");
            return ['success' => false, 'message' => 'Facebook credentials not configured'];
        }

        // If video is provided, post video
        if (!empty($videoPath) && file_exists($videoPath)) {
            return self::postVideo($pageId, $accessToken, $message, $videoPath);
        }
        
        // If image is provided, post photo
        if (!empty($imagePath) && file_exists($imagePath)) {
            return self::postPhoto($pageId, $accessToken, $message, $imagePath, $link);
        }
        
        // Otherwise, post text only
        return self::postText($pageId, $accessToken, $message, $link);
    }

    /**
     * Post text-only message
     */
    private static function postText($pageId, $accessToken, $message, $link = '') {
        $url = "https://graph.facebook.com/v18.0/{$pageId}/feed";
        
        $params = [
            'message' => $message,
            'access_token' => $accessToken
        ];

        // Only add link if it's not localhost (Facebook rejects localhost URLs)
        if (!empty($link) && !preg_match('/localhost|127\.0\.0\.1/i', $link)) {
            $params['link'] = $link;
        }

        return self::sendRequest($url, $params);
    }

    /**
     * Post photo with caption
     */
    private static function postPhoto($pageId, $accessToken, $message, $imagePath, $link = '') {
        $url = "https://graph.facebook.com/v18.0/{$pageId}/photos";
        
        // Create CURLFile for image upload
        $cfile = new CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath));
        
        $params = [
            'message' => $message,
            'source' => $cfile,
            'access_token' => $accessToken
        ];

        // Note: Facebook doesn't support links in photo posts via API
        // The link would need to be included in the message text

        return self::sendRequest($url, $params, true);
    }

    /**
     * Post video with description
     */
    private static function postVideo($pageId, $accessToken, $message, $videoPath) {
        $url = "https://graph.facebook.com/v18.0/{$pageId}/videos";
        
        // Create CURLFile for video upload
        $cfile = new CURLFile($videoPath, mime_content_type($videoPath), basename($videoPath));
        
        $params = [
            'description' => $message,
            'source' => $cfile,
            'access_token' => $accessToken
        ];

        // Video uploads may take longer
        return self::sendRequest($url, $params, true, 120);
    }

    /**
     * Send HTTP request to Facebook API
     */
    private static function sendRequest($url, $params, $isMultipart = false, $timeout = 30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        if ($isMultipart) {
            // For file uploads, don't use http_build_query
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode === 200 && isset($responseData['id'])) {
            error_log("Facebook Post Success: ID " . $responseData['id']);
            return ['success' => true, 'post_id' => $responseData['id']];
        } else {
            error_log("Facebook API Error: " . $response);
            return [
                'success' => false, 
                'message' => $responseData['error']['message'] ?? 'Unknown Facebook API error',
                'raw' => $responseData
            ];
        }
    }

    /**
     * Helper to get setting from database
     */
    public static function getSetting($key) {
        try {
            $conn = getDBConnection();
            if (!$conn) return null;
            
            $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $conn->close();
            
            return $row ? $row['setting_value'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Helper to update setting in database
     */
    public static function updateSetting($key, $value) {
        try {
            $conn = getDBConnection();
            if (!$conn) return false;
            
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $success = $stmt->execute();
            $stmt->close();
            $conn->close();
            
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
