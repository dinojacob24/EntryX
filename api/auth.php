<?php
require_once '../config/db.php';
session_start();

// Load Secrets (Ignored by Git)
if (file_exists('../config/secrets.php')) {
    require_once '../config/secrets.php';
} else {
    // Fallback or Error if secrets missing
    die(json_encode(['error' => 'Server Config Error: Secrets missing.']));
}

// Prevent HTML errors breaking JSON
ini_set('display_errors', 0);
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data)
        $data = $_POST;

    if ($action === 'register') {
        $name = $data['name'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role']; // 'internal' or 'external' or 'faculty'
        $student_id = $data['student_id'] ?? null;

        // Validation: Block Admin Registration
        if ($role === 'admin') {
            die(json_encode(['error' => 'Admin registration is restricted.']));
        }
        if (($role === 'internal' || $role === 'faculty') && empty($student_id)) {
            die(json_encode(['error' => 'ID is required.']));
        }

        if (empty($student_id))
            $student_id = null;

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, student_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role, $student_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['error' => 'One account already exists with this email address. Please login.']);
            } else {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        }

    } elseif ($action === 'login') {
        $email = $data['email'];
        $password = $data['password'];

        // MASTER ADMIN CHECK
        if ($email === MASTER_ADMIN_EMAIL && $password === MASTER_ADMIN_PASS) {
            $_SESSION['user_id'] = MASTER_ADMIN_ID;
            $_SESSION['role'] = 'admin';
            $_SESSION['name'] = 'Master Admin';
            echo json_encode(['success' => true, 'role' => 'admin']);
            exit;
        }

        // DATABASE CHECK
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Optional: Block DB Admins if we ONLY want Master Admin functionality
            // user request: "only the rolebased admins can join the admin login" -> could imply DB admins also ok if role matches.
            // But "fixed mail ID and password" implies restriction.
            // Let's allow DB access normally, but the user specifically asked for a fixed credential. 
            // The Master Admin above guarantees the "fixed" part works.

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            echo json_encode(['success' => true, 'role' => $user['role']]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } elseif ($action === 'google_login') {
        $token = $data['token'];

        // Verify ID Token with Google
        $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $payload = json_decode($response, true);

        if (isset($payload['email'])) {
            $email = $payload['email'];
            $name = $payload['name'];
            $google_id = $payload['sub'];

            // Check if User Exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // User Exists -> Update google_id if missing
                if (empty($user['google_id'])) {
                    $stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                    $stmt->execute([$google_id, $user['id']]);
                }
            } else {
                // Register New User (Default to External)
                // If email domain is college, maybe internal? For now, External.
                $stmt = $pdo->prepare("INSERT INTO users (name, email, role, google_id) VALUES (?, ?, 'external', ?)");
                $stmt->execute([$name, $email, $google_id]);

                // Fetch newly created user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
            }

            // Session Login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            echo json_encode(['success' => true, 'role' => $user['role']]);

        } else {
            echo json_encode(['error' => 'Invalid Google Token']);
        }
    }
} elseif ($action === 'logout') {
    session_destroy();
    header('Location: /Project/index.php');
}
?>