<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google OAuth Test - ENTRY X</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #030303 0%, #0a0a0a 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .test-container {
            max-width: 800px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .warning {
            background: rgba(234, 179, 8, 0.2);
            color: #eab308;
        }

        .error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .test-section {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ff1f1f;
        }

        .test-section h3 {
            color: #ff1f1f;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .test-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .test-item:last-child {
            border-bottom: none;
        }

        .icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .check {
            color: #10b981;
        }

        .cross {
            color: #ef4444;
        }

        button,
        .button {
            background: linear-gradient(135deg, #ff1f1f 0%, #cc0000 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        button:hover,
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 31, 31, 0.3);
        }

        code {
            background: rgba(0, 0, 0, 0.5);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            color: #10b981;
        }

        .info-box {
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .info-box p {
            color: #94a3b8;
            line-height: 1.6;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="test-container">
        <h1>🔐 Google OAuth Test Panel</h1>
        <span class="status-badge success">✓ System Online</span>

        <div class="test-section">
            <h3>📋 Configuration Check</h3>
            <?php
            $configExists = file_exists('../config/google_config.php');

            if ($configExists) {
                require_once '../config/google_config.php';
                $clientIdValid = defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID_HERE';
                $secretValid = defined('GOOGLE_CLIENT_SECRET') && GOOGLE_CLIENT_SECRET !== 'YOUR_GOOGLE_CLIENT_SECRET_HERE';
                $redirectValid = defined('GOOGLE_REDIRECT_URL');
            } else {
                $clientIdValid = false;
                $secretValid = false;
                $redirectValid = false;
            }
            ?>

            <div class="test-item">
                <div class="icon <?php echo $configExists ? 'check' : 'cross'; ?>">
                    <?php echo $configExists ? '✓' : '✗'; ?>
                </div>
                <div>
                    <strong>google_config.php exists:</strong>
                    <?php echo $configExists ? 'Yes' : 'No'; ?>
                </div>
            </div>

            <div class="test-item">
                <div class="icon <?php echo $clientIdValid ? 'check' : 'cross'; ?>">
                    <?php echo $clientIdValid ? '✓' : '✗'; ?>
                </div>
                <div>
                    <strong>GOOGLE_CLIENT_ID configured:</strong>
                    <?php echo $clientIdValid ? 'Yes' : 'Not configured'; ?>
                </div>
            </div>

            <div class="test-item">
                <div class="icon <?php echo $secretValid ? 'check' : 'cross'; ?>">
                    <?php echo $secretValid ? '✓' : '✗'; ?>
                </div>
                <div>
                    <strong>GOOGLE_CLIENT_SECRET configured:</strong>
                    <?php echo $secretValid ? 'Yes' : 'Not configured'; ?>
                </div>
            </div>

            <div class="test-item">
                <div class="icon <?php echo $redirectValid ? 'check' : 'cross'; ?>">
                    <?php echo $redirectValid ? '✓' : '✗'; ?>
                </div>
                <div>
                    <strong>GOOGLE_REDIRECT_URL configured:</strong>
                    <?php echo $redirectValid ? GOOGLE_REDIRECT_URL : 'Not configured'; ?>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3>🔗 Authentication Endpoints</h3>

            <div class="test-item">
                <div class="icon check">✓</div>
                <div>
                    <strong>Student Login:</strong>
                    <code>/pages/user_login.php</code>
                </div>
            </div>

            <div class="test-item">
                <div class="icon check">✓</div>
                <div>
                    <strong>Admin Login:</strong>
                    <code>/pages/admin_login.php</code>
                </div>
            </div>

            <div class="test-item">
                <div class="icon check">✓</div>
                <div>
                    <strong>Google OAuth Initiate:</strong>
                    <code>/api/auth.php?action=google_login</code>
                </div>
            </div>

            <div class="test-item">
                <div class="icon check">✓</div>
                <div>
                    <strong>Google Callback:</strong>
                    <code>/api/auth.php?action=google_callback</code>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3>🚀 Quick Test</h3>
            <p style="color: #94a3b8; margin-bottom: 1rem;">
                Click the button below to test Google Sign-In. You'll be redirected to Google's consent screen.
            </p>

            <?php if ($clientIdValid && $secretValid && $redirectValid): ?>
                <a href="../api/auth.php?action=google_login" class="button">
                    🔗 Test Google Sign-In
                </a>
            <?php else: ?>
                <div class="status-badge error">
                    ⚠ Configuration incomplete. Please check google_config.php
                </div>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <p>
                <strong>ℹ️ Note:</strong> After successful Google authentication, you'll be redirected to the student
                dashboard.
                If you're testing with an admin email, the login will be blocked (admins must use password
                authentication).
            </p>
        </div>

        <div style="margin-top: 2rem; text-align: center;">
            <a href="../pages/user_login.php" style="color: #94a3b8; text-decoration: none;">
                ← Back to Student Login
            </a>
            <span style="color: #666; margin: 0 1rem;">|</span>
            <a href="../pages/admin_login.php" style="color: #94a3b8; text-decoration: none;">
                Admin Login →
            </a>
        </div>
    </div>
</body>

</html>