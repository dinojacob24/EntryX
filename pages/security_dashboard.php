<?php
// Session check MUST be at the very top
session_start();

// Access Control - Security and Admins only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['security', 'super_admin', 'event_admin'])) {
    header('Location: sub_admin_login.php');
    exit;
}

$hideFooter = true;
$hideHeaderNav = true;
$useCustomLayout = true;
require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../classes/Event.php';

$userName = $_SESSION['name'];
$userRole = $_SESSION['role'];
$eventObj = new Event($pdo);
$events = $eventObj->getAllEvents();

$initialEventId = !empty($events) ? $events[0]['id'] : 0;

// If no events exist, try to find a General entry event or just allow any scan
if ($initialEventId == 0) {
    // Check for a 'General' event already
    $stmtCheck = $pdo->query("SELECT id FROM events WHERE name LIKE '%General Admission%' LIMIT 1");
    $initialEventId = $stmtCheck->fetchColumn() ?: 0;
}

// Fetch live entry stats (Unified atomic query for consistency)
$stmtSummary = $pdo->query("
    SELECT 
        COUNT(*) as total_inside,
        SUM(CASE WHEN u.role IN ('internal', 'student', 'staff') THEN 1 ELSE 0 END) as internal_inside,
        SUM(CASE WHEN u.role = 'external' THEN 1 ELSE 0 END) as external_inside
    FROM attendance_logs al
    JOIN registrations r ON al.registration_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE al.status = 'inside'
");
$summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);

$totalInside = (int)($summary['total_inside'] ?? 0);
$internalInside = (int)($summary['internal_inside'] ?? 0);
$externalInside = (int)($summary['external_inside'] ?? 0);

$stmtToday = $pdo->query("SELECT COUNT(*) FROM attendance_logs WHERE DATE(entry_time) = CURDATE()");
$entriesToday = $stmtToday->fetchColumn() ?: 0;

