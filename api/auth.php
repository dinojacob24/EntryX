<?php
// Set session cookie path once at the top to ensure visibility across directories
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/Project/EntryX');
}

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Mailer.php';

// Enable error logging for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/auth_error.log');

header('Content-Type: application/json');

$user = new User($pdo);
$action = $_GET['action'] ?? '';

if ($action === 'register') {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    // --- Server-side Validation ---
    $name = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'external';
    $phone = trim(htmlspecialchars($_POST['phone'] ?? ''));
    $college_id = trim(htmlspecialchars($_POST['college_id'] ?? ''));
    $id_proof_path = null;

    // 1. Basic Empty Checks
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
        exit;
    }

    // 2. Email Format Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
        exit;
    }

    // 3. Password Match Check
    if (!empty($confirmPassword) && $password !== $confirmPassword) {
        echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
        exit;
    }

    // 4. Password Strength Check (Serverside)
    // At least 8 chars, 1 uppercase, 1 lowercase, 1 number
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.']);
        exit;
    }

    // Handle File Upload for External Users
    if ($role === 'external') {
        if (!isset($_FILES['id_proof']) || $_FILES['id_proof']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'ID Proof is required for external participants.']);
            exit;
        }

        $uploadDir = '../assets/uploads/id_proofs/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        // Professional file naming (sanitized)
        $fileExt = strtolower(pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($fileExt, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Only JPG, PNG and PDF files are allowed.']);
            exit;
        }

        if ($_FILES['id_proof']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File size must be under 2MB.']);
            exit;
        }

        $fileName = 'id_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['id_proof']['tmp_name'], $targetPath)) {
            $id_proof_path = 'assets/uploads/id_proofs/' . $fileName;
        } else {
            echo json_encode(['success' => false, 'error' => 'Critical: Failed to save ID proof.']);
            exit;
        }
    }

    $data = [
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'role' => $role,
        'phone' => $phone,
        'college_id' => $role === 'internal' ? $college_id : null,
        'id_proof' => $id_proof_path
    ];

    $result = $user->register($data);
    echo json_encode($result);
    exit;
}

if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    $result = $user->login($email, $password);
    echo json_encode($result);
    exit;
}

if ($action === 'logout') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    header('Location: ../index.php');
    exit;
}

if ($action === 'delete_user') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
        exit;
    }

    $userIdToRevoke = $_GET['id'] ?? null;
    if (!$userIdToRevoke) {
        echo json_encode(['success' => false, 'error' => 'User ID is required']);
        exit;
    }

    try {
        // Only allow super_admin to delete other admins/security (not themselves)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('event_admin', 'security')");
        if ($stmt->execute([$userIdToRevoke])) {
            echo json_encode(['success' => true, 'message' => 'Access revoked successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to revoke access or user not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

try {
    if ($action === 'forgot_password') {
        $email = $_POST['email'] ?? '';
        $token = $user->createResetToken($email);

        if ($token) {
            $resetLink = "/Project/EntryX/pages/reset_password.php?token=" . $token;

            // Try to Send Email
            $emailSent = Mailer::sendResetEmail($email, $resetLink);

            // For Development/Presentation: Always return success if token exists
            echo json_encode([
                'success' => true,
                'email_success' => $emailSent,
                'message' => $emailSent
                    ? 'Reset link has been sent to your email address.'
                    : 'System is in presentation mode. Please use the link below.',
                'debug_link' => $resetLink
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Email not found in our records.']);
        }
        exit;
    }

    if ($action === 'reset_password') {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['password'] ?? '';

        if (empty($token) || empty($newPassword)) {
            echo json_encode(['success' => false, 'error' => 'Missing token or password']);
            exit;
        }

        if ($user->resetPassword($token, $newPassword)) {
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
        }
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System Error: ' . $e->getMessage()]);
    exit;
}

if ($action === 'mock_google_login') {
    // SIMULATED GOOGLE LOGIN for Demo/Testing
    // This bypasses the actual Google API to avoid configuration issues during presentation
    $fakeGoogleId = 'demo_google_12345';
    $fakeEmail = 'demo.student@gmail.com';
    $fakeName = 'Demo Student';

    if ($user->createOrLoginGoogleUser($fakeGoogleId, $fakeEmail, $fakeName)) {
        header('Location: ../pages/dashboard.php');
    } else {
        header('Location: ../pages/login.php?error=mock_login_failed');
    }
    exit;
}

if ($action === 'google_login') {
    if (file_exists('../config/google_config.php')) {
        require_once '../config/google_config.php';
    } else {
        require_once '../config/google_config.example.php';
    }

    if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID_HERE') {
        die("Error: Google Client ID not configured. Please check config/google_config.php");
    }

    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URL,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    exit;
}

if ($action === 'google_callback') {
    if (file_exists('../config/google_config.php')) {
        require_once '../config/google_config.php';
    } else {
        require_once '../config/google_config.example.php';
    }

    if (isset($_GET['code'])) {
        $code = $_GET['code'];

        // Exchange code for Access Token
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URL,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        if (isset($data['access_token'])) {
            $accessToken = $data['access_token'];

            // Get User Info
            $userUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $accessToken;
            $ch = curl_init($userUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $userResponse = curl_exec($ch);
            $googleUser = json_decode($userResponse, true);
            curl_close($ch);

            if (isset($googleUser['id'])) {
                $googleId = $googleUser['id'];
                $email = $googleUser['email'];
                $name = $googleUser['name'];
                $picture = $googleUser['picture'] ?? null;

                if ($user->createOrLoginGoogleUser($googleId, $email, $name, $picture)) {
                    header('Location: ../pages/student_dashboard.php');
                    exit;
                } else {
                    header('Location: ../pages/user_login.php?error=linking_failed');
                    exit;
                }
            }
        }
    }
    header('Location: ../pages/user_login.php?error=auth_failed');
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);