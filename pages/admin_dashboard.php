<?php
// Session and Authentication Checks MUST come before any includes
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/Project/EntryX');
    session_start();
}

// Access Control - Super Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: admin_login.php');
    exit;
}

require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../classes/Event.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$userRole = $_SESSION['role'];

$eventObj = new Event($pdo);
$allEvents = $eventObj->getAllEvents(true);
// Filter out system-generated campus admission events from admin view
$events = array_filter($allEvents, function($evt) {
    $name = strtolower($evt['name']);
    return strpos($name, 'general campus admission') === false
        && strpos($name, 'campus admission') === false
        && strpos($name, 'general admission') === false;
});

// Fetch Sub-Admins for Management
$stmtAdmins = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE role IN ('event_admin', 'security') ORDER BY created_at DESC");
$stmtAdmins->execute();
$subAdmins = $stmtAdmins->fetchAll();

// Initial stats for page load (will be updated live via JS)
$totalEvents = count($events);
$stmtReg = $pdo->query("SELECT COUNT(*) FROM registrations r JOIN events e ON r.event_id = e.id WHERE e.name NOT LIKE '%General%Admission%' AND e.name NOT LIKE '%Campus Admission%'");
$totalReg = $stmtReg->fetchColumn();
$stmtExt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'external'");
$externalCount = $stmtExt->fetchColumn();
$stmtInside = $pdo->query("SELECT COUNT(*) FROM attendance_logs WHERE status = 'inside'");
$insideCount = $stmtInside->fetchColumn();
?>

