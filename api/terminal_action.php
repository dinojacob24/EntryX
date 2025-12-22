<?php
require_once '../config/db.php';
session_start();

// Ideally ensure Admin/Gatekeeper
// if(!isset($_SESSION['role'])) die(json_encode(['error' => 'Auth required']));

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

if ($action === 'manual_entry') {
    // 1. Create User (if not exists)
    // 2. Register for Event (Paid)
    // 3. Mark Attendance (Inside)

    $name = $data['name'];
    $email = $data['email'];
    $role = $data['role']; // internal/external
    $event_id = $data['event_id'];
    $student_id = $data['student_id'] ?? null;

    try {
        $pdo->beginTransaction();

        // Check/Create User
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Create dummy password for walk-ins
            $pass = password_hash('guest123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, student_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $pass, $role, $student_id]);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $user['id'];
        }

        // Calculate Fee to Record
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();

        $amount_paid = 0;
        if ($role === 'external') {
            $amount_paid = $event['base_fee'];
            if ($event['has_gst'])
                $amount_paid += ($event['base_fee'] * $event['gst_rate']) / 100;
        }

        // Register
        $qr_token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO registrations (user_id, event_id, amount_paid, qr_token, payment_status, status) VALUES (?, ?, ?, ?, 'paid', 'registered')");
        $stmt->execute([$user_id, $event_id, $amount_paid, $qr_token]);
        $reg_id = $pdo->lastInsertId();

        // Mark Attendance Intstantly
        $stmt = $pdo->prepare("INSERT INTO attendance (registration_id, entry_time, status) VALUES (?, NOW(), 'inside')");
        $stmt->execute([$reg_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'scan') {
    // Same as old attendance.php but allows ANY valid user to be auto-registered if it's an Open Event?
    // For now, let's assume Scan works on PRE-REGISTERED users.
    // OR if we want to auto-admit Internals who exist in DB but not registered for this specific event:

    $qr_token = $data['qr_token'];
    $event_id = $data['event_id'];

    if (!$event_id)
        die(json_encode(['error' => 'Select Event First']));

    try {
        // Find Registration
        $stmt = $pdo->prepare("
            SELECT r.id, r.user_id, r.event_id, u.name, u.role 
            FROM registrations r
            JOIN users u ON r.user_id = u.id
            WHERE r.qr_token = ?
        ");
        $stmt->execute([$qr_token]);
        $reg = $stmt->fetch();

        if ($reg) {
            // Existing Registration Logic (Check Event Match)
            if ($reg['event_id'] != $event_id) {
                die(json_encode(['error' => 'Ticket is for a different event']));
            }
            // Proceed to toggle entry/exit (Reuse logic or copy)
            // Reuse logic briefly:
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE registration_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$reg['id']]);
            $last = $stmt->fetch();

            if (!$last || $last['status'] === 'outside' || $last['status'] === 'completed') {
                $stmt = $pdo->prepare("INSERT INTO attendance (registration_id, entry_time, status) VALUES (?, NOW(), 'inside')");
                $stmt->execute([$reg['id']]);
                $type = 'entry';
                $msg = "Entry Allowed";
            } else {
                $stmt = $pdo->prepare("UPDATE attendance SET exit_time = NOW(), status = 'completed' WHERE id = ?");
                $stmt->execute([$last['id']]);
                $type = 'exit';
                $msg = "Exit Marked";
            }

            echo json_encode(['success' => true, 'message' => $msg, 'type' => $type, 'user' => $reg['name'], 'role' => $reg['role']]);

        } else {
            // Ticket not found. 
            // Could check if User exists via QR (if QR is static User ID?) but current system uses Transactional QR tokens.
            // So if Ticket not found -> Invalid.
            echo json_encode(['error' => 'Invalid Ticket / Not Registered']);
        }

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>