<?php
// Session and Authentication Checks MUST come before any includes
session_start();

// Access Control - Students and External Users Only
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

// Redirect admins to their dashboards
if (in_array($_SESSION['role'], ['super_admin', 'event_admin', 'security'])) {
    header('Location: admin_dashboard.php');
    exit;
}

// Redirect External Users to their dedicated dashboard
if ($_SESSION['role'] === 'external') {
    header('Location: external_dashboard.php');
    exit;
}

// NOW we can safely include files that output content
require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../classes/Event.php';
require_once '../classes/Registration.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$userRole = $_SESSION['role'];

$eventObj = new Event($pdo);
$regObj = new Registration($pdo);

$allEvents = $eventObj->getAllEvents();
$myRegs = $regObj->getUserRegistrations($userId);

// Filter available events
$registeredEventIds = array_column($myRegs, 'event_id');
$availableEvents = array_filter($allEvents, function ($e) use ($registeredEventIds) {
    return !in_array($e['id'], $registeredEventIds);
});

// Calculate stats
$totalEventsJoined = count($myRegs);
$upcomingEventsCount = count($availableEvents);
?>

<style>
    .dashboard-container {
        padding: 2rem 0;
        position: relative;
    }

    /* Animated Background Particles */
    .dashboard-container::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background:
            radial-gradient(circle at 10% 20%, rgba(255, 31, 31, 0.05) 0%, transparent 40%),
            radial-gradient(circle at 90% 80%, rgba(255, 31, 31, 0.03) 0%, transparent 40%),
            radial-gradient(circle at 50% 50%, rgba(10, 10, 10, 1) 0%, rgba(0, 0, 0, 1) 100%);
        pointer-events: none;
        z-index: -1;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.05);
            opacity: 0.8;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
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
        margin-bottom: 1.5rem;
        padding: 0 1rem;
    }

    .welcome-card-premium {
        background: linear-gradient(135deg, rgba(255, 31, 31, 0.05) 0%, rgba(10, 10, 10, 0.8) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 31, 31, 0.15);
        border-radius: 32px;
        padding: 4rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .welcome-card-premium::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(255, 31, 31, 0.08), transparent 60%);
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

    .welcome-text {
        flex: 1;
        max-width: 700px;
    }

    .welcome-text h1 {
        font-size: 4rem;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #ffffff 0%, #ff1f1f 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.1;
    }

    .welcome-text p {
        color: var(--p-text-dim);
        font-size: 1.3rem;
        line-height: 1.6;
        font-weight: 500;
    }

    .user-avatar-large {
        width: 130px;
        height: 130px;
        border-radius: 35px;
        background: linear-gradient(45deg, var(--p-brand), #ff4d4d);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 4rem;
        font-weight: 900;
        box-shadow: 0 25px 50px rgba(255, 31, 31, 0.4);
        flex-shrink: 0;
        transform: rotate(-5deg);
        transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .user-avatar-large:hover {
        transform: rotate(0deg) scale(1.1);
    }

    /* Premium Logout Button */
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
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        align-items: center;
        gap: 0.8rem;
        box-shadow: 0 5px 20px rgba(239, 68, 68, 0.15);
        border: none;
        outline: none;
    }

    .logout-btn-premium:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.5);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
    }

    .logout-btn-premium i {
        font-size: 1.1rem;
        transition: transform 0.3s ease;
    }

    .logout-btn-premium:hover i {
        transform: rotate(15deg);
    }

    /* User Info Badge */
    .user-info-badge {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.7rem 1.8rem;
        border-radius: 999px;
        font-size: 0.9rem;
        color: white;
        font-weight: 600;
        letter-spacing: 0.02em;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .user-info-badge .role-badge {
        display: inline-block;
        padding: 0.3rem 1rem;
        background: rgba(255, 31, 31, 0.2);
        border-radius: 999px;
        color: var(--p-brand);
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2.5rem;
        margin-bottom: 5rem;
    }

    .stat-card-ultra {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.01) 100%);
        border: 1px solid var(--p-border);
        border-radius: 28px;
        padding: 2.5rem;
        display: flex;
        align-items: center;
        gap: 2rem;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
    }

    .stat-card-ultra::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 31, 31, 0.05) 0%, transparent 100%);
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    .stat-card-ultra:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 31, 31, 0.3);
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
    }

    .stat-card-ultra:hover::before {
        opacity: 1;
    }

    .stat-icon-wrapper {
        width: 72px;
        height: 72px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        background: linear-gradient(135deg, rgba(255, 31, 31, 0.15) 0%, rgba(255, 31, 31, 0.05) 100%);
        color: var(--p-brand);
        box-shadow: 0 10px 30px rgba(255, 31, 31, 0.2);
    }

    .section-title {
        font-size: 2.8rem;
        margin-bottom: 3.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        color: white;
        font-weight: 900;
        letter-spacing: -0.04em;
        text-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
    }

    .section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0.1), transparent);
    }

    .event-card-glass {
        background: linear-gradient(135deg, rgba(15, 15, 15, 0.9) 0%, rgba(10, 10, 10, 0.8) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid var(--p-border);
        border-radius: 28px;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
    }

    .event-card-glass::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 28px;
        padding: 1px;
        background: linear-gradient(135deg, rgba(255, 31, 31, 0.2), transparent);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.5s ease;
        pointer-events: none;
    }

    .event-card-glass:hover {
        transform: translateY(-12px);
        border-color: rgba(255, 31, 31, 0.4);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(255, 31, 31, 0.1);
    }

    .event-card-glass:hover::after {
        opacity: 1;
    }

    .card-banner {
        height: 140px;
        background: linear-gradient(135deg, rgba(255, 31, 31, 0.15) 0%, rgba(0, 0, 0, 0.3) 100%);
        padding: 2rem;
        display: flex;
        justify-content: flex-end;
        align-items: flex-start;
        position: relative;
        overflow: hidden;
    }

    .card-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -30%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255, 31, 31, 0.1), transparent 60%);
        filter: blur(50px);
    }

    .card-content {
        padding: 2.5rem;
    }

    .event-meta-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        color: var(--p-text-dim);
        font-size: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .event-meta-item:hover {
        color: white;
        transform: translateX(5px);
    }

    .event-meta-item i {
        color: var(--p-brand);
        width: 24px;
        font-size: 1.1rem;
    }

    /* Premium Price Badge */
    .price-badge {
        background: linear-gradient(135deg, rgba(255, 31, 31, 0.2) 0%, rgba(255, 31, 31, 0.1) 100%);
        color: var(--p-brand);
        padding: 0.6rem 1.5rem;
        border-radius: 999px;
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: 0.05em;
        border: 1px solid rgba(255, 31, 31, 0.3);
        box-shadow: 0 5px 20px rgba(255, 31, 31, 0.2);
    }

    .price-badge.free {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
        color: #10b981;
        border-color: rgba(16, 185, 129, 0.3);
        box-shadow: 0 5px 20px rgba(16, 185, 129, 0.2);
    }
