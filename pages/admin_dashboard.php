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
$events = $eventObj->getAllEvents(true);

// Fetch Sub-Admins for Management
$stmtAdmins = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE role IN ('event_admin',
'security') ORDER BY created_at DESC");
$stmtAdmins->execute();
$subAdmins = $stmtAdmins->fetchAll();

// Fetch Dynamic Stats
$totalEvents = count($events);
$stmtReg = $pdo->query("SELECT COUNT(*) FROM registrations");
$totalReg = $stmtReg->fetchColumn();
$stmtExt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'external'");
$externalCount = $stmtExt->fetchColumn();
$stmtInside = $pdo->query("SELECT COUNT(*) FROM attendance_logs WHERE exit_time IS NULL");
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

    <!-- Stats Matrix -->
    <div class="stats-matrix">
        <div class="stat-matrix-card reveal">
            <div class="stat-icon-alpha" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                <i class="fa-solid fa-calendar-nodes"></i>
            </div>
            <h3
                style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem;">
                Total Events</h3>
            <p style="font-size: 3rem; font-weight: 900; color: white; line-height: 1;"><?php echo $totalEvents; ?></p>
        </div>
        <div class="stat-matrix-card reveal" style="animation-delay: 0.1s;">
            <div class="stat-icon-alpha" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fa-solid fa-users-viewfinder"></i>
            </div>
            <h3
                style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem;">
                Outside Guests</h3>
            <p style="font-size: 3rem; font-weight: 900; color: white; line-height: 1;"><?php echo $externalCount; ?>
            </p>
        </div>
        <div class="stat-matrix-card reveal" style="animation-delay: 0.2s;">
            <div class="stat-icon-alpha" style="background: rgba(234, 179, 8, 0.1); color: #eab308;">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <h3
                style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem;">
                Registrations</h3>
            <p style="font-size: 3rem; font-weight: 900; color: white; line-height: 1;"><?php echo $totalReg; ?></p>
        </div>
        <div class="stat-matrix-card reveal" style="animation-delay: 0.3s;">
            <div class="stat-icon-alpha" style="background: rgba(239, 68, 68, 0.1); color: var(--p-brand);">
                <i class="fa-solid fa-wifi fa-fade"></i>
            </div>
            <h3
                style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem;">
                People Inside</h3>
            <p style="font-size: 3rem; font-weight: 900; color: white; line-height: 1;"><?php echo $insideCount; ?></p>
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
                            <div style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                                <button class="user-avatar-nav" style="width: 36px; height: 36px; border-radius: 10px;"
                                    onclick="editEvent(<?php echo $event['id']; ?>)">
                                    <i class="fa-solid fa-pen-nib"></i>
                                </button>
                                <button class="user-avatar-nav"
                                    style="width: 36px; height: 36px; border-radius: 10px; color: #ef4444; border-color: rgba(239, 68, 68, 0.1);"
                                    onclick="deleteEvent(<?php echo $event['id']; ?>)">
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
                    <div>
                        <label>Date and Time</label>
                        <input type="datetime-local" name="event_date" id="eventDate" required>
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
                        <input type="number" name="capacity" id="eventCapacity" value="100">
                    </div>
                    <div>
                        <label>Who can attend?</label>
                        <select name="type" id="eventType">
                            <option value="both">Everyone (Internal & External)</option>
                            <option value="internal">College Students Only</option>
                            <option value="external">Outside Participants Only</option>
                        </select>
                    </div>
                </div>
                <div id="statusSection" style="display: none; margin-bottom: 2rem;">
                    <label>Event Status</label>
                    <select name="status" id="eventStatus">
                        <option value="active">Active</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Finished</option>
                    </select>
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
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div>
                                <label>Registration Fee (₹)</label>
                                <input type="number" step="0.01" name="base_price" id="eventPrice">
                            </div>
                            <div>
                                <label>GST Rate (%)</label>
                                <input type="number" step="0.01" name="gst_rate" id="eventGst" value="18.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;"
                        onclick="closeModal()">Cancel</button>
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
                <input type="text" name="name" required>
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" required>
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
                    <label>Start Date</label>
                    <input type="date" name="start_date" id="programStartDate">
                </div>
                <div>
                    <label>End Date</label>
                    <input type="date" name="end_date" id="programEndDate">
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
                                placeholder="0.00" min="0">
                            <small
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
                </div>
            </div>

            <div
                style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem;">
                <h4
                    style="color: #10b981; margin-bottom: 1rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.1em;">
                    <i class="fa-solid fa-info-circle"></i> Program Status
                </h4>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <input type="checkbox" id="programIsActive" name="is_active" checked
                        style="width: 24px; height: 24px; cursor: pointer;">
                    <label for="programIsActive"
                        style="margin: 0; cursor: pointer; text-transform: none; color: white;">
                        Program is Active (can be enabled for public registration)
                    </label>
                </div>
            </div>

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

    function openModal(isEdit = false) {
        modal.style.display = 'flex';
        document.getElementById('modalTitle').innerText = isEdit ? 'Edit Event Settings' : 'Add New Event';
        document.getElementById('saveBtn').innerText = isEdit ? 'Save Changes' : 'Publish Event';
        document.getElementById('statusSection').style.display = isEdit ? 'block' : 'none';
        if (!isEdit) {
            eventForm.reset();
            document.getElementById('eventId').value = '';
            paidSettings.style.display = 'none';
        }
    }

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
                document.getElementById('eventDate').value = event.event_date.replace(' ', 'T');
                document.getElementById('eventVenue').value = event.venue;
                document.getElementById('eventCapacity').value = event.capacity;
                document.getElementById('eventType').value = event.type;
                document.getElementById('eventStatus').value = event.status;

                isPaid.checked = parseInt(event.is_paid) === 1;
                paidSettings.style.display = isPaid.checked ? 'block' : 'none';
                document.getElementById('eventPrice').value = event.base_price;
                document.getElementById('eventGst').value = event.gst_rate;
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
    });

    eventForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        data.is_paid = isPaid.checked ? 1 : 0;
        data.is_gst_enabled = isPaid.checked ? 1 : 0;

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
                Swal.fire('Success', 'Event settings updated successfully.', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', result.error, 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Publish Event';
            }
        } catch (err) {
            Swal.fire('Error', 'Request processing failed', 'error');
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
                            ${(!isExternalRegEnabled || currentExternalProgramId !== program.id) ? `
                                <button class="btn btn-primary" style="padding: 0.6rem 1.2rem; font-size: 0.85rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);"
                                    onclick="enableExternalReg(${program.id})">
                                    <i class="fa-solid fa-rocket"></i> Enable Public Reg
                                </button>
                            ` : isExternalRegEnabled && currentExternalProgramId === program.id ? `
                                <span style="color: #10b981; font-size: 0.85rem; font-weight: 700; padding: 0.6rem 1.2rem; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fa-solid fa-satellite-dish fa-spin"></i> LIVE ON SITE
                                </span>
                            ` : ''}
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

        if (!isEdit) {
            document.getElementById('externalProgramForm').reset();
            document.getElementById('externalProgramId').value = '';

            // Reset Payment UI
            document.getElementById('paymentSettings').style.display = 'none';
            document.getElementById('gstSettings').style.display = 'none';
            document.getElementById('gstBreakdown').style.display = 'none';
            document.getElementById('programTotalAmount').value = '';
        }
    }

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
                document.getElementById('programIsActive').checked = parseInt(program.is_active) === 1;

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
            } else {
                Swal.fire('Error', 'Could not load program details', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Connection error', 'error');
        }
    }

    async function deleteExternalProgram(id, name) {
        const confirm = await Swal.fire({
            title: 'Delete External Program?',
            text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Delete Program',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch(`/Project/EntryX/api/external_programs.php?action=delete&id=${id}`, {
                    method: 'POST'
                });
                const result = await res.json();

                if (result.success) {
                    Swal.fire('Deleted!', 'Program has been removed.', 'success');
                    loadExternalPrograms();
                } else {
                    Swal.fire('Error', result.error, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Failed to delete program', 'error');
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
                    loadExternalRegStatus();
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
                    loadExternalRegStatus();
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
        const data = {
            program_name: formData.get('program_name'),
            program_description: formData.get('program_description'),
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date'),
            max_participants: formData.get('max_participants'),
            is_active: document.getElementById('programIsActive').checked ? 1 : 0,
            // Payment fields
            is_paid: document.getElementById('programIsPaid').checked ? 1 : 0,
            registration_fee: formData.get('registration_fee') || 0,
            currency: formData.get('currency') || 'INR',
            is_gst_enabled: document.getElementById('programGstEnabled').checked ? 1 : 0,
            gst_rate: formData.get('gst_rate') || 18,
            payment_gateway: formData.get('payment_gateway') || 'razorpay'
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

    window.onclick = function (event) {
        if (event.target == modal) closeModal();
        if (event.target == document.getElementById('adminModal')) document.getElementById('adminModal').style.display = 'none';
        if (event.target == document.getElementById('externalProgramModal')) closeExternalProgramModal();
    }
</script>

<?php require_once '../includes/footer.php'; ?>