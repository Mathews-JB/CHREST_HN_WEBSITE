<?php
/**
 * Admin Signup Link / Invite Handler
 */

require_once '../config.php';
require_once 'auth.php';

// Require authentication for sending invites
requireAuth();

$action = $_POST['action'] ?? '';
$email = sanitizeInput($_POST['email'] ?? '');

if (empty($email)) {
    sendJSON(['success' => false, 'message' => 'Email is required'], 400);
}

$signup_link = SITE_URL . "/admin/signup.html?email=" . urlencode($email);

// CRITICAL: Get absolute path for the logo
$logo_path = realpath(__DIR__ . '/../../assets/logo.jpg');
$has_logo = ($logo_path && file_exists($logo_path) && is_readable($logo_path));

if (!$has_logo) {
    error_log("Email Logo Error: Logo not found or not readable at " . (__DIR__ . '/../../assets/logo.jpg'));
}

$subject = "Admin Invitation - CREST HN Admin Panel";

// Generate a unique boundary
$boundary = "PHP-alt-" . md5(time());

// Headers
$headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
$headers .= "Reply-To: " . SMTP_FROM . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/related; boundary=\"$boundary\"\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// HTML Content
$html_content = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Outfit', 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e0e0e0; }
        .header { background: #111111; padding: 40px 20px; text-align: center; }
        .logo { max-width: 150px; height: auto; border-radius: 8px; }
        .content { padding: 40px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #888; background: #f9f9f9; }
        .btn { display: inline-block; padding: 14px 30px; background-color: #7214ff; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 25px 0; }
        .access-code { background: #f0f0f0; padding: 10px; border-radius: 5px; font-family: monospace; font-weight: bold; font-size: 1.1em; color: #7214ff; border: 1px dashed #7214ff; }
        h1 { font-size: 24px; color: #1a1a1a; margin-top: 0; }
        p { margin-bottom: 20px; color: #555; }
        .logo-text { color: #ffffff; font-size: 24px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            " . ($has_logo ? "<img src='cid:crest_logo_img' alt='CREST HN' class='logo'>" : "<div class='logo-text'>CREST HN</div>") . "
        </div>
        <div class='content'>
            <h1>Administrator Invitation</h1>
            <p>Hello,</p>
            <p>You have been invited to join the <strong>CREST HN Admin Panel</strong>. As an administrator, you will be able to manage events, projects, and site settings.</p>
            
            <p>To register your account, click the button below:</p>
            <div style='text-align: center;'>
                <a href='{$signup_link}' class='btn'>Register as Administrator</a>
            </div>
            
            <p><strong>Verification Code:</strong> To access the admin login in the future, use this code in the portal footer:</p>
            <p style='text-align: center;'><span class='access-code'>160901350006</span></p>
            
            <p>Regards,<br><strong>CREST HN Team</strong></p>
        </div>
        <div class='footer'>
            &copy; " . date('Y') . " CREST HN TECH AND INNOVATIONS.
        </div>
    </div>
</body>
</html>";

// Build message parts
$message = "--$boundary\r\n";
$message .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= $html_content . "\r\n\r\n";

if ($has_logo) {
    $file_content = file_get_contents($logo_path);
    $encoded_content = chunk_split(base64_encode($file_content));
    
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: image/jpeg; name=\"logo.jpg\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-ID: <crest_logo_img>\r\n";
    $message .= "Content-Disposition: inline; filename=\"logo.jpg\"\r\n\r\n";
    $message .= $encoded_content . "\r\n";
}

$message .= "--$boundary--";

// Send email
$mail_sent = @mail($email, $subject, $message, $headers);

if ($mail_sent) {
    sendJSON(['success' => true, 'message' => 'Invitation sent successfully. Check your email for the branded logo.']);
} else {
    sendJSON(['success' => false, 'message' => 'Failed to send email. Verify your server SMTP configuration.']);
}
?>