// Recent activity log (UNION entries and exits to show movement history)
$stmtRecent = $pdo->query("
    (
        SELECT al.id, 'inside' as status, u.name as user_name, u.role as user_role, al.entry_time as log_time
        FROM attendance_logs al
        JOIN registrations r ON al.registration_id = r.id
        JOIN users u ON r.user_id = u.id
    )
    UNION ALL
    (
        SELECT al.id, 'exited' as status, u.name as user_name, u.role as user_role, al.exit_time as log_time
        FROM attendance_logs al
        JOIN registrations r ON al.registration_id = r.id
        JOIN users u ON r.user_id = u.id
        WHERE al.exit_time IS NOT NULL
    )
    ORDER BY log_time DESC LIMIT 30
");
$recentActivity = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

// Fetch who is currently inside (alphabetical)
$stmtWho = $pdo->query("
    SELECT al.id as log_id, u.name as user_name, u.role as user_role, al.entry_time
    FROM attendance_logs al
    JOIN registrations r ON al.registration_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE al.status = 'inside'
    ORDER BY u.name ASC
");
$whoInside = $stmtWho->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --sec-brand: #3b82f6;
        --sec-success: #10b981;
        --sec-error: #ef4444;
        --sec-warn: #f59e0b;
        --sec-dark: #04060f;
        --sec-panel: rgba(10, 15, 35, 0.9);
        --sec-border: rgba(59, 130, 246, 0.15);
        --sec-internal: #10b981;
        --sec-external: #f59e0b;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: var(--sec-dark);
        color: #f8fafc;
        font-family: 'Outfit', sans-serif;
        margin: 0;
        height: 100vh;
        overflow: hidden;
        background-image:
            radial-gradient(ellipse at 10% 20%, rgba(59, 130, 246, 0.06) 0%, transparent 50%),
            radial-gradient(ellipse at 90% 80%, rgba(16, 185, 129, 0.04) 0%, transparent 50%);
    }

    nav,
    .top-nav,
    header:not(.gate-header) {
        display: none !important;
    }

    /* ===== MAIN LAYOUT ===== */
    .gate-terminal {
        display: grid;
        grid-template-columns: 300px 1fr 320px;
        height: 100vh;
        overflow: hidden;
    }

    /* ===== LEFT SIDEBAR ===== */
    .sidebar-left {
        background: rgba(8, 12, 30, 0.95);
        border-right: 1px solid var(--sec-border);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--sec-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .brand-mark {
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .brand-icon {
        width: 38px;
        height: 38px;
        background: linear-gradient(135deg, #1d4ed8, #3b82f6);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .brand-text {
        font-size: 1rem;
        font-weight: 900;
        color: white;
        letter-spacing: -0.02em;
    }

    .brand-sub {
        font-size: 0.6rem;
        color: #3b82f6;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-weight: 700;
        margin-top: -2px;
    }

    .logout-btn {
        width: 34px;
        height: 34px;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.25);
        border-radius: 8px;
        color: #ef4444;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        text-decoration: none;
        transition: 0.2s;
    }

    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.2);
    }

    .sidebar-body {
        padding: 1.2rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        overflow-y: auto;
        flex: 1;
    }

    /* ===== STAT CARDS ===== */
    .stat-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 14px;
        padding: 1.2rem;
        transition: 0.2s;
    }

    .stat-card:hover {
        border-color: var(--sec-border);
    }

    .stat-label-sm {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #64748b;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .stat-value-lg {
        font-size: 2.5rem;
        font-weight: 900;
        color: white;
        line-height: 1;
        margin-bottom: 0.3rem;
    }

    .stat-sub {
        font-size: 0.75rem;
        color: #475569;
        font-weight: 600;
    }

    .capacity-bar {
        height: 5px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 99px;
        overflow: hidden;
        margin-top: 0.8rem;
    }

    .capacity-fill {
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, #3b82f6, #10b981);
        transition: width 0.5s ease;
    }

    /* Internal / External breakdown */
    .role-breakdown {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.6rem;
    }

    .role-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 0.8rem;
        text-align: center;
    }

    .role-count {
        font-size: 1.6rem;
        font-weight: 900;
        margin-bottom: 0.2rem;
    }

    .role-card.int .role-count {
        color: var(--sec-internal);
    }

    .role-card.ext .role-count {
        color: var(--sec-external);
    }

    .role-name {
        font-size: 0.6rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #475569;
    }

    /* Mode Indicator */
    .mode-indicator {
        padding: 1rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: 0.3s;
    }

    .mode-indicator.entry {
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.25);
    }

    .mode-indicator.exit {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.25);
    }

    .mode-indicator.idle {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .mode-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .mode-indicator.entry .mode-dot {
        background: #10b981;
        box-shadow: 0 0 8px #10b981;
        animation: blink 1.5s infinite;
    }

    .mode-indicator.exit .mode-dot {
        background: #ef4444;
        box-shadow: 0 0 8px #ef4444;
        animation: blink 1.5s infinite;
    }

    .mode-indicator.idle .mode-dot {
        background: #475569;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.3;
        }
    }

    .sidebar-footer {
        padding: 1rem 1.2rem;
        border-top: 1px solid var(--sec-border);
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .officer-avatar {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 800;
        color: white;
        flex-shrink: 0;
    }

    /* ===== MAIN SCANNER AREA ===== */
    .scanner-main {
        display: flex;
        flex-direction: column;
        background: #000;
        position: relative;
        overflow: hidden;
    }

    .scanner-main-header {
        padding: 1.2rem 1.5rem;
        background: rgba(8, 12, 30, 0.9);
        border-bottom: 1px solid var(--sec-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 10;
        backdrop-filter: blur(20px);
    }

    .mode-tabs {
        display: flex;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 12px;
        padding: 0.3rem;
    }

    .mode-tab {
        padding: 0.6rem 1.4rem;
        border-radius: 8px;
        font-weight: 800;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        cursor: pointer;
        border: none;
        background: transparent;
        color: #475569;
        transition: 0.25s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .mode-tab.active-entry {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.35);
    }

    .mode-tab.active-exit {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.35);
    }

    .scanner-chamber {
        flex: 1;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    #reader {
        width: 100% !important;
        height: 100% !important;
        border: none !important;
    }

    #reader__dashboard,
    #reader__header {
        display: none !important;
    }

    #reader__scan_region {
        background: #000 !important;
    }

    #reader video,
    #reader canvas {
        object-fit: cover !important;
        width: 100% !important;
        height: 100% !important;
    }

    /* Scan overlay */
    .scan-frame {
        position: absolute;
        width: 260px;
        height: 260px;
        z-index: 20;
        pointer-events: none;
    }

    .scan-frame::before,
    .scan-frame::after {
        content: '';
        position: absolute;
        width: 40px;
        height: 40px;
        border-color: var(--frame-color, #3b82f6);
        border-style: solid;
        border-width: 3px;
    }

    .scan-frame::before {
        top: 0;
        left: 0;
        border-right: none;
        border-bottom: none;
        border-radius: 6px 0 0 0;
    }

    .scan-frame::after {
        bottom: 0;
        right: 0;
        border-left: none;
        border-top: none;
        border-radius: 0 0 6px 0;
    }

    .scan-frame .corner-tr {
        position: absolute;
        top: 0;
        right: 0;
        width: 40px;
        height: 40px;
        border-top: 3px solid var(--frame-color, #3b82f6);
        border-right: 3px solid var(--frame-color, #3b82f6);
        border-radius: 0 6px 0 0;
    }

    .scan-frame .corner-bl {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 40px;
        border-bottom: 3px solid var(--frame-color, #3b82f6);
        border-left: 3px solid var(--frame-color, #3b82f6);
        border-radius: 0 0 0 6px;
    }

    .scan-laser {
        position: absolute;
        width: 100%;
        height: 2px;
        background: var(--frame-color, #3b82f6);
        box-shadow: 0 0 12px 2px var(--frame-color, #3b82f6);
        top: 0;
        animation: laserSweep 2.5s ease-in-out infinite;
        z-index: 21;
        pointer-events: none;
    }

    @keyframes laserSweep {

        0%,
        100% {
            top: 0;
            opacity: 1;
        }

        50% {
            top: 100%;
            opacity: 0.7;
        }
    }

    /* Start screen overlay */
    .start-overlay {
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at center, #08102b 0%, #020617 100%);
        z-index: 50;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2rem;
        transition: opacity 0.5s ease, transform 0.5s ease;
    }

    .start-overlay.hidden {
        opacity: 0;
        pointer-events: none;
        transform: scale(1.05);
    }

    .start-camera-icon {
        width: 120px;
        height: 120px;
        background: rgba(59, 130, 246, 0.08);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #3b82f6;
        animation: iconPulse 3s ease-in-out infinite;
    }

    @keyframes iconPulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.15);
        }

        50% {
            box-shadow: 0 0 0 20px rgba(59, 130, 246, 0);
        }
    }

    .start-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: white;
        text-align: center;
    }

    .start-sub {
        color: #475569;
        font-size: 0.9rem;
        font-weight: 600;
        text-align: center;
        max-width: 300px;
        line-height: 1.5;
    }

    .start-btn-group {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        width: 100%;
        max-width: 280px;
    }

    .start-btn {
        width: 100%;
        padding: 1rem 1.5rem;
        border-radius: 14px;
        font-weight: 800;
        font-size: 0.95rem;
        letter-spacing: 0.05em;
        cursor: pointer;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.7rem;
        transition: 0.25s;
    }

    .start-btn:hover {
        transform: translateY(-2px);
    }

    .start-btn.entry-btn {
        background: linear-gradient(135deg, #059669, #10b981);
        color: white;
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    }

    .start-btn.exit-btn {
        background: linear-gradient(135deg, #dc2626, #ef4444);
        color: white;
        box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
    }

    /* Scanner footer controls */
    .scanner-footer {
        padding: 1rem 1.5rem;
        background: rgba(8, 12, 30, 0.9);
        border-top: 1px solid var(--sec-border);
        display: flex;
        gap: 0.8rem;
        align-items: center;
        justify-content: space-between;
        backdrop-filter: blur(20px);
    }

    .ctrl-btn {
        padding: 0.6rem 1.1rem;
        border-radius: 9px;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.04);
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: 0.2s;
    }

    .ctrl-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        color: white;
    }

    .ctrl-btn.danger {
        border-color: rgba(239, 68, 68, 0.3);
        color: #ef4444;
        background: rgba(239, 68, 68, 0.06);
    }

    .ctrl-btn.danger:hover {
        background: rgba(239, 68, 68, 0.12);
    }

    /* Manual input */
    .manual-input-group {
        display: flex;
        gap: 0.4rem;
        flex: 1;
        max-width: 350px;
    }

    .manual-input-group input {
        flex: 1;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 8px;
        color: white;
        padding: 0.55rem 0.9rem;
        font-size: 0.8rem;
        font-weight: 600;
        outline: none;
        transition: 0.2s;
        font-family: monospace;
    }

    .manual-input-group input:focus {
        border-color: var(--sec-brand);
        background: rgba(59, 130, 246, 0.06);
    }

    .manual-input-group input::placeholder {
        color: #334155;
    }

    /* ===== RIGHT PANEL: ACTIVITY LOG ===== */
    .sidebar-right {
        background: rgba(8, 12, 30, 0.95);
        border-left: 1px solid var(--sec-border);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .log-header {
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid var(--sec-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .log-title {
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .live-badge {
        width: 6px;
        height: 6px;
        background: #10b981;
        border-radius: 50%;
        box-shadow: 0 0 6px #10b981;
        animation: blink 1.5s infinite;
    }

    .log-feed {
        flex: 1;
        overflow-y: auto;
        padding: 0.8rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .log-feed::-webkit-scrollbar {
        width: 3px;
    }

    .log-feed::-webkit-scrollbar-track {
        background: transparent;
    }

    .log-feed::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 99px;
    }

    .log-item {
        padding: 0.9rem;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-left: 3px solid #334155;
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(15px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .log-item.entry {
        border-left-color: #10b981;
        background: rgba(16, 185, 129, 0.04);
    }

    .log-item.exit {
        border-left-color: #ef4444;
        background: rgba(239, 68, 68, 0.04);
    }

    /* Inside Now panel item */
    .inside-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.75rem 0.8rem;
        border-radius: 10px;
        background: rgba(16, 185, 129, 0.05);
        border: 1px solid rgba(16, 185, 129, 0.12);
        animation: slideInRight 0.3s ease-out;
    }

    .inside-avatar {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, #064e3b, #10b981);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 800;
        color: white;
        flex-shrink: 0;
    }

    .inside-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 0.2rem;
    }

    .inside-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.65rem;
        color: #475569;
        font-weight: 600;
    }

    .log-name {
        font-weight: 800;
        font-size: 0.85rem;
        color: white;
        margin-bottom: 0.25rem;
    }

    .log-meta {
        font-size: 0.7rem;
        color: #475569;
        font-weight: 600;
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .role-pill {
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .role-pill.internal {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .role-pill.external {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
    }

    .action-pill {
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .action-pill.entry {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .action-pill.exit {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .action-pill.denied {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .empty-log {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: #1e293b;
        text-align: center;
        padding: 2rem;
    }

    /* ===== FULL SCREEN FLASH OVERLAY ===== */
    .result-flash {
        position: fixed;
        inset: 0;
        z-index: 999;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(20px);
        animation: flashIn 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes flashIn {
        from {
            opacity: 0;
            transform: scale(0.92);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .result-flash.success {
        background: rgba(6, 25, 14, 0.97);
    }

    .result-flash.failure {
        background: rgba(25, 6, 6, 0.97);
    }

    .flash-icon {
        font-size: 5rem;
        margin-bottom: 1.5rem;
        animation: iconBounce 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes iconBounce {
        from {
            transform: scale(0);
        }

        to {
            transform: scale(1);
        }
    }

    .flash-title {
        font-size: 2.5rem;
        font-weight: 900;
        margin-bottom: 0.5rem;
        text-align: center;
    }

    .flash-sub {
        font-size: 1.1rem;
        font-weight: 600;
        opacity: 0.7;
        text-align: center;
        margin-bottom: 0.8rem;
    }

    .flash-user {
        font-size: 1.8rem;
        font-weight: 900;
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .flash-role-badge {
        padding: 0.4rem 1.2rem;
        border-radius: 99px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 2rem;
    }

    .flash-role-badge.internal {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .flash-role-badge.external {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .result-flash.success .flash-title {
        color: #10b981;
    }

    .result-flash.failure .flash-title {
        color: #ef4444;
    }

    .result-flash.success .flash-icon {
        color: #10b981;
    }

    .result-flash.failure .flash-icon {
        color: #ef4444;
    }

    /* Time display */
    .live-clock {
        font-size: 1rem;
        font-weight: 800;
        color: #3b82f6;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.05em;
    }

    /* Scrollbars for sidebars */
    .sidebar-body::-webkit-scrollbar {
        width: 3px;
    }

    .sidebar-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-body::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 99px;
    }

    /* Responsive hide for narrow */
    @media (max-width: 1100px) {
        .gate-terminal {
            grid-template-columns: 250px 1fr 260px;
        }
    }

    /* ===== MOBILE LAYOUT (<=768px) ===== */
    @media (max-width: 768px) {
        body {
            height: auto !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            min-height: 100vh;
        }
        .gate-terminal {
            display: flex !important;
            flex-direction: column !important;
            height: auto !important;
            min-height: 100vh;
            overflow: visible !important;
        }
        /* Stats strip at top */
        .sidebar-left {
            flex-direction: column !important;
            height: auto !important;
            border-right: none !important;
            border-bottom: 1px solid rgba(59,130,246,0.2) !important;
            overflow: visible !important;
            order: 2; /* Stats go BELOW camera on mobile */
        }
        .sidebar-header {
            padding: 0.75rem 1rem !important;
            border-bottom: 1px solid rgba(59,130,246,0.1) !important;
        }
        .sidebar-body {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 0.6rem !important;
            padding: 0.75rem !important;
            overflow: visible !important;
            height: auto !important;
            flex: unset !important;
            white-space: normal !important;
        }
        .sidebar-body .stat-card {
            min-width: unset !important;
            padding: 0.75rem !important;
        }
        .sidebar-body .stat-value-lg {
            font-size: 1.8rem !important;
        }
        .sidebar-footer { display: none !important; }
        /* Camera section — auto height so ALL buttons are visible */
        .scanner-main {
            order: 1; /* Camera/scanner comes FIRST on mobile */
            height: auto !important;
            min-height: unset !important;
            flex-shrink: 0 !important;
            overflow: visible !important;
        }
        /* Scanner chamber: auto height to show full start overlay */
        .scanner-chamber {
            position: relative !important;
            height: auto !important;
            min-height: unset !important;
            overflow: visible !important;
        }
        /* When camera is active, give it enough room */
        #cameraFeed:not([style*="display:none"]) + .scanner-chamber,
        .scanner-chamber:has(#cameraFeed[style*="block"]) {
            min-height: 60vh !important;
        }
        /* Start overlay: static position so it flows normally */
        .start-overlay {
            position: relative !important;
            padding: 2rem 1rem !important;
        }
        .scanner-footer {
            flex-wrap: wrap !important;
            gap: 0.5rem !important;
            padding: 0.5rem 0.75rem !important;
        }
        .manual-input-group {
            flex: 1 1 100% !important;
            max-width: 100% !important;
        }
        .sidebar-right { display: none !important; }
        .start-btn-group {
            max-width: 100% !important;
            padding: 0 1rem !important;
            width: 100% !important;
        }
        .mode-indicator { display: none !important; }
    }
</style>


<div class="gate-terminal">
    <!-- ===== LEFT SIDEBAR ===== -->
    <aside class="sidebar-left">
        <div class="sidebar-header">
            <div class="brand-mark">
                <div class="brand-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <div class="brand-text">Gate Terminal</div>
                    <div class="brand-sub">EntryX Security</div>
                </div>
            </div>
            <button onclick="confirmLogout()" class="logout-btn" title="Logout" style="border: none; background: rgba(239, 68, 68, 0.1);">
                <i class="fa-solid fa-power-off"></i>
            </button>
        </div>

        <div class="sidebar-body">
            <!-- Live Clock & Status -->
            <div class="stat-card"
                style="display:flex;justify-content:space-between;align-items:center;padding:1rem 1.2rem;">
                <div>
                    <div class="stat-label-sm"><i class="fa-solid fa-circle"
                            style="color:#10b981;font-size:0.5rem;"></i> LIVE STATUS</div>
                    <div id="liveTime" class="live-clock">00:00:00</div>
                </div>
                <div id="gateConditionBadge"
                    style="padding:0.4rem 0.9rem;border-radius:8px;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;background:rgba(71,85,105,0.2);border:1px solid rgba(71,85,105,0.3);color:#475569;">
                    SECURE
                </div>
            </div>

            <!-- People Inside -->
            <div class="stat-card">
                <div class="stat-label-sm"><i class="fa-solid fa-users" style="color:#3b82f6;"></i> Currently Inside
                </div>
                <div class="stat-value-lg" id="countInside"><?php echo $totalInside; ?></div>
                <div class="stat-sub">of 500 capacity</div>
                <div class="capacity-bar">
                    <div id="capacityFill" class="capacity-fill"
                        style="width: <?php echo min(($totalInside / 500) * 100, 100); ?>%;"></div>
                </div>
            </div>

            <!-- Externals Inside -->
            <div>
                <div class="stat-label-sm" style="margin-bottom:0.6rem;"><i class="fa-solid fa-user-group"
                        style="color:#f59e0b;"></i> Externals Inside</div>
                <div class="role-card ext" style="width:100%;">
                    <div class="role-count" id="countExternal"><?php echo $externalInside; ?></div>
                    <div class="role-name">External</div>
                </div>
                <!-- hidden element kept for JS compatibility -->
                <span id="countInternal" style="display:none;"><?php echo $internalInside; ?></span>
            </div>

            <!-- Today's Activity -->
            <div class="stat-card">
                <div class="stat-label-sm"><i class="fa-solid fa-calendar-day" style="color:#a855f7;"></i> Today's
                    Entries</div>
                <div class="stat-value-lg" id="countToday"><?php echo $entriesToday; ?></div>
                <div class="stat-sub">Total scans today</div>
            </div>

            <!-- Scan Mode Status -->
            <div>
                <div class="stat-label-sm" style="margin-bottom:0.6rem;"><i class="fa-solid fa-radar"
                        style="color:#3b82f6;"></i> Scan Mode</div>
                <div id="modeIndicator" class="mode-indicator idle">
                    <div class="mode-dot"></div>
                    <div>
                        <div style="font-size:0.8rem;font-weight:800;color:#475569;" id="modeText">SCANNER IDLE</div>
                        <div style="font-size:0.65rem;color:#334155;margin-top:2px;">Choose Entry or Exit to begin</div>
                    </div>
                </div>
            </div>

            <input type="hidden" id="activeEventId" value="<?php echo $initialEventId; ?>">
        </div>

        <div class="sidebar-footer">
            <div class="officer-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
            <div style="flex:1;overflow:hidden;">
                <div
                    style="font-size:0.8rem;font-weight:700;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?php echo htmlspecialchars($userName); ?>
                </div>
                <div style="font-size:0.65rem;color:#475569;text-transform:uppercase;letter-spacing:0.08em;">
                    <?php echo str_replace('_', ' ', $userRole); ?>
                </div>
            </div>
        </div>
    </aside>

    <!-- ===== MAIN SCANNER ===== -->
    <main class="scanner-main">
        <div class="scanner-main-header">
            <div style="display:flex;align-items:center;gap:1rem;">
                <div style="font-size:1rem;font-weight:800;color:white;letter-spacing:-0.01em;">Security Scanner</div>
                <div id="scannerModeBadge"
                    style="display:none;padding:0.3rem 0.9rem;border-radius:7px;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;">
                </div>
            </div>
            <div style="display:flex;gap:0.7rem;align-items:center;">
                <div id="cameraControls" style="display:none;gap:0.5rem;display:none;">
                    <button class="ctrl-btn" onclick="switchCamera()"><i class="fa-solid fa-camera-rotate"></i>
                        Flip</button>
                    <button class="ctrl-btn danger" onclick="stopScanner()"><i class="fa-solid fa-stop"></i>
                        Stop</button>
                </div>
                <button class="ctrl-btn" onclick="toggleFullScreen()"><i class="fa-solid fa-expand"></i>
                    Fullscreen</button>
            </div>
        </div>

        <!-- Mode Tabs (shown when scanner is running) -->
        <div id="modeTabs"
            style="display:none;padding:0.8rem 1.5rem;background:rgba(4,6,15,0.8);border-bottom:1px solid var(--sec-border);">
            <div class="mode-tabs">
                <button class="mode-tab active-entry" id="tabEntry" onclick="switchMode('entry')">
                    <i class="fa-solid fa-right-to-bracket"></i> Entry Scan
                </button>
                <button class="mode-tab" id="tabExit" onclick="switchMode('exit')">
                    <i class="fa-solid fa-right-from-bracket"></i> Exit Scan
                </button>
            </div>
        </div>

        <div class="scanner-chamber">
            <!-- Start Overlay -->
            <div class="start-overlay" id="startOverlay">
                <div class="start-camera-icon">
                    <i class="fa-solid fa-camera" style="font-size:3rem;"></i>
                </div>
                <div>
                    <div class="start-title">Gate Security Terminal</div>
                    <div class="start-sub" style="margin-top:0.5rem;">Select a mode to start scanning.<br>Each QR can be used <strong style="color:#10b981;">once for entry</strong> and <strong style="color:#f59e0b;">once for exit</strong>.</div>
                </div>
                <div class="start-btn-group">
                    <button class="start-btn entry-btn" onclick="startScanner('entry')">
                        <i class="fa-solid fa-right-to-bracket fa-lg"></i>
                        START ENTRY SCAN
                    </button>
                    <button class="start-btn exit-btn" onclick="startScanner('exit')">
                        <i class="fa-solid fa-right-from-bracket fa-lg"></i>
                        START EXIT SCAN
                    </button>
                    <button onclick="forceRelease()"
                        style="background:transparent;border:1px solid rgba(255,255,255,0.08);color:#475569;border-radius:10px;padding:0.6rem 1rem;font-size:0.75rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:0.2s;"
                        onmouseover="this.style.color='white'" onmouseout="this.style.color='#475569'">
                        <i class="fa-solid fa-rotate"></i> Force Release Camera
                    </button>
                </div>
            </div>

            <!-- Direct camera video feed -->
            <video id="cameraFeed" autoplay playsinline muted
                style="width:100%;height:100%;object-fit:cover;display:none;position:absolute;inset:0;"></video>

            <!-- Hidden canvas for QR decoding -->
            <canvas id="qrCanvas" style="display:none;"></canvas>

            <!-- Scan Frame Overlay (shown when active) -->
            <div id="scanFrame" style="display:none;" class="scan-frame">
                <div class="corner-tr"></div>
                <div class="corner-bl"></div>
                <div class="scan-laser" id="scanLaser"></div>
            </div>

            <!-- Scanning status badge over video -->
            <div id="scanStatusBadge"
                style="display:none;position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.7);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:99px;padding:0.4rem 1.2rem;font-size:0.75rem;font-weight:700;color:#94a3b8;z-index:30;white-space:nowrap;">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Scanning for QR code...
            </div>
        </div>

        <!-- Footer: manual entry + cam controls -->
        <div class="scanner-footer">
            <div class="manual-input-group">
                <input type="text" id="manualTicketId" placeholder="Paste QR token for manual entry..."
                    style="font-size:0.75rem;">
                <button class="ctrl-btn" onclick="processManualEntry()" style="white-space:nowrap;"><i
                        class="fa-solid fa-paper-plane"></i> Submit</button>
            </div>
            <div style="display:flex;gap:0.5rem;" id="cameraControlsFooter" style="display:none;">
                <button class="ctrl-btn" onclick="switchCamera()"><i class="fa-solid fa-camera-rotate"></i>
                    Flip</button>
                <button class="ctrl-btn danger" onclick="stopScanner()"><i class="fa-solid fa-video-slash"></i>
                    Off</button>
            </div>
            <button class="ctrl-btn" onclick="toggleFullScreen()" style="margin-left:auto;"><i
                    class="fa-solid fa-expand"></i></button>
        </div>
    </main>

    <!-- ===== RIGHT: TABBED PANEL ===== -->
    <aside class="sidebar-right">

        <!-- Tab Strip -->
        <div style="display:flex;border-bottom:1px solid var(--sec-border);">
            <button id="tabActivity" onclick="switchRightTab('activity')"
                style="flex:1;padding:0.9rem 0.5rem;background:rgba(59,130,246,0.1);border:none;border-bottom:2px solid #3b82f6;color:#3b82f6;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;transition:0.2s;display:flex;align-items:center;justify-content:center;gap:0.5rem;">
                <div class="live-badge"></div> Activity Log
            </button>
            <button id="tabInside" onclick="switchRightTab('inside')"
                style="flex:1;padding:0.9rem 0.5rem;background:transparent;border:none;border-bottom:2px solid transparent;color:#475569;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;transition:0.2s;display:flex;align-items:center;justify-content:center;gap:0.5rem;">
                <i class="fa-solid fa-users" style="font-size:0.65rem;"></i> Inside Now
                <span id="insideBadge" style="background:rgba(16,185,129,0.2);color:#10b981;border-radius:99px;padding:1px 7px;font-size:0.6rem;"><?php echo count($whoInside); ?></span>
            </button>
        </div>

        <!-- ── ACTIVITY LOG PANEL ── -->
        <div id="panelActivity" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;">
            <div style="display:flex;justify-content:flex-end;padding:0.6rem 0.8rem;border-bottom:1px solid var(--sec-border);">
                <button onclick="clearLog()" class="ctrl-btn" style="font-size:0.65rem;padding:0.3rem 0.6rem;">
                    <i class="fa-solid fa-xmark"></i> Clear
                </button>
            </div>
            <div class="log-feed" id="logFeed">
                <?php if (empty($recentActivity)): ?>
                    <div class="empty-log" id="emptyLogMsg">
                        <i class="fa-solid fa-fingerprint" style="font-size:2.5rem;opacity:0.15;"></i>
                        <div style="font-size:0.85rem;font-weight:600;">Waiting for scan events...</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivity as $act): ?>
                        <div class="log-item <?php echo $act['status'] === 'inside' ? 'entry' : 'exit'; ?>">
                            <div class="log-name"><?php echo htmlspecialchars($act['user_name']); ?></div>
                            <div class="log-meta">
                                <span class="action-pill <?php echo $act['status'] === 'inside' ? 'entry' : 'exit'; ?>">
                                    <?php echo $act['status'] === 'inside' ? 'ENTRY' : 'EXIT'; ?>
                                </span>
                                <span class="role-pill <?php echo $act['user_role']; ?>">
                                    <?php echo strtoupper($act['user_role']); ?>
                                </span>
                                <span><?php echo date('H:i', strtotime($act['log_time'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── INSIDE NOW PANEL ── -->
        <div id="panelInside" style="flex:1;overflow-y:auto;display:none;flex-direction:column;">
            <div style="padding:0.6rem 0.8rem;border-bottom:1px solid var(--sec-border);display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:0.65rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#64748b;">Currently Inside Venue</span>
                <button onclick="refreshInsidePanel()" class="ctrl-btn" style="font-size:0.65rem;padding:0.3rem 0.6rem;">
                    <i class="fa-solid fa-rotate"></i>
                </button>
            </div>
            <div id="insideFeed" style="flex:1;overflow-y:auto;padding:0.8rem;display:flex;flex-direction:column;gap:0.4rem;">
                <?php if (empty($whoInside)): ?>
                    <div class="empty-log" id="emptyInsideMsg">
                        <i class="fa-solid fa-door-open" style="font-size:2.5rem;opacity:0.15;"></i>
                        <div style="font-size:0.85rem;font-weight:600;">No one inside yet</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($whoInside as $p): ?>
                        <div class="inside-item" style="display:flex;align-items:center;gap:0.8rem;padding:0.8rem;border-radius:10px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);margin-bottom:0.4rem;">
                            <div class="inside-avatar"><?php echo strtoupper(substr($p['user_name'],0,1)); ?></div>
                            <div style="flex:1;overflow:hidden;min-width:0;">
                                <div class="inside-name" style="font-weight:700;font-size:0.85rem;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($p['user_name']); ?></div>
                                <div class="inside-meta" style="display:flex;align-items:center;gap:0.4rem;margin-top:0.2rem;">
                                    <span class="role-pill <?php echo $p['user_role']; ?>"><?php echo strtoupper($p['user_role']); ?></span>
                                    <span style="font-size:0.65rem;color:#475569;">In since <?php echo date('H:i', strtotime($p['entry_time'])); ?></span>
                                </div>
                            </div>
                            <button onclick="manualExit(<?php echo $p['log_id']; ?>, this)"
                                style="flex-shrink:0;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#ef4444;border-radius:8px;padding:0.35rem 0.7rem;font-size:0.65rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;cursor:pointer;transition:0.2s;"
                                onmouseover="this.style.background='rgba(239,68,68,0.2)'"
                                onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                                <i class="fa-solid fa-right-from-bracket"></i> Exit
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </aside>
</div>

<!-- ===== MOBILE LOG PANEL BUTTON (mobile only) ===== -->
<button id="mobileLogBtn" onclick="openMobileLog()"
    style="display:none;position:fixed;bottom:5rem;right:1rem;z-index:200;
           background:linear-gradient(135deg,#1d4ed8,#3b82f6);
           border:none;border-radius:16px;color:white;font-weight:800;font-size:0.8rem;
           padding:0.75rem 1.1rem;cursor:pointer;box-shadow:0 8px 24px rgba(59,130,246,0.4);
           display:flex;align-items:center;gap:0.5rem;text-transform:uppercase;letter-spacing:0.06em;">
    <i class="fa-solid fa-users"></i>
    Who's Inside
    <span id="mobileInsideBadge" style="background:rgba(255,255,255,0.25);border-radius:99px;padding:2px 8px;font-size:0.75rem;">
        <?php echo $totalInside; ?>
    </span>
</button>

<!-- ===== MOBILE LOG BOTTOM SHEET ===== -->
<div id="mobileLogSheet"
    style="display:none;position:fixed;inset:0;z-index:300;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);"
    onclick="closeMobileLog()">
    <div onclick="event.stopPropagation()"
        style="position:absolute;bottom:0;left:0;right:0;background:#08102b;
               border-top:1px solid rgba(59,130,246,0.25);border-radius:24px 24px 0 0;
               max-height:85vh;display:flex;flex-direction:column;overflow:hidden;">

        <!-- Sheet Handle -->
        <div style="padding:0.75rem;text-align:center;">
            <div style="width:40px;height:4px;background:rgba(255,255,255,0.15);border-radius:99px;margin:0 auto;"></div>
        </div>

        <!-- Sheet Tabs -->
        <div style="display:flex;border-bottom:1px solid rgba(59,130,246,0.15);padding:0 1rem;">
            <button id="mobileTabActivity" onclick="switchMobileTab('activity')"
                style="flex:1;padding:0.8rem 0.5rem;background:rgba(59,130,246,0.1);border:none;border-bottom:2px solid #3b82f6;
                       color:#3b82f6;font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;
                       display:flex;align-items:center;justify-content:center;gap:0.5rem;">
                <span style="width:6px;height:6px;background:#10b981;border-radius:50%;box-shadow:0 0 6px #10b981;display:inline-block;"></span>
                Activity Log
            </button>
            <button id="mobileTabInside" onclick="switchMobileTab('inside')"
                style="flex:1;padding:0.8rem 0.5rem;background:transparent;border:none;border-bottom:2px solid transparent;
                       color:#475569;font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;cursor:pointer;
                       display:flex;align-items:center;justify-content:center;gap:0.5rem;">
                <i class="fa-solid fa-users" style="font-size:0.65rem;"></i> Inside Now
                <span style="background:rgba(16,185,129,0.2);color:#10b981;border-radius:99px;padding:1px 7px;font-size:0.65rem;">
                    <?php echo count($whoInside); ?>
                </span>
            </button>
        </div>

        <!-- Activity Log Panel -->
        <div id="mobilePanelActivity" style="flex:1;overflow-y:auto;padding:0.75rem;display:flex;flex-direction:column;gap:0.5rem;">
            <?php if (empty($recentActivity)): ?>
                <div style="text-align:center;padding:3rem;color:#334155;">
                    <i class="fa-solid fa-fingerprint" style="font-size:2rem;opacity:0.15;display:block;margin-bottom:1rem;"></i>
                    Waiting for scan events...
                </div>
            <?php else: ?>
                <?php foreach ($recentActivity as $act): ?>
                    <div style="padding:0.75rem;border-radius:10px;background:rgba(255,255,255,0.02);
                                border:1px solid rgba(255,255,255,0.05);
                                border-left:3px solid <?php echo $act['status']==='inside' ? '#10b981' : '#ef4444'; ?>;">
                        <div style="font-weight:800;font-size:0.85rem;color:white;margin-bottom:0.25rem;">
                            <?php echo htmlspecialchars($act['user_name']); ?>
                        </div>
                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            <span style="padding:2px 7px;border-radius:5px;font-size:0.65rem;font-weight:800;text-transform:uppercase;
                                background:<?php echo $act['status']==='inside' ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)'; ?>;
                                color:<?php echo $act['status']==='inside' ? '#10b981' : '#ef4444'; ?>;">
                                <?php echo $act['status']==='inside' ? 'ENTRY' : 'EXIT'; ?>
                            </span>
                            <span style="padding:2px 7px;border-radius:5px;font-size:0.65rem;font-weight:800;text-transform:uppercase;
                                background:rgba(255,255,255,0.05);color:#94a3b8;">
                                <?php echo strtoupper($act['user_role']); ?>
                            </span>
                            <span style="font-size:0.65rem;color:#475569;"><?php echo date('H:i', strtotime($act['log_time'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Inside Now Panel -->
        <div id="mobilePanelInside" style="flex:1;overflow-y:auto;padding:0.75rem;display:none;flex-direction:column;gap:0.5rem;">
            <?php if (empty($whoInside)): ?>
                <div style="text-align:center;padding:3rem;color:#334155;">
                    <i class="fa-solid fa-door-open" style="font-size:2rem;opacity:0.15;display:block;margin-bottom:1rem;"></i>
                    No one inside yet
                </div>
            <?php else: ?>
                <?php foreach ($whoInside as $p): ?>
                    <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;border-radius:10px;
                                background:rgba(16,185,129,0.04);border:1px solid rgba(16,185,129,0.12);">
                        <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#064e3b,#10b981);
                                    display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:800;
                                    color:white;flex-shrink:0;">
                            <?php echo strtoupper(substr($p['user_name'],0,1)); ?>
                        </div>
                        <div style="flex:1;overflow:hidden;min-width:0;">
                            <div style="font-weight:700;font-size:0.9rem;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($p['user_name']); ?>
                            </div>
                            <div style="display:flex;gap:0.4rem;align-items:center;margin-top:0.2rem;">
                                <span style="padding:2px 7px;border-radius:5px;font-size:0.65rem;font-weight:800;text-transform:uppercase;
                                    background:rgba(16,185,129,0.15);color:#10b981;">
                                    <?php echo strtoupper($p['user_role']); ?>
                                </span>
                                <span style="font-size:0.7rem;color:#475569;">
                                    Since <?php echo date('H:i', strtotime($p['entry_time'])); ?>
                                </span>
                            </div>
                        </div>
                        <button onclick="manualExit(<?php echo $p['log_id']; ?>, this)"
                            style="flex-shrink:0;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);
                                   color:#ef4444;border-radius:8px;padding:0.4rem 0.75rem;font-size:0.7rem;font-weight:800;
                                   text-transform:uppercase;cursor:pointer;">
                            <i class="fa-solid fa-right-from-bracket"></i> Exit
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Close button -->
        <div style="padding:0.75rem 1rem;border-top:1px solid rgba(59,130,246,0.1);">
            <button onclick="closeMobileLog()"
                style="width:100%;padding:0.85rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);
                       border-radius:12px;color:#94a3b8;font-weight:700;font-size:0.9rem;cursor:pointer;">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Mobile Log Sheet Controls
function openMobileLog() {
    document.getElementById('mobileLogSheet').style.display = 'block';
}
function closeMobileLog() {
    document.getElementById('mobileLogSheet').style.display = 'none';
}
function switchMobileTab(tab) {
    const actBtn  = document.getElementById('mobileTabActivity');
    const insBtn  = document.getElementById('mobileTabInside');
    const actPanel = document.getElementById('mobilePanelActivity');
    const insPanel = document.getElementById('mobilePanelInside');

    if (tab === 'activity') {
        actBtn.style.borderBottomColor = '#3b82f6';
        actBtn.style.color = '#3b82f6';
        actBtn.style.background = 'rgba(59,130,246,0.1)';
        insBtn.style.borderBottomColor = 'transparent';
        insBtn.style.color = '#475569';
        insBtn.style.background = 'transparent';
        actPanel.style.display = 'flex';
        insPanel.style.display = 'none';
    } else {
        insBtn.style.borderBottomColor = '#10b981';
        insBtn.style.color = '#10b981';
        insBtn.style.background = 'rgba(16,185,129,0.1)';
        actBtn.style.borderBottomColor = 'transparent';
        actBtn.style.color = '#475569';
        actBtn.style.background = 'transparent';
        insPanel.style.display = 'flex';
        actPanel.style.display = 'none';
    }
}

// Show mobile button only on small screens
function checkMobileLogBtn() {
    const btn = document.getElementById('mobileLogBtn');
    if (btn) btn.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
}
checkMobileLogBtn();
window.addEventListener('resize', checkMobileLogBtn);
</script>


<!-- ===== FULL-SCREEN RESULT FLASH ===== -->
<div id="resultFlash" class="result-flash">
    <div class="flash-icon" id="flashIcon"></div>
    <div class="flash-title" id="flashTitle"></div>
    <div class="flash-user" id="flashUser"></div>
    <div class="flash-role-badge" id="flashRoleBadge"></div>
    <div class="flash-sub" id="flashSub"></div>
</div>

<!-- Direct Camera Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
    // ============================================
    // STATE
    // ============================================
    let videoStream = null;
    let scanInterval = null;
    let isInitializing = false;
    let isProcessing = false;
    let currentScanMode = 'entry';
    let entriesToday = <?php echo $entriesToday; ?>;
    let insideNow = <?php echo $totalInside; ?>;
    let internalNow = <?php echo $internalInside; ?>;
    let externalNow = <?php echo $externalInside; ?>;
    let lastScannedToken = null;
    let lastScannedTime = 0;

    // Elements
    const video = document.getElementById('cameraFeed');
    const canvas = document.getElementById('qrCanvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const statusBadge = document.getElementById('scanStatusBadge');
    const startOverlay = document.getElementById('startOverlay');
    const scanFrame = document.getElementById('scanFrame');
    const modeTabs = document.getElementById('modeTabs');
    const cameraControls = document.getElementById('cameraControls');
    const cameraControlsFooter = document.getElementById('cameraControlsFooter');

    // Sound effects (simple beeps via oscillator - no external audio files needed)
    function playBeep(frequency = 880, duration = 120, type = 'sine') {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = frequency;
            osc.type = type;
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration / 1000);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + duration / 1000);
        } catch (e) { }
    }

    // ============================================
    // FORCE RELEASE ALL CAMERA STREAMS
    // This is the key fix for "Camera in use" errors.
    // It kills every active video track at the browser MediaStream level.
    // ============================================
    // ============================================
    // DIRECT CAMERA SCANNER (RAW getUserMedia)
    // ============================================
    async function startScanner(mode = 'entry') {
        if (isInitializing) return;
        currentScanMode = mode;
        isInitializing = true;
        updateModeUI(mode);

        // Define a helper to try starting the camera with specific constraints
        async function tryStream(facingMode) {
            const constraints = {
                video: {
                    facingMode: facingMode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };
            return await navigator.mediaDevices.getUserMedia(constraints);
        }

        try {
            // Stop any existing stream first
            await forceRelease();

            try {
                // Try preferred mode
                videoStream = await tryStream(usingFacingMode);
            } catch (pErr) {
                console.warn(`Failed to start with ${usingFacingMode}, trying fallback...`, pErr);
                // Fallback: Try ANY camera if the specific one fails
                videoStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            }

            video.srcObject = videoStream;

            // Wait for video to be ready
            await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => reject(new Error('Camera timeout')), 10000);
                video.onloadedmetadata = () => {
                    clearTimeout(timeout);
                    video.play().then(resolve).catch(reject);
                };
            });

            // Step 2: Show UI
            video.style.display = 'block';
            startOverlay.classList.add('hidden');
            scanFrame.style.display = 'block';
            modeTabs.style.display = 'block';
            statusBadge.style.display = 'block';
            cameraControls.style.display = 'flex';
            if (cameraControlsFooter) cameraControlsFooter.style.display = 'flex';

            // Step 3: Start decoding loop (jsQR)
            startDecodingLoop();

            // System messages suppressed — only person scans are logged

        } catch (err) {
            console.error('Camera Error:', err);
            let msg = 'Could not access camera.';
            let hint = 'Please ensure you have allowed camera permissions and no other app is using it.';

            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                msg = 'Camera permission denied.';
                hint = 'Click the LOCK icon 🔒 in the address bar → Reset Permission → Refresh page.';
            } else if (err.name === 'NotFoundError') {
                msg = 'No camera found.';
                hint = 'Please connect a webcam or ensure your camera is not disabled.';
            } else if (err.name === 'NotReadableError' || err.message.includes('busy')) {
                msg = 'Camera is busy or disconnected.';
                hint = 'Close other apps like Teams, Zoom, or other browser tabs using the camera.';
            }

            Swal.fire({
                title: '<span style="color:#ef4444">Camera Error</span>',
                html: `<div style="color:#94a3b8;margin-bottom:1rem;">${msg}</div>
                       <div style="color:#64748b;font-size:0.85rem;line-height:1.5;">${hint}</div>`,
                icon: 'error',
                background: '#0a0a0a',
                color: '#fff'
            });
            addToLog('Error: ' + msg, 'denied');
            updateModeUI('idle');
        } finally {
            isInitializing = false;
        }
    }

    function startDecodingLoop() {
        if (scanInterval) clearInterval(scanInterval);

        scanInterval = setInterval(() => {
            if (isProcessing) return;

            // Capture frame from video to canvas
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Try decoding with jsQR
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });

                if (code) {
                    onScanSuccess(code.data);
                }
            }
        }, 200); // Check every 200ms
    }

    async function stopScanner() {
        await forceRelease();

        video.style.display = 'none';
        startOverlay.classList.remove('hidden');
        scanFrame.style.display = 'none';
        modeTabs.style.display = 'none';
        statusBadge.style.display = 'none';
        cameraControls.style.display = 'none';
        if (cameraControlsFooter) cameraControlsFooter.style.display = 'none';

        updateModeUI('idle');
    }

    async function forceRelease() {
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }

        if (videoStream) {
            videoStream.getTracks().forEach(track => {
                track.stop();
                videoStream.removeTrack(track);
            });
            videoStream = null;
        }

        if (video) {
            video.srcObject = null;
            video.pause();
            video.load(); // Force reset video element
        }

        // Broad cleanup: ensure NO tracks are left active
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            // This is just a placeholder for potential future device-specific cleanup
        } catch (e) { }
    }

    async function switchMode(mode) {
        currentScanMode = mode;
        updateModeUI(mode);
        // mode switch is silent — no log entry
    }

    let usingFacingMode = 'user'; // Default to front camera for laptops
    async function switchCamera() {
        usingFacingMode = usingFacingMode === 'environment' ? 'user' : 'environment';
        await stopScanner();
        await startScanner(currentScanMode);
    }

    function updateModeUI(mode) {
        const indicator = document.getElementById('modeIndicator');
        const modeText = document.getElementById('modeText');
        const badge = document.getElementById('gateConditionBadge');
        const scannerBadge = document.getElementById('scannerModeBadge');
        const laser = document.getElementById('scanLaser');
        const frame = document.getElementById('scanFrame');

        indicator.style.background = '';
        indicator.style.border = '';

        // Tab styling
        const tabEntry = document.getElementById('tabEntry');
        const tabExit  = document.getElementById('tabExit');
        if (tabEntry && tabExit) {
            tabEntry.className = 'mode-tab' + (mode === 'entry' ? ' active-entry' : '');
            tabExit.className  = 'mode-tab' + (mode === 'exit'  ? ' active-exit'  : '');
        }

        if (mode === 'entry') {
            indicator.className = 'mode-indicator entry';
            modeText.textContent = 'ENTRY SCAN';
            modeText.style.color = '#10b981';
            badge.textContent = 'ENTRY';
            badge.style.background = 'rgba(16,185,129,0.15)';
            badge.style.borderColor = 'rgba(16,185,129,0.3)';
            badge.style.color = '#10b981';
            if (laser) { laser.style.background = '#10b981'; laser.style.boxShadow = '0 0 12px 2px #10b981'; }
            if (frame) frame.style.setProperty('--frame-color', '#10b981');
            scannerBadge.textContent = '⬤ ENTRY MODE';
            scannerBadge.style.background = 'rgba(16,185,129,0.15)';
            scannerBadge.style.color = '#10b981';
            scannerBadge.style.border = '1px solid rgba(16,185,129,0.3)';
            scannerBadge.style.display = 'block';
        } else if (mode === 'exit') {
            indicator.className = 'mode-indicator exit';
            modeText.textContent = 'EXIT SCAN';
            modeText.style.color = '#ef4444';
            badge.textContent = 'EXIT';
            badge.style.background = 'rgba(239,68,68,0.15)';
            badge.style.borderColor = 'rgba(239,68,68,0.3)';
            badge.style.color = '#ef4444';
            if (laser) { laser.style.background = '#ef4444'; laser.style.boxShadow = '0 0 12px 2px #ef4444'; }
            if (frame) frame.style.setProperty('--frame-color', '#ef4444');
            scannerBadge.textContent = '⬤ EXIT MODE';
            scannerBadge.style.background = 'rgba(239,68,68,0.15)';
            scannerBadge.style.color = '#ef4444';
            scannerBadge.style.border = '1px solid rgba(239,68,68,0.3)';
            scannerBadge.style.display = 'block';
        } else {
            indicator.className = 'mode-indicator idle';
            modeText.textContent = 'SCANNER IDLE';
            modeText.style.color = '#475569';
            badge.textContent = 'SECURE';
            badge.style.background = 'rgba(71,85,105,0.2)';
            badge.style.borderColor = 'rgba(71,85,105,0.3)';
            badge.style.color = '#475569';
            scannerBadge.style.display = 'none';
        }
    }

    // ============================================
    // SCAN SUCCESS HANDLER
    // ============================================
    function onScanSuccess(decodedText) {
        if (isProcessing) return;

        // Debounce: prevent double-firing same code within 3s
        const now = Date.now();
        if (decodedText === lastScannedToken && (now - lastScannedTime) < 3000) return;
        lastScannedToken = decodedText;
        lastScannedTime = now;

        const eId = document.getElementById('activeEventId').value || 0;
        // Allowance for General Entry Scans even if eId is 0 or unselected
        // The API will handle the context-based lookup (e.g. general program entry)
        
        isProcessing = true;

        if (navigator.vibrate) navigator.vibrate(80);

        fetch('../api/attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ qr_token: decodedText, event_id: eId, mode: currentScanMode })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    playBeep(1200, 120);
                    if (navigator.vibrate) navigator.vibrate(80);
                    
                    // Don't log if it's already in the feed (prevent local duplicates)
                    showResultFlash(true, data.type.toUpperCase() + ' GRANTED', data.user_name, data.user_role, data.time);
                    addToLog(data.user_name, data.type, data.user_role, data.time);

                    // Re-fetch accurate stats from server to avoid sync issues
                    refreshDashboardStats();
                } else {
                    playBeep(300, 300, 'square');
                    if (navigator.vibrate) navigator.vibrate([150, 80, 150]);
                    showResultFlash(false, 'ACCESS DENIED', data.error || 'Unknown error', '', '');
                }

                // Debounce: wait 1.5s before allowing NEXT scan
                setTimeout(() => { isProcessing = false; }, 1500);
                refreshInsidePanel();
            })
            .catch(err => {
                console.error(err);
                isProcessing = false;
            });
    }

    // ============================================
    // RESULT FLASH
    // ============================================
    function showResultFlash(success, title, userName, userRole, time) {
        const flash = document.getElementById('resultFlash');
        const icon = document.getElementById('flashIcon');
        const titleEl = document.getElementById('flashTitle');
        const userEl = document.getElementById('flashUser');
        const roleEl = document.getElementById('flashRoleBadge');
        const subEl = document.getElementById('flashSub');

        flash.className = 'result-flash ' + (success ? 'success' : 'failure');
        icon.innerHTML = success
            ? '<i class="fa-solid fa-circle-check"></i>'
            : '<i class="fa-solid fa-circle-xmark"></i>';
        titleEl.textContent = title;
        userEl.textContent = userName || '';
        subEl.textContent = time ? 'Recorded at ' + time : '';

        if (userRole) {
            roleEl.textContent = userRole.toUpperCase();
            roleEl.className = 'flash-role-badge ' + userRole;
            roleEl.style.display = 'inline-block';
        } else {
            roleEl.style.display = 'none';
        }

        flash.style.display = 'flex';

        setTimeout(() => {
            flash.style.display = 'none';
            isProcessing = false;
        }, success ? 2500 : 3000);
    }

    // ============================================
    // LOG
    // ============================================
    // Only logs actual person entry/exit — no system messages
    function addToLog(name, type, role, time) {
        if (!type || type === 'info') return; // silently drop system messages

        const feed = document.getElementById('logFeed');
        const empty = document.getElementById('emptyLogMsg');
        if (empty) empty.remove();

        const isEntry = type === 'entry';
        const isExit  = type === 'exit';
        if (!isEntry && !isExit) return; // only entry/exit in the log

        const item = document.createElement('div');
        item.className = 'log-item ' + type;

        const rolePill = role
            ? `<span class="role-pill ${role}">${role.toUpperCase()}</span>`
            : '';

        const timeStr = time || new Date().toLocaleTimeString('en-US', { hour12: false });

        item.innerHTML = `
            <div class="log-name">${name}</div>
            <div class="log-meta">
                <span class="action-pill ${type}">${type.toUpperCase()}</span>
                ${rolePill}
                <span>${timeStr}</span>
            </div>
        `;

        feed.insertBefore(item, feed.firstChild);
        while (feed.children.length > 30) feed.removeChild(feed.lastChild);
    }

    // ============================================
    // RIGHT PANEL TABS
    // ============================================
    function switchRightTab(tab) {
        const isActivity = tab === 'activity';
        document.getElementById('panelActivity').style.display = isActivity ? 'flex' : 'none';
        document.getElementById('panelInside').style.display  = isActivity ? 'none'  : 'flex';

        const tA = document.getElementById('tabActivity');
        const tI = document.getElementById('tabInside');
        if (isActivity) {
            tA.style.background   = 'rgba(59,130,246,0.1)';
            tA.style.borderBottomColor = '#3b82f6';
            tA.style.color        = '#3b82f6';
            tI.style.background   = 'transparent';
            tI.style.borderBottomColor = 'transparent';
            tI.style.color        = '#475569';
        } else {
            tI.style.background   = 'rgba(16,185,129,0.1)';
            tI.style.borderBottomColor = '#10b981';
            tI.style.color        = '#10b981';
            tA.style.background   = 'transparent';
            tA.style.borderBottomColor = 'transparent';
            tA.style.color        = '#475569';
        }
    }

    // ============================================
    // INSIDE NOW — fetch & render
    // ============================================
    async function refreshInsidePanel() {
        try {
            const eId = document.getElementById('activeEventId').value || 0;
            const res = await fetch('../api/attendance.php?action=inside&event_id=' + eId);
            const data = await res.json();
            if (!data.success) return;

            const feed = document.getElementById('insideFeed');
            const badge = document.getElementById('insideBadge');
            badge.textContent = data.people.length;

            if (!data.people.length) {
                feed.innerHTML = `<div class="empty-log" id="emptyInsideMsg">
                    <i class="fa-solid fa-door-open" style="font-size:2.5rem;opacity:0.15;"></i>
                    <div style="font-size:0.85rem;font-weight:600;">No one inside yet</div>
                </div>`;
                return;
            }

            feed.innerHTML = data.people.map(p => `
                <div class="inside-item" style="display:flex;align-items:center;gap:0.8rem;padding:0.8rem;border-radius:10px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);margin-bottom:0.4rem;">
                    <div class="inside-avatar">${p.name.charAt(0).toUpperCase()}</div>
                    <div style="flex:1;overflow:hidden;min-width:0;">
                        <div class="inside-name" style="font-weight:700;font-size:0.85rem;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.name}</div>
                        <div class="inside-meta" style="display:flex;align-items:center;gap:0.4rem;margin-top:0.2rem;">
                            <span class="role-pill ${p.role}">${p.role.toUpperCase()}</span>
                            <span style="font-size:0.65rem;color:#475569;">In since ${p.since}</span>
                        </div>
                    </div>
                    <button onclick="manualExit(${p.log_id}, this)"
                        style="flex-shrink:0;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#ef4444;border-radius:8px;padding:0.35rem 0.7rem;font-size:0.65rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;cursor:pointer;transition:0.2s;"
                        onmouseover="this.style.background='rgba(239,68,68,0.2)'"
                        onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                        <i class="fa-solid fa-right-from-bracket"></i> Exit
                    </button>
                </div>
            `).join('');
        } catch(e) { console.warn('Inside refresh failed', e); }
    }

    async function manualExit(logId, btn) {
        btn.disabled = true;
        btn.textContent = '...';
        try {
            const res = await fetch('../api/attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'manual_exit', log_id: logId })
            });
            const data = await res.json();
            if (data.success) {
                playBeep(800, 100);
                // Remove the row immediately
                const row = btn.closest('.inside-item');
                if (row) row.remove();
                // Refresh all stats
                refreshDashboardStats();
                refreshInsidePanel();
                addToLog('Manual exit recorded', 'exit', '', new Date().toLocaleTimeString('en-US', { hour12: false }));
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-right-from-bracket"></i> Exit';
                alert(data.error || 'Exit failed');
            }
        } catch(e) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-right-from-bracket"></i> Exit';
        }
    }

    // Auto-refresh inside panel every 15 seconds
    setInterval(refreshInsidePanel, 15000);

    function clearLog() {
        const feed = document.getElementById('logFeed');
        feed.innerHTML = `
            <div class="empty-log" id="emptyLogMsg">
                <i class="fa-solid fa-fingerprint" style="font-size:2.5rem;opacity:0.15;"></i>
                <div style="font-size:0.85rem;font-weight:600;">Waiting for scan events...</div>
            </div>
        `;
    }

    // ============================================
    // STATS UPDATE
    // ============================================
    function updateStats() {
        document.getElementById('countInside').textContent = insideNow;
        document.getElementById('countToday').textContent = entriesToday;
        document.getElementById('countInternal').textContent = internalNow;
        document.getElementById('countExternal').textContent = externalNow;

        const pct = Math.min((insideNow / 500) * 100, 100);
        document.getElementById('capacityFill').style.width = pct + '%';
    }

    function refreshDashboardStats() {
        // Fetch new counts from server instead of local incrementing
        fetch('../api/stats.php?action=gate_counts')
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    insideNow = res.inside;
                    entriesToday = res.total;
                    document.getElementById('countInside').innerText = res.inside;
                    document.getElementById('countToday').innerText = res.total;
                    document.getElementById('countInternal').innerText = res.internal;
                    document.getElementById('countExternal').innerText = res.external;
                    updateStats();
                }
            });
    }

    // Auto-refresh every 30 seconds to keep dashboard "Live"
    setInterval(() => {
        refreshDashboardStats();
        refreshInsidePanel();
    }, 30000);

    // ============================================
    // MANUAL ENTRY
    // ============================================
    function processManualEntry() {
        const input = document.getElementById('manualTicketId');
        const token = input.value.trim();
        if (!token) return;
        onScanSuccess(token);
        input.value = '';
    }

    document.getElementById('manualTicketId').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') processManualEntry();
    });

    // ============================================
    // FULLSCREEN
    // ============================================
    function toggleFullScreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen && document.exitFullscreen();
        }
    }

    // ============================================
    // Professional Logout with Confirmation
    async function confirmLogout() {
        if (typeof Swal === 'undefined') {
            if (confirm("Are you sure you want to end your security shift?")) {
                window.location.href = '/Project/EntryX/api/auth.php?action=logout';
            }
            return;
        }

        const confirmResult = await Swal.fire({
            title: 'End Shift?',
            text: 'Are you sure you want to log out of the security terminal?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
            confirmButtonText: '<i class="fa-solid fa-power-off"></i> Logout',
            cancelButtonText: 'Cancel',
            background: '#0a0a0a',
            color: '#fff',
            customClass: {
                popup: 'glass-panel'
            }
        });

        if (confirmResult.isConfirmed) {
            Swal.fire({
                title: 'Logging Out...',
                text: 'Ending Session...',
                icon: 'info',
                background: '#0a0a0a',
                color: '#fff',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
                willClose: () => {
                    window.location.href = '/Project/EntryX/api/auth.php?action=logout';
                }
            });
        }
    }

    // CLOCK
    // ============================================
    function updateClock() {
        document.getElementById('liveTime').textContent =
            new Date().toLocaleTimeString('en-US', { hour12: false });
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Initialize mode UI
    updateModeUI('idle');
</script>

<?php require_once '../includes/footer.php'; ?>