</style>


<div class="dashboard-container">
    <!-- Welcome Section with Top Bar -->
    <div class="welcome-section reveal">
        <!-- Top Bar: Logout and User Info -->
        <div class="dashboard-top-bar">
            <div class="user-info-badge">
                <span><?php echo htmlspecialchars($userName); ?></span>
                <span class="role-badge"><?php echo str_replace('_', ' ', strtoupper($userRole)); ?></span>
            </div>
            <button class="logout-btn-premium" onclick="confirmLogout()">
                <i class="fa-solid fa-power-off"></i>
                <span>Logout</span>
            </button>
        </div>

        <!-- Welcome Card -->
        <div class="welcome-card-premium">
            <div class="welcome-text">
                <span
                    style="background: rgba(255,31,31,0.2); color: var(--p-brand); padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1.5rem; display: inline-block; border: 1px solid rgba(255,31,31,0.3);">
                    <i class="fa-solid fa-crown" style="margin-right: 0.5rem;"></i> Member Exclusive
                </span>
                <h1>Hi, <?php echo explode(' ', $userName)[0]; ?>! 👋</h1>
                <p>Welcome to your <strong>Event Experience Hub</strong>. Your next big story starts here.</p>
            </div>
            <div class="user-avatar-large">
                <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card-ultra reveal" style="animation-delay: 0.1s;">
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
                <div
                    style="color: var(--p-text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">
                    Events Joined</div>
                <div style="font-size: 2rem; font-weight: 800; color: white; line-height: 1;">
                    <?php echo $totalEventsJoined; ?>
                </div>
            </div>
        </div>
        <div class="stat-card-ultra reveal" style="animation-delay: 0.2s;">
            <div class="stat-icon-wrapper" style="color: #ff9800; background: rgba(255, 152, 0, 0.1);">
                <i class="fa-solid fa-fire-flame-curved"></i>
            </div>
            <div>
                <div
                    style="color: var(--p-text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">
                    Trending</div>
                <div style="font-size: 2rem; font-weight: 800; color: white; line-height: 1;">
                    <?php echo $upcomingEventsCount; ?>
                </div>
            </div>
        </div>
        <div class="stat-card-ultra reveal" style="animation-delay: 0.3s;">
            <div class="stat-icon-wrapper" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <div
                    style="color: var(--p-text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">
                    Current Status</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: white; line-height: 1;">
                    <?php echo str_replace('_', ' ', strtoupper($userRole)); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Hall of Fame / Results section -->
    <h2 class="section-title reveal"><i class="fa-solid fa-trophy" style="color: #eab308;"></i> Hall of Fame</h2>
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2.5rem; margin-bottom: 5rem;">
        <?php
        $resStmt = $pdo->query("SELECT r.*, e.name as event_name FROM results r JOIN events e ON r.event_id = e.id ORDER BY r.published_at DESC LIMIT 3");
        $recentResults = $resStmt->fetchAll();

        if (empty($recentResults)): ?>
            <div class="glass-panel reveal" style="padding: 4rem; text-align: center; grid-column: 1/-1;">
                <p style="color: var(--p-text-dim);">Historical records will appear here once published.</p>
            </div>
        <?php else:
            foreach ($recentResults as $idx => $res): ?>
                <div class="glass-panel reveal"
                    style="padding: 2.5rem; border-color: rgba(234, 179, 8, 0.2); background: rgba(234,179,8,0.03); position: relative; overflow: hidden; animation-delay: <?php echo $idx * 0.1; ?>s;">
                    <div style="position: absolute; top: -15px; right: -15px; color: rgba(234,179,8,0.1);">
                        <i class="fa-solid fa-medal fa-6x"></i>
                    </div>
                    <div
                        style="width: 50px; height: 50px; background: rgba(234,179,8,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #eab308; margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-award fa-xl"></i>
                    </div>
                    <h3 style="color: white; font-weight: 800; font-size: 1.5rem; margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($res['winner_name']); ?>
                    </h3>
                    <p
                        style="color: #eab308; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 1.5rem;">
                        Winner - <?php echo htmlspecialchars($res['event_name']); ?></p>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1.5rem;">
                        <span style="color: var(--p-text-dim); font-size: 0.9rem; font-weight: 600;">Runner Up:
                            <?php echo htmlspecialchars($res['runner_up_name']); ?></span>
                        <a href="results.php"
                            style="color: var(--p-brand); font-size: 0.9rem; font-weight: 700; text-decoration: none;">Explore
                            All <i class="fa-solid fa-arrow-right fa-xs"></i></a>
                    </div>
                </div>
            <?php endforeach;
        endif; ?>
    </div>

    <!-- Registered Events -->
    <h2 class="section-title reveal"><i class="fa-solid fa-ticket-simple" style="color: var(--p-brand);"></i> My Event
        Passes
    </h2>
    <?php if (empty($myRegs)): ?>
        <div class="glass-panel reveal" style="padding: 4rem; text-align: center; margin-bottom: 5rem;">
            <i class="fa-regular fa-folder-open"
                style="font-size: 3.5rem; color: var(--p-text-muted); margin-bottom: 1.5rem;"></i>
            <p style="color: var(--p-text-dim); font-size: 1.1rem;">You haven't secured any passes yet.</p>
            <a href="#explore" class="btn btn-outline" style="margin-top: 2rem;">Explore Events</a>
        </div>
    <?php else: ?>
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2.5rem; margin-bottom: 6rem;">
            <?php foreach ($myRegs as $idx => $reg): ?>
                <div class="event-card-glass reveal" style="animation-delay: <?php echo $idx * 0.1; ?>s;">
                    <div class="card-banner">
                        <span
                            class="status-badge <?php echo in_array($reg['payment_status'], ['free', 'completed']) ? 'status-inside' : 'status-pending'; ?>">
                            <?php echo strtoupper(in_array($reg['payment_status'], ['free', 'completed']) ? 'Confirmed' : $reg['payment_status']); ?>
                        </span>
                    </div>
                    <div class="card-content">
                        <h3 style="font-size: 1.4rem; margin-bottom: 1.5rem; color: white;">
                            <?php echo htmlspecialchars($reg['event_name']); ?>
                        </h3>
                        <div class="event-meta-item">
                            <i class="fa-regular fa-calendar"></i>
                            <span><?php echo date('D, M d | h:i A', strtotime($reg['event_date'])); ?></span>
                        </div>
                        <div class="event-meta-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?php echo htmlspecialchars($reg['venue']); ?></span>
                        </div>
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button class="btn btn-primary" style="flex: 2;"
                                onclick="showTicket('<?php echo $reg['qr_token']; ?>', '<?php echo htmlspecialchars($reg['event_name']); ?>')">
                                <i class="fa-solid fa-qrcode"></i> View Ticket
                            </button>
                            <button class="btn btn-outline"
                                style="flex: 1; border-color: rgba(239, 68, 68, 0.3); color: #ef4444;"
                                onclick="cancelRegistration(<?php echo $reg['id']; ?>, '<?php echo htmlspecialchars($reg['event_name']); ?>')">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Explore Events -->
    <h2 id="explore" class="section-title reveal"><i class="fa-solid fa-sparkles" style="color: #a855f7;"></i>
        Discover Upcoming Events</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2.5rem;">
        <?php foreach ($availableEvents as $idx => $event): ?>
            <div class="event-card-glass reveal" style="animation-delay: <?php echo $idx * 0.1; ?>s;">
                <div class="card-banner"
                    style="background: linear-gradient(135deg, rgba(255, 31, 31, 0.1) 0%, rgba(10, 10, 10, 0.5) 100%);">
                    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                        <?php if ($event['capacity'] < 10): ?>
                            <span
                                style="background: #ef4444; color: white; padding: 0.4rem 1rem; border-radius: 99px; font-size: 0.75rem; font-weight: 800; animation: pulse 2s infinite;">
                                <i class="fa-solid fa-fire"></i> ONLY <?php echo $event['capacity']; ?> LEFT
                            </span>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>

                        <?php if ($event['is_paid']): ?>
                            <span class="price-badge">
                                ₹<?php echo number_format($event['base_price'], 2); ?>
                            </span>
                        <?php else: ?>
                            <span class="price-badge free">FREE ACCESS</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-content">
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <h3 style="font-size: 1.6rem; color: white; font-weight: 800; flex: 1;">
                            <?php echo htmlspecialchars($event['name']); ?>
                        </h3>
                        <div
                            style="background: rgba(255,255,255,0.05); padding: 0.4rem 0.8rem; border-radius: 12px; font-size: 0.7rem; color: var(--p-text-dim); border: 1px solid rgba(255,255,255,0.1);">
                            <i class="fa-solid fa-user-group"></i> <?php echo $event['capacity']; ?> Slots
                        </div>
                    </div>
                    <p
                        style="color: var(--p-text-dim); font-size: 1.05rem; line-height: 1.7; margin-bottom: 2.5rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        <?php echo htmlspecialchars($event['description']); ?>
                    </p>
                    <div
                        style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid rgba(255,255,255,0.03);">
                        <div class="event-meta-item" style="margin-bottom: 1.2rem;">
                            <i class="fa-regular fa-calendar-check"></i>
                            <span
                                style="font-weight: 600;"><?php echo date('l, M d, Y', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="event-meta-item" style="margin-bottom: 1.2rem;">
                            <i class="fa-regular fa-clock"></i>
                            <span
                                style="font-weight: 600;"><?php echo date('h:i A', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="event-meta-item" style="margin: 0;">
                            <i class="fa-solid fa-location-dot"></i>
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($event['venue']); ?></span>
                        </div>
                    </div>
                    <button class="btn btn-primary"
                        style="width: 100%; padding: 1.2rem; border-radius: 18px; font-weight: 800; font-size: 1rem; box-shadow: 0 15px 30px rgba(255,31,31,0.2);"
                        onclick="registerEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                        Register <i class="fa-solid fa-arrow-right-long"
                            style="margin-left: 0.8rem; transition: transform 0.3s ease;"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


<!-- Premium Ticket Modal -->
<div id="ticketModal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); backdrop-filter: blur(15px); z-index: 2000; justify-content: center; align-items: center; padding: 1rem;">
    <div class="glass-panel"
        style="max-width: 480px; width: 100%; padding: 0; border-radius: 40px; border-color: rgba(255,31,31,0.3); position: relative; overflow: visible;">

        <!-- Ticket Header -->
        <div
            style="background: var(--grad-crimson); padding: 3rem 2rem; border-radius: 40px 40px 0 0; text-align: center; position: relative;">
            <div
                style="position: absolute; top: -20px; left: 50%; transform: translateX(-50%); background: #0a0a0a; padding: 10px 20px; border-radius: 20px; border: 1px solid rgba(255,31,31,0.3);">
                <span
                    style="color: var(--p-brand); font-weight: 800; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.2em;">Official
                    Event Pass</span>
            </div>
            <h3 id="ticketEventName"
                style="margin-bottom: 0.5rem; color: white; font-size: 2rem; letter-spacing: -0.02em;">Event Ticket
            </h3>
            <p style="color: rgba(255,255,255,0.7); font-weight: 600;">ENTRYX SECURE DIGITAL TOKEN</p>

            <button onclick="document.getElementById('ticketModal').style.display='none'"
                style="position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.2); border: none; width: 40px; height: 40px; border-radius: 50%; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Ticket Body -->
        <div style="padding: 3rem 2.5rem; text-align: center; background: #fff; border-radius: 0 0 40px 40px;">
            <div
                style="background: #f8fafc; padding: 2.5rem; border-radius: 30px; border: 2px dashed #cbd5e1; display: inline-block; margin-bottom: 2.5rem; position: relative;">
                <div id="qrContainer"
                    style="width: 260px; height: 260px; display: flex; align-items: center; justify-content: center; background: white;">
                </div>
                <div
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 15px solid #fff; pointer-events: none; border-radius: 20px;">
                </div>
            </div>

            <div
                style="text-align: left; background: #f8fafc; padding: 1.8rem; border-radius: 24px; margin-bottom: 2.5rem; border: 1px solid #e2e8f0;">
                <div
                    style="display: flex; align-items: center; gap: 1rem; color: #94a3b8; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.1em;">
                    <i class="fa-solid fa-user-astronaut" style="color: var(--p-brand);"></i> Pass Holder
                </div>
                <div style="color: #0f172a; font-weight: 900; font-size: 1.4rem; font-family: 'Plus Jakarta Sans';">
                    <?php echo htmlspecialchars($userName); ?>
                </div>
                <div style="margin-top: 0.5rem; color: #64748b; font-size: 0.85rem; font-weight: 600;">System Verified
                    Member</div>
            </div>

            <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6; margin-bottom: 2.5rem; font-weight: 500;">
                Please present this secure token at the terminal. This pass is unique to your account and valid for one
                entry only.
            </p>

            <div style="display: flex; gap: 1rem;">
                <button onclick="document.getElementById('ticketModal').style.display='none'" class="btn btn-outline"
                    style="flex: 1; background: #f1f5f9; color: #475569; border: none; padding: 1.2rem; border-radius: 18px; font-weight: 700;">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
                <button class="btn btn-primary" style="flex: 2; padding: 1.2rem; border-radius: 18px;">
                    <i class="fa-solid fa-download"></i> Download
                </button>
            </div>
        </div>

        <!-- Decorative Circles for Ticket Look -->
        <div
            style="position: absolute; left: -15px; top: 50%; width: 30px; height: 30px; background: rgba(0,0,0,0.95); border-radius: 50%; transform: translateY(-50%);">
        </div>
        <div
            style="position: absolute; right: -15px; top: 50%; width: 30px; height: 30px; background: rgba(0,0,0,0.95); border-radius: 50%; transform: translateY(-50%);">
        </div>
    </div>
</div>



<!-- Manual Payment Modal (Restored) -->
<div id="paymentModal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); z-index: 3000; justify-content: center; align-items: center; padding: 1rem;">
    <div class="glass-panel"
        style="max-width: 450px; width: 100%; border-radius: 30px; border-color: rgba(255,165,0,0.3); background: #0a0a0a; color: #fff; position: relative; padding: 2rem;">
        <h3 id="payEventName" style="color: #fff; font-weight: 800; margin-bottom: 0.5rem; text-align: center;">Event
            Payment</h3>

        <div
            style="background: #fff; padding: 1.5rem; border-radius: 20px; text-align: center; margin-bottom: 2rem; color: #000;">
            <div id="payQr" style="display: inline-block; margin-bottom: 1rem;"></div>
            <div style="font-size: 2rem; font-weight: 900; color: #10b981;" id="payAmount">₹0.00</div>
            <div style="color: #64748b; font-size: 0.8rem; font-weight: 600;">UPI: <span id="payUpiId"
                    style="color: #0f172a;"></span></div>
        </div>

        <div style="margin-bottom: 2rem;">
            <label
                style="color: #94a3b8; font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.5rem;">TRANSACTION
                ID / UTR</label>
            <input type="text" id="payTransactionId" placeholder="Enter 12-digit UTR..."
                style="width: 100%; padding: 1rem; border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; font-family: monospace;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button onclick="document.getElementById('paymentModal').style.display='none'" class="btn btn-outline"
                style="flex: 1; border-color: rgba(255,255,255,0.1); color: #94a3b8;">Cancel</button>
            <button onclick="submitPayment()" class="btn btn-primary"
                style="flex: 2; background: #eab308; color: #000; border: none;">Verify & Register</button>
        </div>
    </div>
</div>
<script>
    let eventQrInstance = null;
    function showTicket(token, name) {
        document.getElementById('ticketEventName').textContent = name;
        const container = document.getElementById('qrContainer');
        container.innerHTML = ''; // Clear previous

        qrInstance = new QRCode(container, {
            text: token,
            width: 240,
            height: 240,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        document.getElementById('ticketModal').style.display = 'flex';
    }

    // Event Registration Logic
    let currentEventId = null;

    async function registerEvent(event) {
        const eventId = typeof event === 'object' ? event.id : event;
        const isPaid = (typeof event === 'object' && event.is_paid == '1');

        if (isPaid) {
            currentEventId = eventId;
            const upiId = event.payment_upi || 'admin@upi';
            const amount = event.base_price || 0;

            document.getElementById('payEventName').innerText = event.name || 'Event';
            document.getElementById('payAmount').innerText = '₹' + parseFloat(amount).toFixed(2);
            document.getElementById('payUpiId').innerText = upiId;
            document.getElementById('payTransactionId').value = '';

            // Generate QR
            const qrContainer = document.getElementById('payQr');
            qrContainer.innerHTML = '';
            const qrUrl = `upi://pay?pa=${upiId}&pn=EntryX&am=${amount}&tn=Reg ${eventId}`;

            new QRCode(qrContainer, {
                text: qrUrl,
                width: 160,
                height: 160,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });

            document.getElementById('paymentModal').style.display = 'flex';
            return;
        }

        proceedWithRegistration(eventId);
    }

    // Confirm Payment
    function submitPayment() {
        const txId = document.getElementById('payTransactionId').value.trim();
        if (!txId || txId.length < 5) {
            Swal.fire('Error', 'Invalid Transaction ID', 'error');
            return;
        }
        document.getElementById('paymentModal').style.display = 'none';
        proceedWithRegistration(currentEventId, txId);
    }

    async function proceedWithRegistration(eventId, transactionId = null) {
        // Fallback if Swal is not loaded
        if (typeof Swal === 'undefined') {
            if (confirm('Confirm Registration: Secure your spot now?')) {
                // If the fallback doesn't exist, we'll try to use the API directly or just alert
                try {
                    const response = await fetch('../api/registrations.php?action=create', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ event_id: eventId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Registration Successful!');
                        window.location.href = 'student_dashboard.php?registered=1';
                    } else {
                        alert('Error: ' + result.error);
                    }
                } catch (e) {
                    alert('Registration failed. Please try again.');
                }
            }
            return;
        }

        const confirmResult = await Swal.fire({
            title: 'Confirm Registration',
            text: 'Register for this event?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ff1f1f',
            confirmButtonText: 'Register',
            cancelButtonText: 'Cancel',
            background: '#0a0a0a',
            color: '#fff',
            customClass: {
                popup: 'glass-panel'
            }
        });

        if (confirmResult.isConfirmed) {
            Swal.fire({
                title: 'Registering...',
                html: 'Please wait while we secure your spot',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
                background: '#0a0a0a',
                color: '#fff'
            });

            try {
                const response = await fetch('../api/registrations.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        event_id: eventId,
                        transaction_id: transactionId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Done!',
                        text: result.payment_needed ? 'Registration submitted for verification.' : 'You are now registered.',
                        confirmButtonColor: '#ff1f1f',
                        background: '#0a0a0a',
                        color: '#fff',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    const redirectParam = result.payment_needed ? 'submitted=1' : 'registered=1';
                    window.location.href = `student_dashboard.php?${redirectParam}`;
                } else {
                    throw new Error(result.error || 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Registration failed:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: error.message,
                    confirmButtonColor: '#ff1f1f',
                    background: '#0a0a0a',
                    color: '#fff'
                });
            }
        }
    }

    // Show success message if redirected after registration
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('registered') || urlParams.has('submitted')) {
            const isPending = urlParams.has('submitted');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: isPending ? 'Registration Submitted (Pending Verification)' : 'Registration confirmed!',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    background: isPending ? '#f59e0b' : '#10b981',
                    color: '#fff'
                });
            }

            // Clear the URL parameters without refreshing the page
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);

            // Highlight the "My Event Passes" section
            const myPassesHeader = document.querySelector('.section-title');
            if (myPassesHeader) {
                myPassesHeader.scrollIntoView({ behavior: 'smooth' });
                myPassesHeader.style.color = '#ff1f1f';
                setTimeout(() => { myPassesHeader.style.color = 'white'; }, 2000);
            }
        }
    });

    async function cancelRegistration(regId, eventName) {
        if (typeof Swal === 'undefined') {
            if (confirm(`Are you sure you want to cancel your registration for ${eventName}?`)) {
                try {
                    const response = await fetch('../api/registrations.php?action=cancel', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ registration_id: regId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Registration cancelled successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + result.error);
                    }
                } catch (e) {
                    alert('Cancellation failed.');
                }
            }
            return;
        }

        const confirmResult = await Swal.fire({
            title: 'Cancel Registration?',
            text: `Remove your spot for ${eventName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Cancel Registration',
            cancelButtonText: 'Keep Spot',
            background: '#0a0a0a',
            color: '#fff',
            customClass: {
                popup: 'glass-panel'
            }
        });

        if (confirmResult.isConfirmed) {
            Swal.fire({
                title: 'Cancelling...',
                didOpen: () => Swal.showLoading(),
                background: '#0a0a0a',
                color: '#fff'
            });

            try {
                const response = await fetch('../api/registrations.php?action=cancel', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ registration_id: regId })
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Cancelled',
                        text: 'Your registration has been successfully removed.',
                        confirmButtonColor: '#ff1f1f',
                        background: '#0a0a0a',
                        color: '#fff'
                    });
                    location.reload();
                } else {
                    throw new Error(result.error || 'Cancellation failed');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    confirmButtonColor: '#ff1f1f',
                    background: '#0a0a0a',
                    color: '#fff'
                });
            }
        }
    }

    // Professional Logout with Confirmation
    async function confirmLogout() {
        const confirmResult = await Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to exit?',
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
            // Show loading state
            Swal.fire({
                title: 'Logging Out...',
                text: 'See you soon!',
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

    window.onclick = function (event) {
        const modal = document.getElementById('ticketModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>


<script src="../assets/js/qrcode.min.js"></script>
<?php require_once '../includes/footer.php'; ?>