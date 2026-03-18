<?php
require_once '../config/db_connect.php';
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (($_GET['action'] ?? '') === 'gate_counts') {
    // Single atomic query for all 'Inside' stats to ensure total = internal + external
    $stmtSummary = $pdo->query("
        SELECT 
            COUNT(*) as inside,
            SUM(CASE WHEN u.role IN ('internal', 'student', 'staff') THEN 1 ELSE 0 END) as internal,
            SUM(CASE WHEN u.role = 'external' THEN 1 ELSE 0 END) as external
        FROM attendance_logs al
        JOIN registrations r ON al.registration_id = r.id
        JOIN users u ON r.user_id = u.id
        WHERE al.status = 'inside'
    ");
    $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    // Specific query for Today's cumulative throughput
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM attendance_logs WHERE DATE(entry_time) = CURDATE()");
    $totalToday = $stmtTotal->fetchColumn() ?: 0;

    echo json_encode([
        'success'  => true,
        'inside'   => (int)($summary['inside'] ?? 0),
        'internal' => (int)($summary['internal'] ?? 0),
        'external' => (int)($summary['external'] ?? 0),
        'total'    => (int)$totalToday
    ]);
    exit;
}

// New comprehensive dashboard stats action
if (($_GET['action'] ?? '') === 'dashboard_live') {
    try {
        // LIVE Campus Entry Stats - who is physically inside right now
        $stmtInside = $pdo->query("
            SELECT 
                COUNT(*) as inside,
                SUM(CASE WHEN u.role IN ('internal', 'student', 'staff') THEN 1 ELSE 0 END) as internal_inside,
                SUM(CASE WHEN u.role = 'external' THEN 1 ELSE 0 END) as external_inside
            FROM attendance_logs al
            JOIN registrations r ON al.registration_id = r.id
            JOIN users u ON r.user_id = u.id
            WHERE al.status = 'inside'
        ");
        $insideData = $stmtInside->fetch(PDO::FETCH_ASSOC);

        // Today's total entry count
        $stmtToday = $pdo->query("SELECT COUNT(*) FROM attendance_logs WHERE DATE(entry_time) = CURDATE()");
        $totalToday = (int)($stmtToday->fetchColumn() ?: 0);

        // Total events (real, upcoming events ONLY)
        $stmtEvents = $pdo->query("
            SELECT COUNT(*) FROM events 
            WHERE name NOT LIKE '%General%Admission%' 
              AND name NOT LIKE '%Campus Admission%'
              AND name NOT LIKE '%General%Campus%'
              AND event_date >= CURDATE()
              AND status NOT IN ('cancelled', 'completed')
        ");
        $totalEvents = (int)($stmtEvents->fetchColumn() ?: 0);

        // Total registrations (for upcoming real events only)
        $stmtReg = $pdo->query("
            SELECT COUNT(*) FROM registrations r 
            JOIN events e ON r.event_id = e.id 
            WHERE e.name NOT LIKE '%General%Admission%' 
              AND e.name NOT LIKE '%Campus Admission%'
              AND e.name NOT LIKE '%General%Campus%'
              AND e.event_date >= CURDATE()
        ");
        $totalReg = (int)($stmtReg->fetchColumn() ?: 0);

        // Total external participants registered
        $stmtExt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'external'");
        $externalCount = (int)($stmtExt->fetchColumn() ?: 0);

        // Recent activity (last 5 entries/exits for live feed)
        $stmtRecent = $pdo->query("
            SELECT u.name, u.role, al.entry_time, al.exit_time, al.status
            FROM attendance_logs al
            JOIN registrations r ON al.registration_id = r.id
            JOIN users u ON r.user_id = u.id
            ORDER BY al.entry_time DESC
            LIMIT 5
        ");
        $recentActivity = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success'          => true,
            'timestamp'        => date('H:i:s'),
            'people_inside'    => (int)($insideData['inside'] ?? 0),
            'internal_inside'  => (int)($insideData['internal_inside'] ?? 0),
            'external_inside'  => (int)($insideData['external_inside'] ?? 0),
            'entries_today'    => $totalToday,
            'total_events'     => $totalEvents,
            'total_reg'        => $totalReg,
            'external_count'   => $externalCount,
            'recent_activity'  => $recentActivity
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
