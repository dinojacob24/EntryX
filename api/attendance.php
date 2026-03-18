<?php
require_once '../config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/');
    session_start();
}
header('Content-Type: application/json');

function debugLog($msg) {
    $logFile = __DIR__ . '/attendance_debug.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
}

// Only allow authenticated staff
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'event_admin', 'security'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized Access. Please re-login.']);
    exit;
}

// ── GET ?action=inside — return who is currently inside ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'inside') {
    try {
        $stmt = $pdo->prepare("
            SELECT al.id as log_id, u.name, u.role, al.entry_time
            FROM   attendance_logs al
            JOIN   registrations r ON al.registration_id = r.id
            JOIN   users u ON r.user_id = u.id
            WHERE  al.status = 'inside'
            ORDER  BY al.entry_time ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $people = array_map(fn($r) => [
            'log_id' => $r['log_id'],
            'name'   => $r['name'],
            'role'   => $r['role'],
            'since'  => date('H:i', strtotime($r['entry_time']))
        ], $rows);
        echo json_encode(['success' => true, 'people' => $people]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── POST: handle QR scan OR manual exit ──
$input = json_decode(file_get_contents('php://input'), true);

// ── MANUAL EXIT from "Inside Now" panel ──
if (($input['action'] ?? '') === 'manual_exit') {
    $logId = (int)($input['log_id'] ?? 0);
    if (!$logId) { echo json_encode(['success' => false, 'error' => 'Missing log_id']); exit; }
    try {
        $stmtCheck = $pdo->prepare("SELECT id, status FROM attendance_logs WHERE id = ?");
        $stmtCheck->execute([$logId]);
        $logRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if (!$logRow || $logRow['status'] !== 'inside') {
            echo json_encode(['success' => false, 'error' => 'Person is not marked as inside']);
            exit;
        }
        $pdo->prepare("UPDATE attendance_logs SET exit_time = CURRENT_TIMESTAMP, status = 'exited' WHERE id = ?")
            ->execute([$logId]);
        debugLog("Manual exit: log_id $logId");
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── QR SCAN ──
$qrToken        = trim($input['qr_token'] ?? '');
$scannerEventId = (int)($input['event_id'] ?? 0);
$scanMode       = $input['mode'] ?? 'entry'; // 'entry' or 'exit'

if (empty($qrToken)) {
    echo json_encode(['success' => false, 'error' => 'No QR Token received']);
    exit;
}

try {
    // ─────────────────────────────────────────────────────────────────
    // STRATEGY 1: Token matches registrations.qr_token OR qr_code
    //             (event ticket - externals registering for events)
    //
    // NOTE: We check BOTH columns because old records may have stored
    // the token only in qr_code while new records use qr_token.
    // This ensures backward compatibility.
    // ─────────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT r.id AS reg_id, r.event_id, r.payment_status,
               u.id AS user_id, u.name AS user_name, u.role AS user_role
        FROM   registrations r JOIN users u ON r.user_id = u.id
        WHERE  r.qr_token = ? OR r.qr_code = ?
        LIMIT 1
    ");
    $stmt->execute([$qrToken, $qrToken]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);

    // Sync both columns if one is missing (backfill for legacy records)
    if ($reg) {
        try {
            $pdo->prepare("UPDATE registrations SET qr_token = ?, qr_code = ? WHERE id = ? AND (qr_token IS NULL OR qr_code IS NULL OR qr_token != ? OR qr_code != ?)")
                ->execute([$qrToken, $qrToken, $reg['reg_id'], $qrToken, $qrToken]);
        } catch (Exception $syncEx) {
            // Non-fatal: best effort sync
            debugLog("QR sync warning: " . $syncEx->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // STRATEGY 2: Token matches users.qr_token (the Entry QR Pass 
    //             shown on the external dashboard)
    //
    // IMPORTANT RULES:
    //  - This QR is the OFFICIAL entry pass for externals.
    //  - It must ONLY be accepted if the user is an 'external'.
    //  - ONE entry + ONE exit per QR. Once exited, permanently locked.
    //  - We find or create ONE registration per user per event.
    //    We NEVER create a second registration if one already exists
    //    for ANY attendance state (inside OR exited).
    // ─────────────────────────────────────────────────────────────────
    if (!$reg) {
        $stmtUser = $pdo->prepare("
            SELECT u.id AS user_id, u.name AS user_name, u.role AS user_role, ep.program_name
            FROM   users u LEFT JOIN external_programs ep ON u.external_program_id = ep.id
            WHERE  u.qr_token = ?
        ");
        $stmtUser->execute([$qrToken]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Only accept this QR for external users
            if ($user['user_role'] !== 'external') {
                debugLog("DENIED (non-external tried users.qr_token): " . $user['user_name']);
                echo json_encode(['success' => false, 'error' => '❌ This QR is not valid for campus entry. Use your event ticket instead.']);
                exit;
            }

            // Find or create the General Admission event
            if ($scannerEventId <= 0) {
                $stmtEx = $pdo->query("SELECT id FROM events WHERE name LIKE '%General Admission%' LIMIT 1");
                $fallbackEventId = $stmtEx->fetchColumn();
                if (!$fallbackEventId) {
                    $pdo->prepare("
                        INSERT INTO events (name, description, venue, event_date, type, status)
                        VALUES ('General Campus Admission', 'Main gate entry.', 'Main Entry Gate', NOW(), 'both', 'ongoing')
                    ")->execute();
                    $fallbackEventId = (int)$pdo->lastInsertId();
                }
                $scannerEventId = $fallbackEventId;
            }

            // Look for ANY existing registration for this user+event
            // (regardless of attendance state — inside, exited, or untouched)
            $stmtReg = $pdo->prepare("
                SELECT r.id AS reg_id, r.event_id, r.payment_status, u.name AS user_name, u.role AS user_role
                FROM   registrations r JOIN users u ON r.user_id = u.id
                WHERE  r.user_id = ? AND r.event_id = ?
                LIMIT 1
            ");
            $stmtReg->execute([$user['user_id'], $scannerEventId]);
            $reg = $stmtReg->fetch(PDO::FETCH_ASSOC);

            if (!$reg) {
                // ── First time this external ever shows up at the gate ──
                // Create exactly ONE registration. This is the only auto-registration 
                // allowed. Future scans reuse this same registration row.
                $pgName = !empty($user['program_name']) ? ('-' . str_replace(' ', '', $user['program_name'])) : '';
                $walkInToken = bin2hex(random_bytes(16)) . '-auto' . $pgName . '-' . $user['user_id'];
                $pdo->prepare("
                    INSERT INTO registrations (user_id, event_id, qr_token, qr_code, payment_status, total_amount, base_amount, gst_amount, amount_paid)
                    VALUES (?, ?, ?, ?, 'free', 0, 0, 0, 0)
                ")->execute([$user['user_id'], $scannerEventId, $walkInToken, $walkInToken]);
                $reg = [
                    'reg_id'         => (int)$pdo->lastInsertId(),
                    'event_id'       => $scannerEventId,
                    'payment_status' => 'free',
                    'user_name'      => $user['user_name'],
                    'user_role'      => $user['user_role'],
                ];
                debugLog("Auto-registration created for: " . $user['user_name'] . " | reg_id=" . $reg['reg_id']);
            }
        }
    } else {
        // Strategy 1 found a registration — ensure scannerEventId is set correctly
        if ($scannerEventId <= 0) {
            // Allow the scan against whatever event the registration belongs to
            $scannerEventId = (int)$reg['event_id'];
        }
    }

    if (!$reg) {
        echo json_encode(['success' => false, 'error' => '❌ Invalid QR Code — not recognised in the system']);
        exit;
    }

    if ($reg['payment_status'] === 'pending') {
        echo json_encode(['success' => false, 'error' => '⚠️ Payment pending — entry denied']);
        exit;
    }

    $regId = (int)$reg['reg_id'];

    // ─────────────────────────────────────────────────────────────────
    // ONE-TIME-PER-MODE RULE  (strict, no loopholes)
    //
    //   State A — No attendance record yet
    //             → ENTRY mode only → allowed (1st & ONLY entry)
    //             → EXIT mode → DENIED (person never entered)
    //
    //   State B — Status = 'inside' (entered, not yet exited)
    //             → EXIT mode only → allowed (1st & ONLY exit)
    //             → ENTRY mode → DENIED (already inside)
    //
    //   State C — Status = 'exited' (already entered AND exited)
    //             → ANY mode → PERMANENTLY DENIED
    //             → QR cannot be reused under any circumstances
    //
    // ─────────────────────────────────────────────────────────────────
    $stmtLog = $pdo->prepare("SELECT id, status, exit_time FROM attendance_logs WHERE registration_id = ? LIMIT 1");
    $stmtLog->execute([$regId]);
    $log = $stmtLog->fetch(PDO::FETCH_ASSOC);

    $isInside     = ($log && $log['status'] === 'inside');
    $hasExited    = ($log && $log['status'] === 'exited');
    $neverEntered = !$log;

    // ── State C: QR permanently locked after exit ──
    if ($hasExited) {
        debugLog("DENIED (already exited): " . $reg['user_name'] . " | reg_id=$regId");
        echo json_encode([
            'success' => false,
            'error'   => '🔒 ' . $reg['user_name'] . ' has already exited. This QR code is permanently locked and cannot be reused.'
        ]);
        exit;
    }

    if ($scanMode === 'entry') {

        if ($neverEntered) {
            // ── Race-condition guard: use INSERT with a unique constraint or double-check ──
            // Re-check inside a transaction to prevent duplicate entries
            $pdo->beginTransaction();
            try {
                // Lock the row for this reg and re-verify
                $stmtRC = $pdo->prepare("SELECT id FROM attendance_logs WHERE registration_id = ? LIMIT 1");
                $stmtRC->execute([$regId]);
                if ($stmtRC->fetch()) {
                    // Another request beat us to it
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'error' => '⚠️ ' . $reg['user_name'] . ' is already inside! This QR has been used for entry.']);
                    exit;
                }

                // ✅ Grant entry — this is the only entry allowed for this QR
                $pdo->prepare("INSERT INTO attendance_logs (registration_id, entry_time, status) VALUES (?, CURRENT_TIMESTAMP, 'inside')")
                    ->execute([$regId]);
                $pdo->commit();

                debugLog("✅ ENTRY GRANTED: " . $reg['user_name'] . " | reg_id=$regId | role=" . $reg['user_role']);
                echo json_encode([
                    'success'   => true,
                    'type'      => 'entry',
                    'message'   => 'Entry Recorded',
                    'user_name' => $reg['user_name'],
                    'user_role' => $reg['user_role'],
                    'time'      => date('H:i:s')
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                debugLog("ENTRY transaction failed: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Entry failed due to a system error. Please try again.']);
            }

        } elseif ($isInside) {
            // Already entered — deny re-entry
            debugLog("DENIED (already inside / re-entry attempt): " . $reg['user_name'] . " | reg_id=$regId");
            echo json_encode([
                'success' => false,
                'error'   => '⚠️ ' . $reg['user_name'] . ' is already inside! Re-entry is not allowed. Switch to Exit mode to record exit.'
            ]);
        }

    } elseif ($scanMode === 'exit') {

        if ($isInside) {
            // ✅ Grant exit — QR is now permanently locked after this
            $pdo->prepare("UPDATE attendance_logs SET exit_time = CURRENT_TIMESTAMP, status = 'exited' WHERE id = ?")
                ->execute([$log['id']]);
            debugLog("✅ EXIT GRANTED: " . $reg['user_name'] . " | reg_id=$regId | log_id=" . $log['id']);
            echo json_encode([
                'success'   => true,
                'type'      => 'exit',
                'message'   => 'Exit Recorded — QR permanently locked',
                'user_name' => $reg['user_name'],
                'user_role' => $reg['user_role'],
                'time'      => date('H:i:s')
            ]);

        } elseif ($neverEntered) {
            // Person hasn't entered at all — can't exit
            debugLog("DENIED (exit scan but never entered): " . $reg['user_name'] . " | reg_id=$regId");
            echo json_encode([
                'success' => false,
                'error'   => '⚠️ ' . $reg['user_name'] . ' has not entered yet! Switch to Entry mode to record entry first.'
            ]);
        }

    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid scan mode. Use "entry" or "exit".']);
    }

} catch (PDOException $e) {
    debugLog("DB ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>