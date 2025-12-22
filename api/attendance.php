<?php
require_once '../config/db.php';
session_start();

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

if ($action === 'scan') {
    $qr_token = $data['qr_token'];

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

        if (!$reg) {
            die(json_encode(['error' => 'Invalid Ticket']));
        }

        // Check Attendance State
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE registration_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$reg['id']]);
        $last_record = $stmt->fetch();

        $message = '';
        $type = '';

        if (!$last_record || $last_record['status'] === 'outside' || $last_record['status'] === 'completed') {
            // Mark Entry
            $stmt = $pdo->prepare("INSERT INTO attendance (registration_id, entry_time, status) VALUES (?, NOW(), 'inside')");
            $stmt->execute([$reg['id']]);
            $message = "Entry Allowed: " . $reg['name'];
            $type = 'entry';
        } else {
            // Mark Exit
            $stmt = $pdo->prepare("UPDATE attendance SET exit_time = NOW(), status = 'completed' WHERE id = ?");
            $stmt->execute([$last_record['id']]);
            $message = "Exit Marked: " . $reg['name'];
            $type = 'exit';
        }

        // Get Current Count for this Event
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM attendance a 
            JOIN registrations r ON a.registration_id = r.id
            WHERE r.event_id = ? AND a.status = 'inside'
        ");
        $stmt->execute([$reg['event_id']]);
        $count = $stmt->fetch()['count'];

        echo json_encode([
            'success' => true,
            'message' => $message,
            'type' => $type,
            'current_count' => $count,
            'user' => $reg['name'],
            'role' => $reg['role']
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>