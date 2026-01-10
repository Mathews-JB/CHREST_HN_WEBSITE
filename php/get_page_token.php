<?php
/**
 * Facebook Page Token Fetcher
 * Retrieves page access tokens from a user access token
 * 
 * USAGE: Navigate to http://localhost/CREST/php/get_page_token.php
 * SECURITY: Delete this file after getting your token!
 */

header('Content-Type: text/html; charset=utf-8');

$userToken = '';
$pageTokens = [];
$error = null;

// Check if token was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['user_token'])) {
    $userToken = trim($_POST['user_token']);
    
    // Fetch pages associated with this user token
    $url = "https://graph.facebook.com/v18.0/me/accounts?access_token=" . urlencode($userToken);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['data'])) {
        $pageTokens = $data['data'];
    } else {
        $error = $data['error']['message'] ?? 'Failed to fetch pages. Please check your token.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Page Token Fetcher - CREST HN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 0.9rem;
            font-family: 'Courier New', monospace;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .page-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .page-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .page-card.target {
            border-color: #28a745;
            background: #d4edda;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .page-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
        }
        .page-id {
            font-size: 0.85rem;
            color: #666;
            font-family: 'Courier New', monospace;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge.success {
            background: #28a745;
            color: white;
        }
        .badge.info {
            background: #17a2b8;
            color: white;
        }
        .token-display {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 12px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            word-break: break-all;
            margin-bottom: 10px;
            position: relative;
        }
        .copy-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .copy-btn:hover {
            background: #218838;
        }
        .copy-btn:active {
            transform: scale(0.95);
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
        }
        .warning-box strong {
            color: #856404;
        }
        .instructions {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .instructions h3 {
            color: #1976D2;
            margin-bottom: 10px;
        }
        .instructions ol {
            margin-left: 20px;
            color: #555;
        }
        .instructions li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Facebook Page Token Fetcher</h1>
        <p class="subtitle">CREST HN TECH AND INNOVATIONS</p>

        <?php if (empty($pageTokens) && !$error): ?>
            <div class="instructions">
                <h3>📋 Instructions:</h3>
                <ol>
                    <li>Go to <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a></li>
                    <li>Select your app: <strong>AccessMathew</strong></li>
                    <li>Add permissions: <code>pages_show_list</code>, <code>pages_read_engagement</code>, <code>pages_manage_posts</code></li>
                    <li>Click "Generate Access Token" and approve</li>
                    <li>Copy the token and paste it below</li>
                </ol>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="user_token">Paste Your User Access Token:</label>
                    <input type="text" id="user_token" name="user_token" placeholder="EAAT1mpTpGOoBQW..." required>
                </div>
                <button type="submit" class="btn">🔍 Fetch Page Tokens</button>
            </form>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="?" class="btn">← Try Again</a>
        <?php endif; ?>

        <?php if (!empty($pageTokens)): ?>
            <div class="info-box">
                <strong>✅ Success!</strong> Found <?php echo count($pageTokens); ?> page(s). 
                Look for your CREST HN page (ID: 848707248318831) below.
            </div>

            <?php foreach ($pageTokens as $page): ?>
                <?php $isTarget = ($page['id'] === '848707248318831'); ?>
                <div class="page-card <?php echo $isTarget ? 'target' : ''; ?>">
                    <div class="page-header">
                        <div>
                            <div class="page-name">
                                <?php echo htmlspecialchars($page['name']); ?>
                                <?php if ($isTarget): ?>
                                    <span class="badge success">🎯 Your Page</span>
                                <?php endif; ?>
                            </div>
                            <div class="page-id">Page ID: <?php echo htmlspecialchars($page['id']); ?></div>
                        </div>
                        <span class="badge info"><?php echo htmlspecialchars($page['category'] ?? 'Page'); ?></span>
                    </div>

                    <label style="font-size: 0.9rem; margin-bottom: 5px; display: block;">
                        <?php echo $isTarget ? '✨ Use This Token:' : 'Access Token:'; ?>
                    </label>
                    <div class="token-display" id="token-<?php echo $page['id']; ?>">
                        <?php echo htmlspecialchars($page['access_token']); ?>
                    </div>
                    <button class="copy-btn" onclick="copyToken('<?php echo $page['id']; ?>')">
                        📋 Copy Token
                    </button>

                    <?php if ($isTarget): ?>
                        <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 5px; border: 1px solid #28a745;">
                            <strong style="color: #28a745;">✅ Next Step:</strong>
                            <ol style="margin: 10px 0 0 20px; color: #555;">
                                <li>Click "Copy Token" above</li>
                                <li>Open <code>c:\xampp\htdocs\CREST\php\config.php</code></li>
                                <li>Replace line 7 with the new token</li>
                                <li>Test at <a href="test_facebook.php">test_facebook.php</a></li>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <a href="?" class="btn" style="margin-top: 20px;">← Fetch Another Token</a>
        <?php endif; ?>

        <div class="warning-box">
            <strong>⚠️ Security Warning:</strong> Delete this file after getting your token. 
            Run: <code>del c:\xampp\htdocs\CREST\php\get_page_token.php</code>
        </div>
    </div>

    <script>
        function copyToken(pageId) {
            const tokenElement = document.getElementById('token-' + pageId);
            const token = tokenElement.textContent.trim();
            
            navigator.clipboard.writeText(token).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✅ Copied!';
                btn.style.background = '#218838';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '#28a745';
                }, 2000);
            }).catch(err => {
                alert('Failed to copy. Please select and copy manually.');
            });
        }
    </script>
</body>
</html>
