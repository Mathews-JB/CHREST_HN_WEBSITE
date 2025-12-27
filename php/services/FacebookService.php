<?php
/**
 * Facebook Graph API Service
 * Handles automatic posting to Facebook Pages
 */

class FacebookService {
    /**
     * Post a message and link to a Facebook Page
     * 
     * @param string $message The content of the post
     * @param string $link Optional link to include in the post
     * @return array Response from Facebook API
     */
    public static function postToPage($message, $link = '') {
        $pageId = defined('FB_PAGE_ID') ? FB_PAGE_ID : '';
        $accessToken = defined('FB_ACCESS_TOKEN') ? FB_ACCESS_TOKEN : '';

        // Check if credentials are set
        if (empty($pageId) || empty($accessToken) || $accessToken === 'YOUR_ACCESS_TOKEN_HERE') {
            error_log("Facebook Auto-Post skipped: Credentials not configured in config.php");
            return ['success' => false, 'message' => 'Facebook credentials not configured'];
        }

        $url = "https://graph.facebook.com/v18.0/{$pageId}/feed";
        
        $params = [
            'message' => $message,
            'access_token' => $accessToken
        ];

        if (!empty($link)) {
            $params['link'] = $link;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development simplicity

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode === 200 && isset($responseData['id'])) {
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
}
?>
