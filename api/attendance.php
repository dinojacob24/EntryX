<?php
require_once '../config/db_connect.php';

session_start();
header('Content-Type: application/json');

// Only allow authenticated staff (Super Admin, Event Coordinator, or Security)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'event_admin', 'security'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized Access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$qrToken = $input['qr_token'] ?? '';
$scannerEventId = $input['event_id'] ?? 0;

if (empty($qrToken)) {
    echo json_encode(['success' => false, 'error' => 'No QR Token']);
    exit;
}

try {
    // 1. Validate Registration via Token
    // We join with Users table to get the name for the response
    $stmt = $pdo->prepare("SELECT r.id as reg_id, r.event_id, u.name as user_name, u.role as user_role 
                           FROM registrations r 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.qr_token = ?");
    $stmt->execute([$qrToken]);
    $reg = $stmt->fetch();

    // FALLBACK: If not found in registrations, check users table (for External Guests)
    if (!$reg) {
        $stmtUser = $pdo->prepare("SELECT id as user_id, name as user_name, role as user_role, '0' as reg_id, '0' as event_id FROM users WHERE qr_token = ?");
        $stmtUser->execute([$qrToken]);
        $userReg = $stmtUser->fetch();

        if ($userReg) {
            // Found a valid User Token
            // Since they are external, we might not track precise "Entry/Exit" against an event ID in log if they are just generic guests,
            // OR we treat them as registered for the 'scannerEventId' implicitly if they are a valid user.
            // For now, let's treat it as a valid entry.
            $reg = $userReg;
            $reg['reg_id'] = 'USER_' . $userReg['user_id']; // Virtual Diff Key

            // Bypass event ID check for generic external passes if needed, or enforce it?
            // If the user is just a 'Guest', we might allow them in.
            $reg['event_id'] = $scannerEventId;
        }
    }

    if (!$reg) {
        echo json_encode(['success' => false, 'error' => 'Invalid QR Code']);
        exit;
    }

    if ($reg['event_id'] != $scannerEventId) {
        echo json_encode(['success' => false, 'error' => 'Ticket is for a different event']);
        exit;
    }

    // 2. Check Attendance State with Explicit Mode
    $scanMode = $input['mode'] ?? 'entry'; // default to entry if missing

    // Look for an 'inside' record (active session)
    $stmtLog = $pdo->prepare("SELECT id FROM attendance_logs WHERE registration_id = ? AND exit_time IS NULL");
    $stmtLog->execute([$reg['reg_id']]);
    $fActiveLog = $stmtLog->fetch();

    if ($scanMode === 'exit') {
        // --- EXIT MODE ---
        if ($fActiveLog) {
            // User IS inside -> Process EXIT
            $update = $pdo->prepare("UPDATE attendance_logs SET exit_time = CURRENT_TIMESTAMP, status = 'exited' WHERE id = ?");
            $update->execute([$fActiveLog['id']]);

            echo json_encode([
                'success' => true,
                'type' => 'exit',
                'message' => 'Exit Recorded',
                'user_name' => $reg['user_name'],
                'user_role' => $reg['user_role'],
                'time' => date('H:i:s')
            ]);
        } else {
            // User is NOT inside -> ERROR
            echo json_encode(['success' => false, 'error' => 'User is not inside (No Entry Record)']);
        }
    } else {
        // --- ENTRY MODE ---
        if ($fActiveLog) {
            // User IS inside -> ERROR (Already entered)
            echo json_encode(['success' => false, 'error' => 'User is already inside!']);
        } else {
            // User is OUTSIDE -> Process ENTRY
            $insert = $pdo->prepare("INSERT INTO attendance_logs (registration_id, entry_time, status) VALUES (?, CURRENT_TIMESTAMP, 'inside')");
            $insert->execute([$reg['reg_id']]);

            echo json_encode([
                'success' => true,
                'type' => 'entry',
                'message' => 'Entry Recorded',
                'user_name' => $reg['user_name'],
                'user_role' => $reg['user_role'],
                'time' => date('H:i:s')
            ]);
        }
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>