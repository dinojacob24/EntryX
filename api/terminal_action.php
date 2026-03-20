<?php
require_once '../config/project_root.php';
require_once '../config/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

if ($action === 'scan') {
    $qr_token = $data['qr_token'] ?? '';
    $event_id = $data['event_id'] ?? 0;

    if (!$event_id) {
        die(json_encode(['error' => 'Please select an event first.']));
    }

    if (!$qr_token) {
        die(json_encode(['error' => 'No QR data received.']));
    }

    try {
        // Find Registration by QR token
        $stmt = $pdo->prepare("
            SELECT r.id, r.user_id, r.event_id, r.payment_status, u.name, u.role 
            FROM registrations r
            JOIN users u ON r.user_id = u.id
            WHERE r.qr_token = ?
        ");
        $stmt->execute([$qr_token]);
        $reg = $stmt->fetch();

        if (!$reg) {
            // Try legacy qr_code column too (for backward compatibility)
            $stmt = $pdo->prepare("
                SELECT r.id, r.user_id, r.event_id, r.payment_status, u.name, u.role 
                FROM registrations r
                JOIN users u ON r.user_id = u.id
                WHERE r.qr_code = ?
            ");
            $stmt->execute([$qr_token]);
            $reg = $stmt->fetch();
        }

        if (!$reg) {
            echo json_encode(['error' => 'Invalid ticket. Not registered.']);
            exit;
        }

        // Check Payment
        if ($reg['payment_status'] === 'pending') {
            echo json_encode(['error' => 'Payment pending. Entry denied.']);
            exit;
        }

        // Check Event Match
        if ($reg['event_id'] != $event_id) {
            echo json_encode(['error' => 'This ticket is for a different event.']);
            exit;
        }

        // Toggle Entry / Exit using attendance_logs
        $stmt = $pdo->prepare("
            SELECT * FROM attendance_logs 
            WHERE registration_id = ? 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$reg['id']]);
        $last = $stmt->fetch();

        if (!$last || $last['status'] === 'exited') {
            // Mark entry
            $stmt = $pdo->prepare("INSERT INTO attendance_logs (registration_id, entry_time, status) VALUES (?, NOW(), 'inside')");
            $stmt->execute([$reg['id']]);
            $msg = "Entry Granted";
            $type = "entry";
        } else {
            // Mark exit
            $stmt = $pdo->prepare("UPDATE attendance_logs SET exit_time = NOW(), status = 'exited' WHERE id = ?");
            $stmt->execute([$last['id']]);
            $msg = "Exit Marked";
            $type = "exit";
        }

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'type' => $type,
            'user' => $reg['name'],
            'role' => $reg['role']
        ]);

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'manual_entry') {
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $role = $data['role'] ?? 'internal';
    $event_id = $data['event_id'] ?? 0;

    if (!$name || !$email || !$event_id) {
        echo json_encode(['error' => 'Name, email, and event are required.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check/Create User
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $pass = password_hash('walkin_' . time(), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $pass, $role]);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $user['id'];
        }

        // Check for existing registration
        $stmt = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$user_id, $event_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $reg_id = $existing['id'];
        } else {
            // Register with free status
            $qr_token = bin2hex(random_bytes(16)) . '-' . $user_id . '-' . $event_id;
            $stmt = $pdo->prepare("
                INSERT INTO registrations (user_id, event_id, qr_token, payment_status, total_amount) 
                VALUES (?, ?, ?, 'free', 0)
            ");
            $stmt->execute([$user_id, $event_id, $qr_token]);
            $reg_id = $pdo->lastInsertId();
        }

        // Mark Entry
        $stmt = $pdo->prepare("INSERT INTO attendance_logs (registration_id, entry_time, status) VALUES (?, NOW(), 'inside')");
        $stmt->execute([$reg_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Walk-in entry recorded successfully.']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>