<style>
    .dashboard-container {
        padding: 2rem 0;
        position: relative;
    }

    /* Dashboard Top Section */
    .welcome-section {
        position: relative;
        margin-bottom: 4rem;
    }

    /* Top Bar with Logout and User Info */
    .dashboard-top-bar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding: 0 1rem;
    }

    /* Premium Header */
    .admin-hero-card {
        background: linear-gradient(135deg, rgba(255, 31, 31, 0.05) 0%, rgba(10, 10, 10, 0.8) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 31, 31, 0.15);
        border-radius: 32px;
        padding: 4rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }

    .admin-hero-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(255, 31, 31, 0.1), transparent 60%);
        filter: blur(60px);
        animation: float 8s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(5deg);
        }
    }

    .hero-text h1 {
        font-size: 4rem;
        font-weight: 900;
        letter-spacing: -0.05em;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Stats Grid Enhancements */
    .stats-matrix {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 2.5rem;
        margin-bottom: 5rem;
    }

    /* Live indicator for stat cards */
    .live-pulse-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        box-shadow: 0 0 8px #10b981;
        animation: livePulse 1.5s ease-in-out infinite;
        margin-left: 0.5rem;
        vertical-align: middle;
    }

    @keyframes livePulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.8); }
    }

    .live-badge-strip {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 999px;
        padding: 0.3rem 0.9rem;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #10b981;
    }

    .stat-update-flash {
        animation: statFlash 0.4s ease-out;
    }

    @keyframes statFlash {
        0% { color: #10b981; transform: scale(1.05); }
        100% { color: white; transform: scale(1); }
    }

    /* campus admission notice banner */
    .admission-system-note {
        background: rgba(99, 102, 241, 0.06);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 14px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.85rem;
        color: #a5b4fc;
    }

    .stat-matrix-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--p-border);
        border-radius: 24px;
        padding: 2.5rem;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .stat-matrix-card:hover {
        background: rgba(255, 255, 255, 0.04);
        transform: translateY(-8px);
        border-color: rgba(255, 31, 31, 0.3);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    .stat-icon-alpha {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
    }

    .stat-matrix-card:hover .stat-icon-alpha {
        transform: scale(1.1);
    }

    /* Action Buttons */
    .action-group {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    /* Re-using premium logout styles */
    .logout-btn-premium {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
        padding: 0.9rem 2rem;
        border-radius: 999px;
        font-size: 0.9rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        cursor: pointer;
        transition: 0.4s;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .logout-btn-premium:hover {
        background: rgba(239, 68, 68, 0.2);
        transform: translateY(-2px);
    }

    .user-info-badge {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.7rem 1.8rem;
        border-radius: 999px;
        font-size: 0.9rem;
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .role-badge-admin {
        padding: 0.3rem 1rem;
        background: rgba(255, 31, 31, 0.2);
        border-radius: 999px;
        color: var(--p-brand);
        font-size: 0.7rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    /* Global Select & Dropdown Visibility Fix */
    select {
        background-color: #1a1a1a !important;
        color: white !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        cursor: pointer;
    }

    select option {
        background-color: #1a1a1a !important;
        color: white !important;
        padding: 1rem !important;
    }

    /* Specific fix for Payment & Modal Selects */
    #externalProgramModal select,
    #eventModal select,
    #adminModal select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.25rem;
        padding-right: 3rem !important;
        appearance: none;
        -webkit-appearance: none;
    }

    /* ============================================================
       ADMIN DASHBOARD — PROFESSIONAL MOBILE REFACTOR (≤ 768px)
       ============================================================ */
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem 0 !important;
            max-width: 100% !important;
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 1.5rem !important;
        }

        /* ── TOP NAVIGATION CONTEXT ──
           Tighten the header navigation if present
        */
        .nav-standard .container {
            padding: 0 1rem !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }

        /* ── DASHBOARD TOP BAR ──
           [ 🛡 User Name (Badge) ]    [⏻ Logout]
        */
        .dashboard-top-bar {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin: 0 1rem 0.5rem !important;
            padding: 0.85rem 1.25rem !important;
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 16px !important;
            backdrop-filter: blur(15px) !important;
            gap: 1rem !important;
        }

        .user-info-badge {
            background: rgba(255, 31, 31, 0.05) !important;
            border: 1px solid rgba(255, 31, 31, 0.1) !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.85rem !important;
            border-radius: 12px !important;
            flex: 1 !important;
            min-width: 0 !important;
            justify-content: flex-start !important;
        }

        .user-info-badge span:first-of-type {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 120px !important;
        }

        .role-badge-admin {
            font-size: 0.6rem !important;
            padding: 0.2rem 0.6rem !important;
            background: rgba(255, 31, 31, 0.2) !important;
            flex-shrink: 0 !important;
        }

        .logout-btn-premium {
            padding: 0.6rem 1.2rem !important;
            font-size: 0.8rem !important;
            border-radius: 12px !important;
            background: rgba(239, 68, 68, 0.08) !important;
            border: 1px solid rgba(239, 68, 68, 0.2) !important;
            width: auto !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .logout-btn-premium span {
            display: none !important; /* Hide 'Logout' text, show icon only or keep it compact */
        }
        .logout-btn-premium::after {
            content: 'OFF' !important;
            margin-left: 4px;
            font-weight: 800;
        }

        /* ── ADMIN HERO CARD ──
           Centered column layout, centered text
        */
        .admin-hero-card {
            flex-direction: column !important;
            padding: 2.5rem 1.25rem !important;
            text-align: center !important;
            border-radius: 24px !important;
            margin: 0 1rem !important;
            gap: 2rem !important;
            align-items: center !important;
        }

        .admin-hero-card .hero-text h4 {
            font-size: 0.65rem !important;
            letter-spacing: 0.3em !important;
            color: #ff3131 !important;
            margin-bottom: 0.75rem !important;
        }

        .admin-hero-card .hero-text h1,
        .hero-text h1 {
            font-size: 2.25rem !important;
            line-height: 1.1 !important;
            margin-bottom: 1rem !important;
        }

        .admin-hero-card .hero-text p {
            font-size: 0.95rem !important;
            max-width: 300px !important;
            margin: 0 auto !important;
            opacity: 0.8 !important;
        }

        /* ── ACTION BUTTONS ──
           Standardized, high-impact buttons
        */
        .action-group {
            width: 100% !important;
            background: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            border: none !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
        }

        .action-group > div {
            padding: 0 !important;
            background: none !important;
            border: none !important;
        }

        /* Primary Call to Action: ADD NEW EVENT */
        .btn-primary {
            width: 100% !important;
            padding: 1.1rem !important;
            font-size: 0.95rem !important;
            letter-spacing: 0.08em !important;
            border-radius: 14px !important;
            background: linear-gradient(135deg, #ff3131 0%, #a80000 100%) !important;
            box-shadow: 0 10px 25px rgba(255, 49, 49, 0.4) !important;
            border: none !important;
            height: auto !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            font-weight: 800 !important;
        }

        /* Sub Buttons: SUB-ADMIN + RESULTS */
        .action-group [style*="grid-template-columns: 1fr 1fr"] {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 0.75rem !important;
        }

        .btn-outline {
            padding: 0.9rem 0.5rem !important;
            font-size: 0.75rem !important;
            border-radius: 12px !important;
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            height: auto !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            font-weight: 700 !important;
        }

        /* Results button specific yellow border override */
        .btn-outline[style*="rgba(234, 179, 8, 0.4)"] {
            border-color: rgba(234, 179, 8, 0.4) !important;
            color: #eab308 !important;
        }

        /* ── STATS MATRIX ──
           2-column grid for better horizontal space usage
        */
        .stats-matrix {
            grid-template-columns: 1fr 1fr !important;
            gap: 1rem !important;
            padding: 0 1rem !important;
            margin-bottom: 2rem !important;
        }

        .stat-matrix-card {
            padding: 1.5rem 1rem !important;
            border-radius: 20px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
        }

        .stat-icon-alpha {
            width: 48px !important;
            height: 48px !important;
            border-radius: 14px !important;
            font-size: 1.1rem !important;
            margin-bottom: 1rem !important;
        }

        .stat-matrix-card h3 {
            font-size: 0.7rem !important;
            letter-spacing: 0.05em !important;
            margin-bottom: 0.4rem !important;
        }

        .stat-matrix-card p {
            font-size: 1.85rem !important;
            font-weight: 900 !important;
        }

        /* ── INFRASTRUCTURE CONTROL TABLE ── */
        .glass-panel[style*="padding: 3rem"] {
            padding: 1.5rem !important;
            margin: 0 1rem !important;
            border-radius: 24px !important;
        }

        .glass-panel h2 {
            font-size: 1.4rem !important;
            justify-content: center !important;
            margin-bottom: 1.5rem !important;
        }

        /* Hide complicated table headers, stack the content */
        table thead { display: none !important; }
        table tbody tr {
            display: flex !important;
            flex-direction: column !important;
            padding: 1rem !important;
            border-radius: 16px !important;
            margin-bottom: 1rem !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
        }

        table tbody td {
            padding: 0.4rem 0 !important;
            border: none !important;
            text-align: center !important;
        }

        table tbody td:first-child {
            font-size: 1.1rem !important;
            color: #ff3131 !important;
        }

        table tbody td:last-child {
            justify-content: center !important;
            display: flex !important;
            padding-top: 1rem !important;
        }

        /* ── MISC ── */
        .admission-system-note {
            margin: 0 1rem 1rem !important;
            padding: 0.85rem !important;
            font-size: 0.75rem !important;
        }
    }

    /* Small Phone adjustments */
    @media (max-width: 480px) {
        .admin-hero-card .hero-text h1 {
            font-size: 1.85rem !important;
        }
        .stats-matrix {
            grid-template-columns: 1fr !important;
        }
    }
</style>


<div class="dashboard-container">
    <!-- Top Bar -->
    <div class="dashboard-top-bar reveal">
        <div class="user-info-badge">
            <i class="fa-solid fa-shield-halved" style="color: var(--p-brand);"></i>
            <span><?php echo htmlspecialchars($userName); ?></span>
            <span class="role-badge-admin">SUPER ADMIN</span>
        </div>
        <button class="logout-btn-premium" onclick="confirmLogout()">
            <i class="fa-solid fa-power-off"></i>
            <span>Logout</span>
        </button>
    </div>

    <!-- Admin Management Hub -->
    <div class="admin-hero-card reveal">
        <div class="hero-text">
            <h4
                style="color: var(--p-brand); text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.75rem; margin-bottom: 0.8rem; font-weight: 800;">
                Superuser Level Authorization</h4>
            <h1>Super Admin <span style="color: var(--p-brand);">Console</span></h1>
            <p style="color: var(--p-text-dim); font-size: 1.25rem;">Directly manage your events and coordinate your
                administrative team.</p>
        </div>
        <div class="action-group"
            style="background: rgba(255,255,255,0.03); padding: 2rem; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05);">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <button class="btn btn-primary"
                    style="border-radius: 12px; padding: 1.2rem; min-width: 250px; justify-content: center; font-weight: 800; letter-spacing: 0.05em;"
                    onclick="openModal()">
                    <i class="fa-solid fa-plus-circle"></i> ADD NEW EVENT
                </button>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button class="btn btn-outline"
                        style="border-radius: 12px; border-color: rgba(255,255,255,0.1); color: white;"
                        onclick="document.getElementById('adminModal').style.display='flex'">
                        <i class="fa-solid fa-user-shield"></i> + SUB-ADMIN
                    </button>
                    <a href="publish_result.php" class="btn btn-outline"
                        style="border-radius: 12px; border-color: rgba(234, 179, 8, 0.4); color: #eab308;">
                        <i class="fa-solid fa-trophy"></i> RESULTS
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Stats Header Bar -->
    <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1.5rem;">
        <div class="live-badge-strip" id="liveSyncBadge">
            <span class="live-pulse-dot"></span>
            <span>LIVE</span>
            <span id="lastSyncTime" style="opacity: 0.7; font-weight: 600;">--:--:--</span>
        </div>
    </div>

    <!-- Stats Matrix -->
    <div class="stats-matrix">
        <div class="stat-matrix-card reveal">
            <div class="stat-icon-alpha" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                <i class="fa-solid fa-calendar-nodes"></i>
            </div>
            <h3 style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem;">Total Events</h3>
            <p id="stat-events" style="font-size: 3rem; font-weight: 900; color: white; line-height: 1; transition: color 0.3s;"><?php echo $totalEvents; ?></p>
        </div>
        <div class="stat-matrix-card reveal" style="animation-delay: 0.1s;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div class="stat-icon-alpha" style="background: rgba(16, 185, 129, 0.1); color: #10b981; margin-bottom: 0;">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
                <span class="live-badge-strip" style="font-size: 0.6rem; padding: 0.2rem 0.6rem;">
                    <span class="live-pulse-dot" style="width: 6px; height: 6px;"></span> LIVE
                </span>
            </div>
            <h3 style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem;">Outside Guests Inside</h3>
            <p id="stat-external" style="font-size: 3rem; font-weight: 900; color: white; line-height: 1; transition: color 0.3s;">0</p>
            <div style="font-size: 0.75rem; color: #475569; margin-top: 0.4rem; font-weight: 600;">Currently on campus</div>
        </div>
        <div class="stat-matrix-card reveal" style="animation-delay: 0.2s;">
            <div class="stat-icon-alpha" style="background: rgba(234, 179, 8, 0.1); color: #eab308;">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <h3 style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem;">Registrations</h3>
            <p id="stat-reg" style="font-size: 3rem; font-weight: 900; color: white; line-height: 1; transition: color 0.3s;"><?php echo $totalReg; ?></p>
        </div>
        <div class="stat-matrix-card reveal" style="animation-delay: 0.3s;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div class="stat-icon-alpha" style="background: rgba(239, 68, 68, 0.1); color: var(--p-brand); margin-bottom: 0;">
                    <i class="fa-solid fa-wifi fa-fade"></i>
                </div>
                <span class="live-badge-strip" style="font-size: 0.6rem; padding: 0.2rem 0.6rem;">
                    <span class="live-pulse-dot" style="width: 6px; height: 6px;"></span> LIVE
                </span>
            </div>
            <h3 style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem;">People Inside Campus</h3>
            <p id="stat-inside" style="font-size: 3rem; font-weight: 900; color: white; line-height: 1; transition: color 0.3s;"><?php echo $insideCount; ?></p>
            <div style="display: flex; gap: 0.8rem; margin-top: 0.8rem;">
                <div style="flex: 1; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.15); border-radius: 10px; padding: 0.5rem 0.8rem; text-align: center;">
                    <div id="stat-internal" style="font-size: 1.2rem; font-weight: 900; color: #10b981;">0</div>
                    <div style="font-size: 0.6rem; color: #475569; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;">Internal</div>
                </div>
                <div style="flex: 1; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.15); border-radius: 10px; padding: 0.5rem 0.8rem; text-align: center;">
                    <div id="stat-external-inside" style="font-size: 1.2rem; font-weight: 900; color: #f59e0b;">0</div>
                    <div style="font-size: 0.6rem; color: #475569; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;">External</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Infrastructure Control Table -->
    <div class="glass-panel reveal"
        style="padding: 3rem; border-color: rgba(255,31,31,0.1); box-shadow: 0 40px 80px rgba(0,0,0,0.4); border-radius: 32px;">
        <h2
            style="margin-bottom: 2.5rem; display: flex; align-items: center; gap: 1rem; font-size: 1.8rem; color: white;">
            <i class="fa-solid fa-list-check" style="color: var(--p-brand);"></i> Manage Events
        </h2>
        <table style="width: 100%; border-collapse: separate; border-spacing: 0 1rem;">
            <thead>
                <tr
                    style="text-align: left; color: var(--p-text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.15em;">
                    <th style="padding: 0 1.5rem;">Event Name</th>
                    <th>Date & Time</th>
                    <th>Event Venue</th>
                    <th>Status</th>
                    <th style="text-align: right; padding: 0 1.5rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr style="background: rgba(255,255,255,0.02); transition: all 0.3s;"
                        onmouseover="this.style.background='rgba(255,255,255,0.04)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                        <td
                            style="padding: 1.5rem; border-radius: 16px 0 0 16px; font-weight: 700; color: white; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <?php echo htmlspecialchars($event['name']); ?>
                        </td>
                        <td
                            style="color: var(--p-text-dim); border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <div style="font-size: 0.9rem;"><?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                            </div>
                            <div style="font-size: 0.75rem; opacity: 0.6;">
                                <?php echo date('h:i A', strtotime($event['event_date'])); ?>
                            </div>
                        </td>
                        <td
                            style="color: var(--p-text-dim); border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <?php echo htmlspecialchars($event['venue']); ?>
                        </td>
                        <td
                            style="border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <?php
                            $statusClass = 'status-pending';
                            if ($event['status'] === 'active')
                                $statusClass = 'status-inside';
                            if ($event['status'] === 'cancelled')
                                $statusClass = 'status-outside';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo strtoupper($event['status']); ?>
                            </span>
                        </td>
                        <td
                            style="text-align: right; padding: 1.5rem; border-radius: 0 16px 16px 0; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <button class="user-avatar-nav"
                                style="width: 36px; height: 36px; border-radius: 10px; color: #3b82f6; border-color: rgba(59, 130, 246, 0.1);"
                                title="View Registrations"
                                onclick="viewRegistrations(<?php echo $event['id']; ?>, '<?php echo addslashes($event['name']); ?>')">
                                <i class="fa-solid fa-users"></i>
                            </button>
                            <button class="user-avatar-nav" style="width: 36px; height: 36px; border-radius: 10px;"
                                title="Edit Event" onclick="editEvent(<?php echo $event['id']; ?>)">
                                <i class="fa-solid fa-pen-nib"></i>
                            </button>
                            <button class="user-avatar-nav"
                                style="width: 36px; height: 36px; border-radius: 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.1);"
                                title="Delete Event" onclick="deleteEvent(<?php echo $event['id']; ?>)">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
        </div>
        </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>

<!-- Admin Management -->
<div class="glass-panel reveal"
    style="padding: 3rem; margin-top: 4rem; border-color: rgba(255,31,31,0.1); box-shadow: 0 40px 80px rgba(0,0,0,0.4); border-radius: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <h2 style="display: flex; align-items: center; gap: 1rem; font-size: 1.8rem; color: white; margin: 0;">
            <i class="fa-solid fa-user-shield" style="color: var(--p-brand);"></i> Admin Management
        </h2>
        <button class="btn btn-primary" style="padding: 0.8rem 1.5rem; font-size: 0.9rem;"
            onclick="document.getElementById('adminModal').style.display='flex'">
            <i class="fa-solid fa-user-plus"></i> Add New Admin
        </button>
    </div>

    <table style="width: 100%; border-collapse: separate; border-spacing: 0 1rem;">
        <thead>
            <tr
                style="text-align: left; color: var(--p-text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.15em;">
                <th style="padding: 0 1.5rem;">Admin Details</th>
                <th>Admin Role</th>
                <th>Joined Date</th>
                <th style="text-align: right; padding: 0 1.5rem;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subAdmins as $admin): ?>
                <tr style="background: rgba(255,255,255,0.02); transition: all 0.3s; border-radius: 16px;">
                    <td
                        style="padding: 1.5rem; border-radius: 16px 0 0 16px; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <div style="color: white; font-weight: 700;"><?php echo htmlspecialchars($admin['name']); ?>
                        </div>
                        <div style="color: var(--p-text-dim); font-size: 0.85rem;">
                            <?php echo htmlspecialchars($admin['email']); ?>
                        </div>
                    </td>
                    <td
                        style="border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <span class="role-badge-admin"
                            style="background: <?php echo $admin['role'] === 'event_admin' ? 'rgba(79, 70, 229, 0.2)' : 'rgba(234, 179, 8, 0.2)'; ?>; color: <?php echo $admin['role'] === 'event_admin' ? '#818cf8' : '#eab308'; ?>;">
                            <?php echo str_replace('_', ' ', strtoupper($admin['role'])); ?>
                        </span>
                    </td>
                    <td
                        style="color: var(--p-text-dim); border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <?php echo date('M d, Y', strtotime($admin['created_at'])); ?>
                    </td>
                    <td
                        style="text-align: right; padding: 1.5rem; border-radius: 0 16px 16px 0; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <button class="user-avatar-nav"
                            style="width: 36px; height: 36px; border-radius: 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.1);"
                            onclick="deleteUser(<?php echo $admin['id']; ?>, '<?php echo addslashes($admin['name']); ?>')">
                            <i class="fa-solid fa-user-minus"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($subAdmins)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--p-text-muted);">
                        No admins found in the records.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- External Programs Management -->
<div class="glass-panel reveal"
    style="padding: 3rem; margin-top: 4rem; border-color: rgba(16, 185, 129, 0.2); box-shadow: 0 40px 80px rgba(0,0,0,0.4); border-radius: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h2
                style="display: flex; align-items: center; gap: 1rem; font-size: 1.8rem; color: white; margin: 0 0 0.5rem 0;">
                <i class="fa-solid fa-globe" style="color: #10b981;"></i> External Programs
            </h2>
            <p style="color: var(--p-text-dim); font-size: 0.9rem; margin: 0;">
                Control public registration visibility and manage external participant programs
            </p>
        </div>
        <button class="btn btn-primary"
            style="padding: 0.8rem 1.5rem; font-size: 0.9rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);"
            onclick="openExternalProgramModal()">
            <i class="fa-solid fa-plus-circle"></i> Create Program
        </button>
    </div>

    <!-- Current Status Banner -->
    <div id="externalRegStatus"
        style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <i class="fa-solid fa-circle-xmark" style="color: #ef4444; font-size: 1.5rem;"></i>
            <div>
                <div style="font-weight: 700; color: white; margin-bottom: 0.3rem;">External Registration: DISABLED
                </div>
                <div style="color: var(--p-text-dim); font-size: 0.85rem;">Public registration is currently not
                    visible on the landing page</div>
            </div>
        </div>
    </div>

    <!-- Programs Table -->
    <div id="programsTableContainer">
        <p style="text-align: center; color: var(--p-text-muted); padding: 2rem;">
            <i class="fa-solid fa-spinner fa-spin"></i> Loading programs...
        </p>
    </div>
</div>

<!-- Payment Verification Section -->
<div class="glass-panel reveal"
    style="padding: 3rem; margin-top: 4rem; border-color: rgba(234, 179, 8, 0.2); box-shadow: 0 40px 80px rgba(0,0,0,0.4); border-radius: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h2
                style="display: flex; align-items: center; gap: 1rem; font-size: 1.8rem; color: white; margin: 0 0 0.5rem 0;">
                <i class="fa-solid fa-receipt" style="color: #eab308;"></i> Payment Verification
            </h2>
            <p style="color: var(--p-text-dim); font-size: 0.9rem; margin: 0;">
                Review and confirm pending registrations against bank transaction records
            </p>
        </div>
        <button class="btn btn-outline"
            style="padding: 0.8rem 1.5rem; font-size: 0.9rem; border-color: rgba(234, 179, 8, 0.4); color: #eab308;"
            onclick="loadPendingPayments()">
            <i class="fa-solid fa-rotate"></i> Refresh List
        </button>
    </div>

    <div id="pendingPaymentsTableContainer">
        <p style="text-align: center; color: var(--p-text-muted); padding: 2rem;">
            <i class="fa-solid fa-spinner fa-spin"></i> Checking for pending payments...
        </p>
    </div>
</div>

<!-- Registration View Modal -->
<div id="regViewModal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 2500; justify-content: center; align-items: center; backdrop-filter: blur(15px); padding: 2rem;">
    <div class="glass-panel"
        style="width: 100%; max-width: 900px; padding: 3rem; position: relative; max-height: 85vh; overflow-y: auto; border-color: rgba(59, 130, 246, 0.2);">
        <button onclick="document.getElementById('regViewModal').style.display='none'"
            style="position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(255,255,255,0.05); border: none; width: 40px; height: 40px; border-radius: 50%; color: white; cursor: pointer;">&times;</button>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
            <div>
                <h2 id="regViewTitle" style="color: white; margin: 0 0 0.5rem 0;">Event Registrations</h2>
                <p id="regViewSub" style="color: var(--p-text-dim); margin: 0; font-size: 0.9rem;"></p>
            </div>
            <button onclick="exportRegistrations()" class="btn btn-outline"
                style="border-color: rgba(16, 185, 129, 0.3); color: #10b981;">
                <i class="fa-solid fa-file-export"></i> Export CSV
            </button>
        </div>

        <div id="regListContainer">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<!-- High-Performance Modal System -->
<div id="eventModal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(15px);">
    <div class="glass-panel"
        style="width: 100%; max-width: 650px; padding: 4rem; position: relative; max-height: 90vh; overflow-y: auto; border-color: rgba(255,31,31,0.2);">
        <button onclick="closeModal()"
            style="position: absolute; top: 2rem; right: 2rem; background: rgba(255,255,255,0.05); border: none; width: 44px; height: 44px; border-radius: 50%; color: white; cursor: pointer; transition: 0.3s;"
            onmouseover="this.style.background='var(--p-brand)'"
            onmouseout="this.style.background='rgba(255,255,255,0.05)'">&times;</button>
        <h2 id="modalTitle" style="margin-bottom: 3rem; font-size: 2rem; color: white;">Add New Event</h2>
        <form id="eventForm">
            <input type="hidden" name="id" id="eventId">
            <div style="margin-bottom: 2rem;">
                <label>Event Name</label>
                <input type="text" name="name" id="eventName" required placeholder="Name of the event">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div style="position: relative;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label style="margin: 0;">Date and Time</label>
                        <button type="button" onclick="setEventToNow()"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--p-brand); padding: 0.2rem 0.8rem; border-radius: 6px; font-size: 0.75rem; cursor: pointer; font-weight: 700;">
                            <i class="fa-solid fa-clock"></i> SET NOW
                        </button>
                    </div>
                    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1rem;">
                        <input type="date" name="event_date_only" id="eventDateOnly" required
                            style="padding: 0.8rem; border-radius: 8px;">
                        <input type="time" name="event_time_only" id="eventTimeOnly" required
                            style="padding: 0.8rem; border-radius: 8px;">
                    </div>
                    <small id="eventDateError"
                        style="color:#ef4444; font-size:0.75rem; display:none; margin-top:0.3rem;">⚠️ Event date
                        cannot be in the past.</small>
                </div>
                <div>
                    <label>Event Location</label>
                    <input type="text" name="venue" id="eventVenue" required placeholder="Where is it happening?">
                </div>
            </div>
            <div style="margin-bottom: 2rem;">
                <label>Event Details</label>
                <textarea name="description" id="eventDescription" rows="3"
                    placeholder="Tell students more about the event..."></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label>Maximum Attendees</label>
                    <input type="number" name="capacity" id="eventCapacity" value="100" min="1">
                </div>
                <div>
                    <label>Who can attend?</label>
                    <select name="type" id="eventType">
                        <option value="both">Everyone (Internal & External)</option>
                        <option value="internal">Internals Only</option>
                        <option value="external">Outside Participants Only</option>
                    </select>
                </div>
            </div>

            <!-- Event Status Section (Required for edit mode) -->
            <div id="statusSection" style="margin-bottom: 2rem; display: none;">
                <label>Event Status</label>
                <select name="status" id="eventStatus">
                    <option value="active">Active / Operational</option>
                    <option value="pending">Pending / Upcoming</option>
                    <option value="cancelled">Cancelled / Hidden</option>
                </select>
            </div>

            <div
                style="background: rgba(255,152,0,0.03); padding: 2rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid rgba(255,152,0,0.1);">
                <div style="display: flex; gap: 1.2rem; align-items: center; margin-bottom: 1rem;">
                    <input type="checkbox" id="isGroupEvent" name="is_group_event"
                        style="width: 24px; height: 24px; cursor: pointer;">
                    <label for="isGroupEvent"
                        style="margin: 0; cursor: pointer; text-transform: none; color: white; font-weight: 700;">
                        <i class="fa-solid fa-users"></i> This is a Team/Group Event
                    </label>
                </div>
                <div id="groupSettings"
                    style="display: none; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
                    <div>
                        <label>Min Team Size</label>
                        <input type="number" name="min_team_size" id="minTeamSize" value="1" min="1">
                    </div>
                    <div>
                        <label>Max Team Size</label>
                        <input type="number" name="max_team_size" id="maxTeamSize" value="1" min="1">
                    </div>
                </div>
            </div>

            <div
                style="background: rgba(255,255,255,0.03); padding: 2.5rem; border-radius: 24px; margin-bottom: 3rem; border: 1px solid rgba(255,255,255,0.08);">
                <h4
                    style="margin-bottom: 1.5rem; color: var(--p-brand); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em;">
                    Pricing & Fees</h4>
                <div style="display: flex; gap: 1.2rem; align-items: center; margin-bottom: 1.5rem;">
                    <input type="checkbox" id="isPaid" name="is_paid"
                        style="width: 24px; height: 24px; cursor: pointer;">
                    <label for="isPaid" style="margin: 0; cursor: pointer; text-transform: none; color: white;">This
                        is a Paid Event</label>
                </div>
                <div id="paidSettings" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem;">
                        <div>
                            <label>Registration Fee (₹)</label>
                            <input type="number" step="0.01" name="base_price" id="eventPrice" placeholder="0.00"
                                min="0.01">
                            <small id="eventPriceError" style="color:#ef4444; font-size:0.75rem; display:none;">⚠️
                                Fee must be greater than 0.</small>
                        </div>
                        <div>
                            <label>Apply GST for:</label>
                            <select name="gst_target" id="eventGstTarget">
                                <option value="both" selected>Both (Local & Guest)</option>
                                <option value="externals_only">Outside Guests Only</option>
                                <option value="internals_only">Internals Only</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem;">
                        <div>
                            <label>GST Rate (%)</label>
                            <input type="number" step="0.01" name="gst_rate" id="eventGst" value="18.00" min="0"
                                max="100">
                        </div>
                        <div>
                            <label>Total Amount (Inc. GST)</label>
                            <input type="text" id="eventTotalAmount" readonly
                                style="background: rgba(255,255,255,0.05); cursor: not-allowed;" placeholder="₹0.00">
                        </div>
                    </div>

                    <!-- UPI ID Field (Hidden as we use Razorpay) -->
                    <div style="margin-bottom: 1.5rem; display: none;">
                        <label>UPI ID (for Payment QR)</label>
                        <input type="text" name="payment_upi" id="eventPaymentUpi" placeholder="e.g. username@okaxis">
                        <small style="color: var(--p-text-dim); font-size: 0.75rem;">Used to generate registration
                            QR code</small>
                    </div>

                    <!-- GST Breakdown Preview -->
                    <div id="eventGstBreakdown"
                        style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 12px; padding: 1.2rem; display: none;">
                        <div style="font-size: 0.85rem; color: var(--p-text-dim); margin-bottom: 0.5rem;">
                            <strong style="color: #10b981;">Pricing Summary:</strong>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.3rem;">
                            <span>Base Registration Fee:</span>
                            <span id="eventBreakdownBase" style="color: white; font-weight: 600;">₹0.00</span>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.3rem;">
                            <span>GST (<span id="eventBreakdownRate">18</span>%):</span>
                            <span id="eventBreakdownGst" style="color: white; font-weight: 600;">₹0.00</span>
                        </div>
                        <div
                            style="border-top: 1px solid rgba(16, 185, 129, 0.1); margin: 0.5rem 0; padding-top: 0.5rem; display: flex; justify-content: space-between;">
                            <strong style="color: white;">Effective Total:</strong>
                            <strong id="eventBreakdownTotal" style="color: #10b981; font-size: 1.1rem;">₹0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 2rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 2;" id="saveBtn">Publish Event</button>
            </div>
        </form>
    </div>
</div>
</div>

<style>
    .dashboard-container {
        padding: 4rem 0;
    }

    table tr:hover {
        transform: scale(1.002);
    }
</style>


<!-- Create Admin Modal -->
<div id="adminModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;">
    <div class="glass-panel" style="width: 100%; max-width: 400px; padding: 2rem; position: relative;">
        <button onclick="document.getElementById('adminModal').style.display='none'"
            style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h2 style="margin-bottom: 1.5rem;">Add Event Admin</h2>
        <form id="createAdminForm">
            <div>
                <label>Name</label>
                <input type="text" name="name" required minlength="2" placeholder="Full name">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" required placeholder="admin@example.com">
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" required minlength="6" placeholder="Min 6 characters">
                <small style="color:var(--p-text-dim); font-size:0.72rem; display:block; margin-top:0.3rem;">Minimum 6
                    characters</small>
            </div>
            <div>
                <label>Access Role</label>
                <select name="role" required>
                    <option value="event_admin">Event Coordinator</option>
                    <option value="security">Security Officer</option>
                </select>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;"
                    onclick="document.getElementById('adminModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Create Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- External Program Modal -->
<div id="externalProgramModal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(15px);">
    <div class="glass-panel"
        style="width: 100%; max-width: 700px; padding: 4rem; position: relative; max-height: 90vh; overflow-y: auto; border-color: rgba(16, 185, 129, 0.3);">
        <button onclick="closeExternalProgramModal()"
            style="position: absolute; top: 2rem; right: 2rem; background: rgba(255,255,255,0.05); border: none; width: 44px; height: 44px; border-radius: 50%; color: white; cursor: pointer; transition: 0.3s;"
            onmouseover="this.style.background='#10b981'"
            onmouseout="this.style.background='rgba(255,255,255,0.05)'">&times;</button>
        <h2 id="externalProgramModalTitle" style="margin-bottom: 3rem; font-size: 2rem; color: white;">Create External
            Program</h2>
        <form id="externalProgramForm">
            <input type="hidden" name="id" id="externalProgramId">

            <div style="margin-bottom: 2rem;">
                <label>Program Name <span style="color: #ef4444;">*</span></label>
                <input type="text" name="program_name" id="programName" required
                    placeholder="e.g., Tech Fest 2026, Annual Symposium">
            </div>

            <div style="margin-bottom: 2rem;">
                <label>Program Description</label>
                <textarea name="program_description" id="programDescription" rows="4"
                    placeholder="Describe the program for external participants..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label>Start Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="start_date" id="programStartDate" required>
                    <small id="programStartDateError"
                        style="color:#ef4444; font-size:0.75rem; display:none; margin-top:0.3rem;">⚠️ Start date cannot
                        be in the past.</small>
                </div>
                <div>
                    <label>End Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="end_date" id="programEndDate" required>
                    <small id="programEndDateError"
                        style="color:#ef4444; font-size:0.75rem; display:none; margin-top:0.3rem;">⚠️ End date must be
                        on or after start date.</small>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <label>Maximum Number of External Entries</label>
                <input type="number" name="max_participants" id="programMaxParticipants" value="500" min="1"
                    placeholder="500">
                <small style="color: var(--p-text-dim); font-size: 0.75rem; display: block; margin-top: 0.3rem;">
                    Maximum external participants allowed for this program
                </small>
            </div>

            <!-- Payment & GST Configuration -->
            <div
                style="background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.2); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem;">
                <h4
                    style="color: #eab308; margin-bottom: 1.5rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.1em;">
                    <i class="fa-solid fa-indian-rupee-sign"></i> Payment Configuration
                </h4>

                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <input type="checkbox" id="programIsPaid" name="is_paid"
                        style="width: 24px; height: 24px; cursor: pointer;">
                    <label for="programIsPaid"
                        style="margin: 0; cursor: pointer; text-transform: none; color: white; font-weight: 600;">
                        This is a Paid Program (Require registration fee)
                    </label>
                </div>

                <div id="paymentSettings" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem;">
                        <div>
                            <label>Registration Fee (₹) <span style="color: #ef4444;">*</span></label>
                            <input type="number" step="0.01" name="registration_fee" id="programRegistrationFee"
                                placeholder="0.01" min="0.01">
                            <small id="programFeeError"
                                style="color:#ef4444; font-size:0.75rem; display:none; margin-top:0.3rem;">⚠️ Fee must
                                be greater than 0.</small>
                            <small id="programFeeHint"
                                style="color: var(--p-text-dim); font-size: 0.75rem; display: block; margin-top: 0.3rem;">
                                Base amount before GST
                            </small>
                        </div>
                        <div>
                            <label>Currency</label>
                            <select name="currency" id="programCurrency">
                                <option value="INR" selected>INR (₹)</option>
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                            </select>
                        </div>
                    </div>

                    <!-- GST Configuration -->
                    <div
                        style="background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.2rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <input type="checkbox" id="programGstEnabled" name="is_gst_enabled"
                                style="width: 20px; height: 20px; cursor: pointer;">
                            <label for="programGstEnabled"
                                style="margin: 0; cursor: pointer; text-transform: none; color: white; font-weight: 600; font-size: 0.9rem;">
                                <i class="fa-solid fa-percent"></i> Enable GST (Goods and Services Tax)
                            </label>
                        </div>

                        <div id="gstSettings" style="display: none;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div>
                                    <label>GST Rate (%)</label>
                                    <input type="number" step="0.01" name="gst_rate" id="programGstRate" value="18.00"
                                        min="0" max="100">
                                    <small
                                        style="color: var(--p-text-dim); font-size: 0.75rem; display: block; margin-top: 0.3rem;">
                                        Standard rate: 18%
                                    </small>
                                </div>
                                <div>
                                    <label>Total Amount (Auto-calculated)</label>
                                    <input type="text" id="programTotalAmount" readonly
                                        style="background: rgba(255,255,255,0.05); cursor: not-allowed;"
                                        placeholder="₹0.00">
                                    <small
                                        style="color: var(--p-text-dim); font-size: 0.75rem; display: block; margin-top: 0.3rem;">
                                        Fee + GST
                                    </small>
                                </div>
                            </div>

                            <!-- GST Breakdown Preview -->
                            <div id="gstBreakdown"
                                style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 8px; padding: 1rem; margin-top: 1rem; display: none;">
                                <div style="font-size: 0.85rem; color: var(--p-text-dim); margin-bottom: 0.5rem;">
                                    <strong style="color: #10b981;">Payment Breakdown:</strong>
                                </div>
                                <div
                                    style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.3rem;">
                                    <span>Base Fee:</span>
                                    <span id="breakdownBaseFee" style="color: white; font-weight: 600;">₹0.00</span>
                                </div>
                                <div
                                    style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.3rem;">
                                    <span>GST (<span id="breakdownGstRate">18</span>%):</span>
                                    <span id="breakdownGstAmount" style="color: white; font-weight: 600;">₹0.00</span>
                                </div>
                                <div
                                    style="border-top: 1px solid rgba(16, 185, 129, 0.3); margin: 0.5rem 0; padding-top: 0.5rem; display: flex; justify-content: space-between; font-size: 0.9rem;">
                                    <strong style="color: white;">Total Payable:</strong>
                                    <strong id="breakdownTotal"
                                        style="color: #10b981; font-size: 1.1rem;">₹0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Gateway Selection -->
                    <div style="margin-top: 1rem;">
                        <label>Payment Gateway</label>
                        <select name="payment_gateway" id="programPaymentGateway">
                            <option value="razorpay" selected>Razorpay (Recommended for India)</option>
                            <option value="stripe">Stripe (International)</option>
                            <option value="paytm">Paytm</option>
                            <option value="manual">Manual/Offline Payment</option>
                        </select>
                        <small
                            style="color: var(--p-text-dim); font-size: 0.75rem; display: block; margin-top: 0.3rem;">
                            Configure gateway settings in Payment Settings
                        </small>
                    </div>

                    <!-- UPI ID Configuration (Hidden as we use Razorpay) -->
                    <div style="margin-top: 1rem; display: none;">
                        <label>UPI ID (for QR Code) <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="payment_upi" id="programPaymentUpi" placeholder="e.g. username@okaxis"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 0.8rem; border-radius: 8px; width: 100%;">
                        <small
                            style="color: var(--p-text-dim); font-size: 0.75rem; display: block; margin-top: 0.3rem;">
                            This UPI ID will be used to generate the payment QR code.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Program is always created as active; enable/disable via the Enable Public Reg button -->
            <input type="hidden" id="programIsActive" name="is_active" value="1">

            <div style="display: flex; gap: 2rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;"
                    onclick="closeExternalProgramModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"
                    style="flex: 2; background: linear-gradient(135deg, #10b981 0%, #059669 100%);" id="saveProgramBtn">
                    <i class="fa-solid fa-check-circle"></i> Create Program
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Professional Logout with Confirmation
    async function confirmLogout() {
        const confirmResult = await Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to sign out?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
            confirmButtonText: '<i class="fa-solid fa-power-off"></i> Logout',
            cancelButtonText: 'No, Stay',
            background: '#0a0a0a',
            color: '#fff',
            customClass: {
                popup: 'glass-panel'
            }
        });

        if (confirmResult.isConfirmed) {
            Swal.fire({
                title: 'Logging out...',
                text: 'Signing you out of the session securely.',
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

    const modal = document.getElementById('eventModal');
    const eventForm = document.getElementById('eventForm');
    const isPaid = document.getElementById('isPaid');
    const paidSettings = document.getElementById('paidSettings');
    const isGroupEvent = document.getElementById('isGroupEvent');
    const groupSettings = document.getElementById('groupSettings');

    function openModal(isEdit = false) {
        modal.style.display = 'flex';
        document.getElementById('modalTitle').innerText = isEdit ? 'Edit Event Settings' : 'Add New Event';
        document.getElementById('saveBtn').innerText = isEdit ? 'Save Changes' : 'Publish Event';
        document.getElementById('statusSection').style.display = isEdit ? 'block' : 'none';
        if (!isEdit) {
            eventForm.reset();
            document.getElementById('eventId').value = '';
            paidSettings.style.display = 'none';
            groupSettings.style.display = 'none';
        }
        // Set minimum date to today
        const todayStr = new Date().toISOString().split('T')[0];
        document.getElementById('eventDateOnly').min = todayStr;
    }

    function setEventToNow() {
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].substring(0, 5);
        document.getElementById('eventDateOnly').value = dateStr;
        document.getElementById('eventTimeOnly').value = timeStr;
    }

    // Real-time event date validation
    document.getElementById('eventDateOnly').addEventListener('change', function () {
        const today = new Date().toISOString().split('T')[0];
        const errEl = document.getElementById('eventDateError');
        if (this.value && this.value < today) {
            errEl.style.display = 'block';
            this.style.borderColor = '#ef4444';
        } else {
            errEl.style.display = 'none';
            this.style.borderColor = '';
        }
    });


    function closeModal() {
        modal.style.display = 'none';
    }

    async function editEvent(id) {
        try {
            const res = await fetch(`/Project/EntryX/api/events.php?action=get&id=${id}`);
            const result = await res.json();

            if (result.success) {
                const event = result.data;
                openModal(true);

                document.getElementById('eventId').value = event.id;
                document.getElementById('eventName').value = event.name;
                document.getElementById('eventDescription').value = event.description;

                const dt = event.event_date.split(' ');
                document.getElementById('eventDateOnly').value = dt[0];
                document.getElementById('eventTimeOnly').value = dt[1].substring(0, 5);

                document.getElementById('eventVenue').value = event.venue;
                document.getElementById('eventCapacity').value = event.capacity;
                document.getElementById('eventType').value = event.type;
                document.getElementById('eventStatus').value = event.status;

                isPaid.checked = parseInt(event.is_paid) === 1;
                paidSettings.style.display = isPaid.checked ? 'block' : 'none';
                document.getElementById('eventPrice').value = event.base_price;
                document.getElementById('eventGst').value = event.gst_rate;
                document.getElementById('eventGstTarget').value = event.gst_target || 'both';
                document.getElementById('eventPaymentUpi').value = event.payment_upi || '';

                isGroupEvent.checked = parseInt(event.is_group_event) === 1;
                groupSettings.style.display = isGroupEvent.checked ? 'grid' : 'none';
                document.getElementById('minTeamSize').value = event.min_team_size || 1;
                document.getElementById('maxTeamSize').value = event.max_team_size || 1;

                if (isPaid.checked) {
                    calculateEventGST();
                }
            } else {
                Swal.fire('Error', 'Could not find event details', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Connection Error', 'error');
        }
    }

    async function deleteUser(id, name) {
        const confirm = await Swal.fire({
            title: 'Remove Sub-Admin?',
            text: `Are you sure you want to remove ${name}? They will lose all access to the system.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Remove Admin',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirm.isConfirmed) {
            try {
                // We'll create a simple API endpoint or use auth.php for this
                const res = await fetch(`/Project/EntryX/api/auth.php?action=delete_user&id=${id}`, {
                    method: 'POST'
                });
                const result = await res.json();

                if (result.success) {
                    Swal.fire('Removed!', 'Admin access has been removed.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', result.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Communication failure', 'error');
            }
        }
    }

    async function deleteEvent(id) {
        const confirm = await Swal.fire({
            title: 'Delete Event?',
            text: 'This will permanently remove the event and all associated student registrations!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Delete Everything',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch(`/Project/EntryX/api/events.php?action=delete&id=${id}`, {
                    method: 'POST'
                });
                const result = await res.json();

                if (result.success) {
                    Swal.fire('Deleted!', 'Event has been removed.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', result.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Operation failed', 'error');
            }
        }
    }

    isPaid.addEventListener('change', (e) => {
        paidSettings.style.display = e.target.checked ? 'block' : 'none';
        if (e.target.checked) calculateEventGST();
    });

    isGroupEvent.addEventListener('change', (e) => {
        groupSettings.style.display = e.target.checked ? 'grid' : 'none';
    });

    // GST Calculation for Events
    function calculateEventGST() {
        const baseFee = parseFloat(document.getElementById('eventPrice').value) || 0;
        const gstRate = parseFloat(document.getElementById('eventGst').value) || 18;
        const isPaidChecked = document.getElementById('isPaid').checked;

        if (baseFee > 0 && isPaidChecked) {
            const gstAmount = (baseFee * gstRate) / 100;
            const totalAmount = baseFee + gstAmount;

            document.getElementById('eventTotalAmount').value = `₹${totalAmount.toFixed(2)}`;
            document.getElementById('eventBreakdownBase').textContent = `₹${baseFee.toFixed(2)}`;
            document.getElementById('eventBreakdownRate').textContent = gstRate.toFixed(2);
            document.getElementById('eventBreakdownGst').textContent = `₹${gstAmount.toFixed(2)}`;
            document.getElementById('eventBreakdownTotal').textContent = `₹${totalAmount.toFixed(2)}`;
            document.getElementById('eventGstBreakdown').style.display = 'block';
        } else {
            document.getElementById('eventTotalAmount').value = baseFee > 0 ? `₹${baseFee.toFixed(2)}` : '';
            document.getElementById('eventGstBreakdown').style.display = 'none';
        }
    }

    document.getElementById('eventPrice').addEventListener('input', calculateEventGST);
    document.getElementById('eventGst').addEventListener('input', calculateEventGST);

    eventForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        // ===== FRONTEND VALIDATION =====
        const today = new Date().toISOString().split('T')[0];
        const dateOnly = document.getElementById('eventDateOnly').value;
        const timeOnly = document.getElementById('eventTimeOnly').value;

        if (!dateOnly) {
            Swal.fire('Missing Date', 'Please select a date for the event.', 'warning');
            return;
        }
        if (dateOnly < today) {
            document.getElementById('eventDateError').style.display = 'block';
            Swal.fire('Invalid Date', 'Event date cannot be in the past. Please select today or a future date.', 'warning');
            return;
        }

        const capacity = parseInt(document.getElementById('eventCapacity').value);
        if (isNaN(capacity) || capacity < 1) {
            Swal.fire('Invalid Capacity', 'Maximum attendees must be at least 1.', 'warning');
            return;
        }

        if (isPaid.checked) {
            const price = parseFloat(document.getElementById('eventPrice').value);
            if (isNaN(price) || price <= 0) {
                document.getElementById('eventPriceError').style.display = 'block';
                Swal.fire('Invalid Fee', 'Registration fee must be greater than ₹0 for a paid event.', 'warning');
                return;
            }
            document.getElementById('eventPriceError').style.display = 'none';
            const gstRate = parseFloat(document.getElementById('eventGst').value);
            if (isNaN(gstRate) || gstRate < 0 || gstRate > 100) {
                Swal.fire('Invalid GST', 'GST rate must be between 0% and 100%.', 'warning');
                return;
            }
        }
        // ===== END VALIDATION =====

        data.event_date = `${dateOnly} ${timeOnly}:00`;

        data.is_paid = isPaid.checked ? 1 : 0;
        data.is_gst_enabled = isPaid.checked ? 1 : 0;
        data.gst_target = data.gst_target || 'both';
        data.payment_upi = data.payment_upi || '';
        data.is_group_event = isGroupEvent.checked ? 1 : 0;
        data.min_team_size = data.min_team_size || 1;
        data.max_team_size = data.max_team_size || 1;

        const id = document.getElementById('eventId').value;
        const action = id ? 'update' : 'create';
        const url = `/Project/EntryX/api/events.php?action=${action}${id ? '&id=' + id : ''}`;

        try {
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await res.json();
            if (result.success) {
                closeModal();
                Swal.fire({
                    title: 'Success!',
                    text: 'Event created Successfully',
                    icon: 'success',
                    background: '#0a0a0a',
                    color: '#fff',
                    confirmButtonText: 'Great!',
                    confirmButtonColor: '#10b981'
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', result.error, 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Publish Event';
            }
        } catch (err) {
            console.error('Save Error:', err);
            Swal.fire('Error', 'Request processing failed', 'error');
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Publish Event';
        }
    });

    document.getElementById('createAdminForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        try {
            const res = await fetch('/Project/EntryX/api/auth.php?action=register', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'New Access Link Created',
                    text: 'Admin credentials have been registered.',
                    background: '#0a0a0a',
                    color: '#fff'
                }).then(() => {
                    document.getElementById('adminModal').style.display = 'none';
                });
            } else {
                Swal.fire('Error', result.error, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Failed to initialize sub-admin', 'error');
        }
    });

    // Global variables for external programs
    let currentExternalProgramId = null;
    let isExternalRegEnabled = false;

    // ========== EXTERNAL PROGRAMS MANAGEMENT ==========

    // Load external programs on page load
    document.addEventListener('DOMContentLoaded', function () {
        loadExternalRegStatus().then(() => {
            loadExternalPrograms();
            loadPendingPayments(); // Also load pending payments on start
        });
    });

    async function loadExternalPrograms() {
        try {
            const res = await fetch('/Project/EntryX/api/external_programs.php?action=get_all');
            const result = await res.json();

            if (result.success) {
                displayPrograms(result.data);
            } else {
                document.getElementById('programsTableContainer').innerHTML =
                    '<p style="text-align: center; color: var(--p-text-muted); padding: 2rem;">Failed to load programs</p>';
            }
        } catch (err) {
            document.getElementById('programsTableContainer').innerHTML =
                '<p style="text-align: center; color: #ef4444; padding: 2rem;"><i class="fa-solid fa-exclamation-triangle"></i> Error loading programs</p>';
        }
    }

    async function loadExternalRegStatus() {
        try {
            const res = await fetch('/Project/EntryX/api/external_programs.php?action=get_settings');
            const result = await res.json();

            if (result.success) {
                isExternalRegEnabled = result.data.external_registration_enabled === '1';
                currentExternalProgramId = result.data.current_external_program_id ? parseInt(result.data.current_external_program_id) : null;
                const programName = result.data.current_external_program_name || 'None';

                const statusDiv = document.getElementById('externalRegStatus');
                if (isExternalRegEnabled) {
                    statusDiv.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 1.5rem;"></i>
                            <div>
                                <div style="font-weight: 700; color: white; margin-bottom: 0.3rem;">External Registration: ENABLED</div>
                                <div style="color: var(--p-text-dim); font-size: 0.85rem;">Active Program: ${programName}</div>
                            </div>
                        </div>
                        <button class="btn btn-outline" style="padding: 0.7rem 1.5rem; border-color: #ef4444; color: #ef4444;" 
                                onclick="disableExternalReg()">
                            <i class="fa-solid fa-power-off"></i> Disable
                        </button>
                    `;
                    statusDiv.style.background = 'rgba(16, 185, 129, 0.1)';
                    statusDiv.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                } else {
                    statusDiv.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fa-solid fa-circle-xmark" style="color: #ef4444; font-size: 1.5rem;"></i>
                            <div>
                                <div style="font-weight: 700; color: white; margin-bottom: 0.3rem;">External Registration: DISABLED</div>
                                <div style="color: var(--p-text-dim); font-size: 0.85rem;">Public registration is currently not visible on the landing page</div>
                            </div>
                        </div>
                    `;
                    statusDiv.style.background = 'rgba(239, 68, 68, 0.1)';
                    statusDiv.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                }
            }
        } catch (err) {
            console.error('Failed to load status:', err);
        }
    }

    function displayPrograms(programs) {
        const statusDiv = document.getElementById('externalRegStatus');
        if (programs.length === 0) {
            if (statusDiv) statusDiv.style.display = 'none';
            document.getElementById('programsTableContainer').innerHTML = `
                <div style="text-align: center; padding: 4rem; color: var(--p-text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No external programs created yet</p>
                    <button class="btn btn-primary" style="margin-top: 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);" 
                            onclick="openExternalProgramModal()">
                        <i class="fa-solid fa-plus-circle"></i> Create Your First Program
                    </button>
                </div>
            `;
            return;
        }

        if (statusDiv) statusDiv.style.display = 'flex';

        let html = '<table style="width: 100%; border-collapse: separate; border-spacing: 0 1rem;">';
        html += `
            <thead>
                <tr style="text-align: left; color: var(--p-text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.15em;">
                    <th style="padding: 0 1.5rem;">Program Name</th>
                    <th>Duration</th>
                    <th>External Entries</th>
                    <th>Status</th>
                    <th style="text-align: right; padding: 0 1.5rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
        `;

        programs.forEach(program => {
            const startDate = program.start_date ? new Date(program.start_date).toLocaleDateString() : 'N/A';
            const endDate = program.end_date ? new Date(program.end_date).toLocaleDateString() : 'N/A';
            const isActive = parseInt(program.is_active) === 1;

            html += `
                <tr style="background: rgba(255,255,255,0.02); transition: all 0.3s;">
                    <td style="padding: 1.5rem; border-radius: 16px 0 0 16px; font-weight: 700; color: white; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        ${program.program_name}
                        ${program.program_description ? `<div style="font-size: 0.8rem; color: var(--p-text-dim); font-weight: 400; margin-top: 0.3rem;">${program.program_description.substring(0, 60)}...</div>` : ''}
                    </td>
                    <td style="color: var(--p-text-dim); border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <div style="font-size: 0.85rem;">${startDate} - ${endDate}</div>
                    </td>
                    <td style="color: var(--p-text-dim); border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <span style="font-weight: 700; color: white;">${program.participant_count || 0}</span> / ${program.max_participants}
                    </td>
                    <td style="border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <span class="status-badge ${isActive ? 'status-inside' : 'status-outside'}">
                            ${isActive ? 'ACTIVE' : 'INACTIVE'}
                        </span>
                    </td>
                    <td style="text-align: right; padding: 1.5rem; border-radius: 0 16px 16px 0; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <div style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                            ${(isExternalRegEnabled && currentExternalProgramId === parseInt(program.id)) ? `
                                <span style="color: #10b981; font-size: 0.85rem; font-weight: 700; padding: 0.6rem 1.2rem; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fa-solid fa-satellite-dish fa-spin"></i> LIVE ON SITE
                                </span>
                            ` : `
                                <button class="btn btn-primary" style="padding: 0.6rem 1.2rem; font-size: 0.85rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);"
                                    onclick="enableExternalReg(${program.id})">
                                    <i class="fa-solid fa-rocket"></i> Enable Public Reg
                                </button>
                            `}
                            <button class="user-avatar-nav" style="width: 36px; height: 36px; border-radius: 10px;"
                                onclick="editExternalProgram(${program.id})">
                                <i class="fa-solid fa-pen-nib"></i>
                            </button>
                            <button class="user-avatar-nav" style="width: 36px; height: 36px; border-radius: 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.1);"
                                onclick="deleteExternalProgram(${program.id}, '${program.program_name}')">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        document.getElementById('programsTableContainer').innerHTML = html;
    }

    function openExternalProgramModal(isEdit = false) {
        document.getElementById('externalProgramModal').style.display = 'flex';
        document.getElementById('externalProgramModalTitle').innerText = isEdit ? 'Edit External Program' : 'Create External Program';
        document.getElementById('saveProgramBtn').innerHTML = isEdit ? '<i class="fa-solid fa-save"></i> Save Changes' : '<i class="fa-solid fa-check-circle"></i> Create Program';

        // Set minimum date to today for start date
        const todayStr = new Date().toISOString().split('T')[0];
        document.getElementById('programStartDate').min = todayStr;
        document.getElementById('programEndDate').min = todayStr;

        if (!isEdit) {
            document.getElementById('externalProgramForm').reset();
            document.getElementById('externalProgramId').value = '';

            // Reset Payment UI
            document.getElementById('gstSettings').style.display = 'none';
            document.getElementById('gstBreakdown').style.display = 'none';
            document.getElementById('programTotalAmount').value = '';
            document.getElementById('programPaymentUpi').value = '';
        }
    }

    // Update end date min when start date changes
    document.getElementById('programStartDate').addEventListener('change', function () {
        const today = new Date().toISOString().split('T')[0];
        const startErrEl = document.getElementById('programStartDateError');
        if (this.value && this.value < today) {
            startErrEl.style.display = 'block';
            this.style.borderColor = '#ef4444';
        } else {
            startErrEl.style.display = 'none';
            this.style.borderColor = '';
        }
        // Update end date minimum
        if (this.value) {
            document.getElementById('programEndDate').min = this.value;
            const endDate = document.getElementById('programEndDate').value;
            if (endDate && endDate < this.value) {
                document.getElementById('programEndDate').value = '';
                document.getElementById('programEndDateError').style.display = 'block';
            }
        }
    });

    document.getElementById('programEndDate').addEventListener('change', function () {
        const startDate = document.getElementById('programStartDate').value;
        const endErrEl = document.getElementById('programEndDateError');
        if (startDate && this.value && this.value < startDate) {
            endErrEl.style.display = 'block';
            this.style.borderColor = '#ef4444';
        } else {
            endErrEl.style.display = 'none';
            this.style.borderColor = '';
        }
    });

    // Real-time fee validation for external program
    document.getElementById('programRegistrationFee').addEventListener('input', function () {
        const feeErrEl = document.getElementById('programFeeError');
        const feeHintEl = document.getElementById('programFeeHint');
        if (this.value !== '' && parseFloat(this.value) <= 0) {
            feeErrEl.style.display = 'block';
            feeHintEl.style.display = 'none';
            this.style.borderColor = '#ef4444';
        } else {
            feeErrEl.style.display = 'none';
            feeHintEl.style.display = 'block';
            this.style.borderColor = '';
        }
        calculateGST();
    });

    function closeExternalProgramModal() {
        document.getElementById('externalProgramModal').style.display = 'none';
    }

    async function editExternalProgram(id) {
        try {
            const res = await fetch(`/Project/EntryX/api/external_programs.php?action=get&id=${id}`);
            const result = await res.json();

            if (result.success) {
                const program = result.data;
                openExternalProgramModal(true);

                document.getElementById('externalProgramId').value = program.id;
                document.getElementById('programName').value = program.program_name;
                document.getElementById('programDescription').value = program.program_description || '';
                document.getElementById('programStartDate').value = program.start_date || '';
                document.getElementById('programEndDate').value = program.end_date || '';
                document.getElementById('programMaxParticipants').value = program.max_participants;
                document.getElementById('programIsActive').value = parseInt(program.is_active) === 1 ? 1 : 0;

                // Payment Fields
                const isPaid = parseInt(program.is_paid) === 1;
                document.getElementById('programIsPaid').checked = isPaid;
                document.getElementById('paymentSettings').style.display = isPaid ? 'block' : 'none';
                document.getElementById('programRegistrationFee').value = program.registration_fee || 0;
                document.getElementById('programCurrency').value = program.currency || 'INR';

                const isGst = parseInt(program.is_gst_enabled) === 1;
                document.getElementById('programGstEnabled').checked = isGst;
                document.getElementById('gstSettings').style.display = isGst ? 'block' : 'none';
                document.getElementById('programGstRate').value = program.gst_rate || 18;

                if (isPaid && isGst) {
                    calculateGST();
                }

                // Populate UPI ID (New Field)
                document.getElementById('programPaymentUpi').value = program.payment_upi || '';
            } else {
                Swal.fire('Error', 'Could not load program details', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Connection error', 'error');
        }
    }

    async function deleteExternalProgram(id, name, force = false) {
        let confirm;
        if (force) {
            confirm = await Swal.fire({
                title: 'Confirm Forced Deletion',
                text: `WARNING: This will permanently delete program "${name}" AND ALL its registered participants. This data cannot be recovered.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'I Understand, Delete Everything',
                background: '#0a0a0a',
                color: '#fff'
            });
        } else {
            confirm = await Swal.fire({
                title: 'Delete External Program?',
                text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Delete Program',
                background: '#0a0a0a',
                color: '#fff'
            });
        }

        if (confirm.isConfirmed) {
            // Second Confirmation Layer
            const finalConfirm = await Swal.fire({
                title: 'Final Confirmation',
                text: 'This is a critical action. Please confirm once more that you want to delete this program.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Delete It',
                cancelButtonText: 'No, Wait',
                background: '#0a0a0a',
                color: '#fff'
            });

            if (finalConfirm.isConfirmed) {
                try {
                    const url = force
                        ? `/Project/EntryX/api/external_programs.php?action=delete&id=${id}&force=true`
                        : `/Project/EntryX/api/external_programs.php?action=delete&id=${id}`;

                    const res = await fetch(url, { method: 'POST' });
                    const result = await res.json();

                    if (result.success) {
                        Swal.fire('Deleted!', 'Program has been removed.', 'success');
                        loadExternalPrograms();
                    } else {
                        // Check if error is due to participants
                        if (result.error && result.error.includes("registered participants")) {
                            const forceConfirm = await Swal.fire({
                                title: 'Cannot Delete: Participants Exist',
                                text: `${result.error}. Do you want to FORCE delete this program and remove all these participants from the system?`,
                                icon: 'error',
                                showCancelButton: true,
                                confirmButtonColor: '#ef4444',
                                confirmButtonText: 'Yes, FORCE DELETE',
                                cancelButtonText: 'No, Cancel',
                                background: '#0a0a0a',
                                color: '#fff'
                            });

                            if (forceConfirm.isConfirmed) {
                                deleteExternalProgram(id, name, true);
                            }
                        } else {
                            Swal.fire('Error', result.error, 'error');
                        }
                    }
                } catch (err) {
                    Swal.fire('Error', 'Failed to delete program', 'error');
                }
            }
        }
    }

    async function enableExternalReg(programId) {
        const confirm = await Swal.fire({
            title: 'Enable Public Registration?',
            text: 'This will make the registration form visible on the landing page for external participants.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Yes, Enable Registration',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch(`/Project/EntryX/api/external_programs.php?action=enable_external_registration&program_id=${programId}`, {
                    method: 'POST'
                });
                const result = await res.json();

                if (result.success) {
                    Swal.fire('Enabled!', 'External registration is now live on the landing page.', 'success');
                    await loadExternalRegStatus();
                    loadExternalPrograms();
                } else {
                    Swal.fire('Error', result.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Failed to enable registration', 'error');
            }
        }
    }

    async function disableExternalReg() {
        const confirm = await Swal.fire({
            title: 'Disable Public Registration?',
            text: 'This will hide the registration button from the landing page.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Disable',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch('/Project/EntryX/api/external_programs.php?action=disable_external_registration', {
                    method: 'POST'
                });
                const result = await res.json();

                if (result.success) {
                    Swal.fire('Disabled!', 'External registration has been disabled.', 'success');
                    await loadExternalRegStatus();
                    loadExternalPrograms();
                } else {
                    Swal.fire('Error', result.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Failed to disable registration', 'error');
            }
        }
    }

    // ========== PAYMENT & GST CALCULATION ==========

    // Toggle payment settings visibility
    document.getElementById('programIsPaid').addEventListener('change', function (e) {
        const paymentSettings = document.getElementById('paymentSettings');
        paymentSettings.style.display = e.target.checked ? 'block' : 'none';
        if (!e.target.checked) {
            document.getElementById('programGstEnabled').checked = false;
            document.getElementById('gstSettings').style.display = 'none';
            document.getElementById('gstBreakdown').style.display = 'none';
        }
    });

    // Toggle GST settings visibility
    document.getElementById('programGstEnabled').addEventListener('change', function (e) {
        const gstSettings = document.getElementById('gstSettings');
        gstSettings.style.display = e.target.checked ? 'block' : 'none';
        if (e.target.checked) {
            calculateGST();
        } else {
            document.getElementById('gstBreakdown').style.display = 'none';
            document.getElementById('programTotalAmount').value = '';
        }
    });

    // Calculate GST and update breakdown
    function calculateGST() {
        const baseFee = parseFloat(document.getElementById('programRegistrationFee').value) || 0;
        const gstRate = parseFloat(document.getElementById('programGstRate').value) || 18;
        const gstEnabled = document.getElementById('programGstEnabled').checked;

        if (baseFee > 0 && gstEnabled) {
            const gstAmount = (baseFee * gstRate) / 100;
            const totalAmount = baseFee + gstAmount;

            // Update total amount field
            document.getElementById('programTotalAmount').value = `₹${totalAmount.toFixed(2)}`;

            // Update breakdown display
            document.getElementById('breakdownBaseFee').textContent = `₹${baseFee.toFixed(2)}`;
            document.getElementById('breakdownGstRate').textContent = gstRate.toFixed(2);
            document.getElementById('breakdownGstAmount').textContent = `₹${gstAmount.toFixed(2)}`;
            document.getElementById('breakdownTotal').textContent = `₹${totalAmount.toFixed(2)}`;
            document.getElementById('gstBreakdown').style.display = 'block';
        } else {
            document.getElementById('programTotalAmount').value = baseFee > 0 ? `₹${baseFee.toFixed(2)}` : '';
            document.getElementById('gstBreakdown').style.display = 'none';
        }
    }

    // Attach calculation to input changes
    document.getElementById('programRegistrationFee').addEventListener('input', calculateGST);
    document.getElementById('programGstRate').addEventListener('input', calculateGST);

    // Update form submission to include payment data
    const originalFormSubmit = document.getElementById('externalProgramForm').onsubmit;
    document.getElementById('externalProgramForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        // ===== FRONTEND VALIDATION =====
        const today = new Date().toISOString().split('T')[0];
        const startDate = formData.get('start_date');
        const endDate = formData.get('end_date');
        const isPaidProgram = document.getElementById('programIsPaid').checked;
        const regFee = parseFloat(formData.get('registration_fee'));
        const maxPart = parseInt(formData.get('max_participants'));
        const progName = formData.get('program_name');

        if (!progName || progName.trim().length < 2) {
            Swal.fire('Missing Name', 'Program name must be at least 2 characters.', 'warning');
            return;
        }
        if (!startDate) {
            Swal.fire('Missing Start Date', 'Please select a start date for the program.', 'warning');
            return;
        }
        if (startDate < today) {
            document.getElementById('programStartDateError').style.display = 'block';
            Swal.fire('Invalid Start Date', 'Program start date cannot be in the past.', 'warning');
            return;
        }
        if (!endDate) {
            Swal.fire('Missing End Date', 'Please select an end date for the program.', 'warning');
            return;
        }
        if (endDate < startDate) {
            document.getElementById('programEndDateError').style.display = 'block';
            Swal.fire('Invalid End Date', 'End date must be on or after the start date.', 'warning');
            return;
        }
        if (isNaN(maxPart) || maxPart < 1) {
            Swal.fire('Invalid Capacity', 'Maximum participants must be at least 1.', 'warning');
            return;
        }
        if (isPaidProgram) {
            if (isNaN(regFee) || regFee <= 0) {
                document.getElementById('programFeeError').style.display = 'block';
                Swal.fire('Invalid Fee', 'Registration fee must be greater than ₹0 for a paid program.', 'warning');
                return;
            }
            const gateway = formData.get('payment_gateway');
            if (gateway === 'manual') {
                const upiId = formData.get('payment_upi') || '';
                if (!upiId.trim()) {
                    Swal.fire('UPI ID Required', 'Please enter a UPI ID to generate the payment QR code for paid programs.', 'warning');
                    document.getElementById('programPaymentUpi').focus();
                    return;
                }
                if (!upiId.includes('@')) {
                    Swal.fire('Invalid UPI ID', 'Please enter a valid UPI ID (e.g. name@okaxis).', 'warning');
                    document.getElementById('programPaymentUpi').focus();
                    return;
                }
            }

        }
        // ===== END VALIDATION =====

        const data = {
            program_name: progName,
            program_description: formData.get('program_description'),
            start_date: startDate,
            end_date: endDate,
            max_participants: maxPart,
            is_active: 1, // Always active; controlled via Enable/Disable Public Registration
            // Payment fields
            is_paid: isPaidProgram ? 1 : 0,
            registration_fee: isPaidProgram ? regFee : 0,
            currency: formData.get('currency') || 'INR',
            is_gst_enabled: document.getElementById('programGstEnabled').checked ? 1 : 0,
            gst_rate: formData.get('gst_rate') || 18,
            payment_gateway: formData.get('payment_gateway') || 'razorpay',
            payment_upi: formData.get('payment_upi') || ''
        };

        const id = document.getElementById('externalProgramId').value;
        const action = id ? 'update' : 'create';
        const url = `/Project/EntryX/api/external_programs.php?action=${action}${id ? '&id=' + id : ''}`;

        try {
            const saveBtn = document.getElementById('saveProgramBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await res.json();
            if (result.success) {
                Swal.fire('Success', `Program ${id ? 'updated' : 'created'} successfully!`, 'success');
                closeExternalProgramModal();
                loadExternalPrograms();
            } else {
                Swal.fire('Error', result.error, 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = id ? '<i class="fa-solid fa-save"></i> Save Changes' : '<i class="fa-solid fa-check-circle"></i> Create Program';
            }
        } catch (err) {
            Swal.fire('Error', 'Request processing failed', 'error');
        }
    });

    // ========== REGISTRATION LIST SYSTEM ==========
    let currentViewEventId = null;

    async function viewRegistrations(eventId, eventName) {
        currentViewEventId = eventId;
        document.getElementById('regViewTitle').innerText = eventName;
        document.getElementById('regViewModal').style.display = 'flex';
        const container = document.getElementById('regListContainer');
        container.innerHTML = '<p style="text-align: center; color: var(--p-text-muted); padding: 3rem;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading attendees...</p>';

        try {
            const res = await fetch(`../api/results.php?action=get_candidates&event_id=${eventId}&all=1`);
            const data = await res.json();

            if (data.success && data.candidates) {
                // Using candidates but here we want more info like date
                // Actually the API currently returns only name and email
                // I should update the API to return all fields if all=1 is requested

                if (data.candidates.length === 0) {
                    container.innerHTML = '<p style="text-align: center; color: var(--p-text-muted); padding: 3rem;">No registrations found for this event yet.</p>';
                } else {
                    let html = '<table style="width: 100%; border-collapse: separate; border-spacing: 0 0.8rem;">';
                    html += `
                        <thead>
                            <tr style="text-align: left; color: var(--p-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;">
                                <th style="padding: 0.5rem 1rem;">Participant Details</th>
                                <th>Status</th>
                                <th>Registration Info</th>
                            </tr>
                        </thead>
                        <tbody>
                    `;
                    data.candidates.forEach(reg => {
                        const isGroup = reg.team_name ? true : false;
                        html += `
                            <tr style="background: rgba(255,255,255,0.02);">
                                <td style="padding: 1.2rem; border-radius: 12px 0 0 12px;">
                                    <div style="font-weight: 700; color: white;">${reg.name}</div>
                                    <div style="font-size: 0.8rem; color: var(--p-text-dim);">${reg.email}</div>
                                    ${reg.team_name ? `
                                        <div style="margin-top: 0.8rem; padding: 0.8rem; background: rgba(16, 185, 129, 0.05); border-radius: 10px; border: 1px solid rgba(16, 185, 129, 0.1);">
                                            <div style="color: #10b981; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.3rem;">Team Structure</div>
                                            <div style="color: white; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.4rem;">${reg.team_name}</div>
                                            <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                                ${JSON.parse(reg.team_members || '[]').map((m, i) => `
                                                    <div style="color: var(--p-text-dim); font-size: 0.75rem; display: flex; align-items: flex-start; gap: 0.4rem;">
                                                        <span style="color: #10b981; opacity: 0.7;">•</span>
                                                        <span>${m.includes('|') ? m.split('|').map(s => s.trim()).join(' — ') : m}</span>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                    ` : ''}
                                </td>
                                <td>
                                    <span class="status-badge ${reg.payment_status === 'completed' || reg.payment_status === 'free' ? 'status-inside' : 'status-pending'}">
                                        ${reg.payment_status ? reg.payment_status.toUpperCase() : 'CONFIRMED'}
                                    </span>
                                </td>
                                <td style="color: var(--p-text-dim); border-radius: 0 12px 12px 0;">
                                    <div style="font-size: 0.8rem;">${reg.registration_date || ''}</div>
                                </td>
                            </tr>
                        `;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                }
            } else {
                container.innerHTML = `<p style="text-align: center; color: #ef4444; padding: 3rem;">Error: ${data.error || 'Failed to load'}</p>`;
            }
        } catch (e) {
            container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 3rem;">Connection failed</p>';
        }
    }

    function exportRegistrations() {
        if (!currentViewEventId) {
            Swal.fire('Error', 'No event selected for export', 'error');
            return;
        }

        Swal.fire({
            title: 'Exporting...',
            text: 'Preparing registration data for download',
            didOpen: () => Swal.showLoading(),
            timer: 1000,
            showConfirmButton: false
        }).then(() => {
            window.location.href = `../api/export_registrations.php?event_id=${currentViewEventId}`;
        });
    }

    // ========== PAYMENT VERIFICATION SYSTEM ==========

    async function loadPendingPayments() {
        const container = document.getElementById('pendingPaymentsTableContainer');
        try {
            const res = await fetch('/Project/EntryX/api/verify_payment.php?action=list_pending');
            const result = await res.json();

            if (result.success) {
                displayPendingPayments(result.data);
            } else {
                container.innerHTML = `<p style="text-align: center; color: var(--p-text-muted); padding: 2rem;">Error: ${result.error}</p>`;
            }
        } catch (err) {
            container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;"><i class="fa-solid fa-exclamation-triangle"></i> Communication error</p>';
        }
    }

    function displayPendingPayments(payments) {
        const container = document.getElementById('pendingPaymentsTableContainer');
        if (payments.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: var(--p-text-muted);">
                    <i class="fa-solid fa-check-double" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></i>
                    <p>All payments verified! No pending registrations found.</p>
                </div>
            `;
            return;
        }

        let html = '<table style="width: 100%; border-collapse: separate; border-spacing: 0 1rem;">';
        html += `
            <thead>
                <tr style="text-align: left; color: var(--p-text-muted); font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 0 1.5rem;">User / Contact</th>
                    <th>Event Name</th>
                    <th>Transaction ID (UTR)</th>
                    <th>Amount Paid</th>
                    <th style="text-align: right; padding: 0 1.5rem;">Action</th>
                </tr>
            </thead>
            <tbody>
        `;

        payments.forEach(reg => {
            html += `
                <tr style="background: rgba(234, 179, 8, 0.05); border-left: 4px solid #eab308;">
                    <td style="padding: 1.5rem; border-radius: 0 0 0 0; border-top: 1px solid rgba(234, 179, 8, 0.1); border-bottom: 1px solid rgba(234, 179, 8, 0.1);">
                        <div style="font-weight: 700; color: white;">${reg.user_name}</div>
                        <div style="font-size: 0.8rem; color: var(--p-text-dim);">${reg.user_email}</div>
                    ${reg.team_name ? `
                            <div style="margin-top: 0.8rem; padding: 0.5rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2);">
                                <div style="color: #10b981; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.3rem;">Team: ${reg.team_name}</div>
                                <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                                    ${JSON.parse(reg.team_members || '[]').map(m => `
                                        <div style="color: white; font-size: 0.7rem;">• ${m.includes('|') ? m.split('|').map(s => s.trim()).join(' — ') : m}</div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </td>
                    <td style="color: white; border-top: 1px solid rgba(234, 179, 8, 0.1); border-bottom: 1px solid rgba(234, 179, 8, 0.1);">
                        ${reg.event_name}
                    </td>
                    <td style="border-top: 1px solid rgba(234, 179, 8, 0.1); border-bottom: 1px solid rgba(234, 179, 8, 0.1);">
                        <code style="background: rgba(0,0,0,0.3); padding: 0.4rem 0.8rem; border-radius: 6px; color: #eab308; font-family: monospace; font-size: 1.1rem; border: 1px solid rgba(234, 179, 8, 0.2);">
                            ${reg.transaction_id}
                        </code>
                    </td>
                    <td style="font-weight: 800; color: white; border-top: 1px solid rgba(234, 179, 8, 0.1); border-bottom: 1px solid rgba(234, 179, 8, 0.1);">
                        ₹${parseFloat(reg.total_amount).toFixed(2)}
                    </td>
                    <td style="text-align: right; padding: 1.5rem; border-top: 1px solid rgba(234, 179, 8, 0.1); border-bottom: 1px solid rgba(234, 179, 8, 0.1);">
                        <button class="btn btn-primary" style="background: #eab308; color: #000; font-weight: 800; font-size: 0.9rem;"
                            onclick="verifyPaymentReg(${reg.id}, '${reg.user_name}')">
                            <i class="fa-solid fa-check"></i> Verify & Confirm
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    async function verifyPaymentReg(regId, name) {
        const confirm = await Swal.fire({
            title: 'Verify Payment?',
            text: `Please confirm that you have received payment for ${name} in your bank account.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#eab308',
            confirmButtonText: 'Yes, Payment Received',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch('/Project/EntryX/api/verify_payment.php?action=verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ registration_id: regId })
                });
                const result = await res.json();

                if (result.success) {
                    Swal.fire({
                        title: 'Verified!',
                        text: 'Registration has been confirmed and ticket is now active.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        background: '#0a0a0a',
                        color: '#fff'
                    });
                    loadPendingPayments();
                } else {
                    Swal.fire('Error', result.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Communication failure', 'error');
            }
        }
    }


    // Professional Logout with Confirmation
    async function confirmLogout() {
        const res = await Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to end your session?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Logout',
            background: '#0a0a0a',
            color: '#fff'
        });
        if (res.isConfirmed) {
            window.location.href = '/Project/EntryX/api/auth.php?action=logout';
        }
    }

    // ========== LIVE DASHBOARD STATS SYSTEM ==========
    // Stores the last known values to detect changes and flash animation
    let _lastStats = {
        inside: null, internal: null, externalInside: null,
        events: null, reg: null, external: null
    };

    function animateStatChange(elementId, newValue) {
        const el = document.getElementById(elementId);
        if (!el) return;
        const current = parseInt(el.textContent);
        if (current !== newValue) {
            el.textContent = newValue;
            el.classList.remove('stat-update-flash');
            // Force reflow to restart animation
            void el.offsetWidth;
            el.classList.add('stat-update-flash');
            setTimeout(() => el.classList.remove('stat-update-flash'), 500);
        }
    }

    async function fetchLiveDashboardStats() {
        try {
            const res = await fetch('/Project/EntryX/api/stats.php?action=dashboard_live&_t=' + Date.now());
            if (!res.ok) return;
            const data = await res.json();

            if (!data.success) return;

            // Update People Inside (most critical - live campus tracking)
            animateStatChange('stat-inside', data.people_inside);
            animateStatChange('stat-internal', data.internal_inside);
            animateStatChange('stat-external-inside', data.external_inside);

            // Update other stats
            animateStatChange('stat-events', data.total_events);
            animateStatChange('stat-reg', data.total_reg);
            // Outside Guests = only externals currently inside campus (scanned in, not just registered)
            animateStatChange('stat-external', data.external_inside);

            // Update last sync time in the live badge
            const timeEl = document.getElementById('lastSyncTime');
            if (timeEl) timeEl.textContent = data.timestamp;

            _lastStats = {
                inside: data.people_inside,
                internal: data.internal_inside,
                externalInside: data.external_inside,
                events: data.total_events,
                reg: data.total_reg,
                external: data.external_count
            };

        } catch (err) {
            // Silently fail - dashboard keeps showing last known values
            console.warn('Live stats fetch failed:', err.message);
        }
    }

    // Start live polling immediately on load, then every 3 seconds
    fetchLiveDashboardStats();
    setInterval(fetchLiveDashboardStats, 3000);

</script>
<?php require_once '../includes/footer.php'; ?>