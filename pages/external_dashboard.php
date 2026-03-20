<?php
require_once '../config/project_root.php';
// Access Level Check - Only External Users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external') {
    header('Location: user_login.php');
    exit;
}

require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../classes/Event.php';
require_once '../classes/Registration.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$userEmail = $_SESSION['email'];

$eventObj = new Event($pdo);
$regObj = new Registration($pdo);

// Fetch User's Entry QR Token and Primary Program Details
$stmt = $pdo->prepare("
    SELECT u.qr_token, ep.program_name, ep.start_date, ep.program_description 
    FROM users u 
    LEFT JOIN external_programs ep ON u.external_program_id = ep.id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$primaryPass = $stmt->fetch(PDO::FETCH_ASSOC);

$allEvents = $eventObj->getAllEvents();
$fullRegList = $regObj->getUserRegistrations($userId);

// Filter out internal "General Admission" or "Campus Admission" registrations from the UI
$myRegs = array_filter($fullRegList, function($r) {
    if (stripos($r['event_name'], 'General Admission') !== false) return false;
    if (stripos($r['event_name'], 'Campus Admission') !== false) return false;
    if (stripos($r['event_name'], 'General Campus') !== false) return false;
    return true;
});

// Filter available events
$registeredEventIds = array_column($fullRegList, 'event_id');
$availableEvents = array_filter($allEvents, function ($e) use ($registeredEventIds) {
    // Hide if already registered
    if (in_array($e['id'], $registeredEventIds)) return false;
    
    // Hide 'internal' only events
    if (isset($e['type']) && $e['type'] === 'internal') return false;
    
    // Hide automatic admission events from the public ticket list
    if (stripos($e['name'], 'General Admission') !== false) return false;
    if (stripos($e['name'], 'Campus Admission') !== false) return false;
    if (stripos($e['name'], 'General Campus') !== false) return false;
    
    // Hide cancelled or completed events
    if (isset($e['status']) && in_array($e['status'], ['cancelled', 'completed'])) return false;
    
    // Hide past events
    if (isset($e['event_date']) && strtotime($e['event_date']) < strtotime('today')) return false;

    return true;
});

// Calculate stats
$totalEventsJoined = count($myRegs);
$upcomingEventsCount = count($availableEvents);
?>
<style>
    .dashboard-container { padding: 2.5rem 0; position: relative; }
    .dashboard-container::before {
        content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: radial-gradient(circle at 10% 20%, rgba(255,31,31,0.05) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(255,31,31,0.03) 0%, transparent 40%),
                    radial-gradient(circle at 50% 50%, rgba(10,10,10,1) 0%, rgba(0,0,0,1) 100%);
        pointer-events: none; z-index: -1;
    }
    .welcome-card-premium {
        background: linear-gradient(135deg, rgba(255,31,31,0.05) 0%, rgba(10,10,10,0.8) 100%);
        backdrop-filter: blur(20px); border: 1px solid rgba(255,31,31,0.15); border-radius: 32px;
        padding: 4rem; display: flex; justify-content: space-between; align-items: center;
        position: relative; overflow: hidden; margin-bottom: 4rem;
        box-shadow: 0 30px 60px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.03);
    }
    .welcome-card-premium::before {
        content: ''; position: absolute; top: -50%; right: -20%; width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(255,31,31,0.08), transparent 60%);
        filter: blur(60px); animation: float 8s ease-in-out infinite;
    }
    @keyframes float { 0%,100%{transform:translateY(0px) rotate(0deg);} 50%{transform:translateY(-20px) rotate(5deg);} }
    .welcome-text h1 {
        font-size: 4rem; margin-bottom: 1rem;
        background: linear-gradient(135deg, #ffffff 0%, #ff1f1f 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        font-weight: 900; letter-spacing: -0.03em; line-height: 1.1;
    }
    .welcome-text p { color: var(--p-text-dim); font-size: 1.3rem; line-height: 1.6; font-weight: 500; }
    .user-avatar-large {
        width: 130px; height: 130px; border-radius: 35px;
        background: linear-gradient(45deg, #10b981, #059669);
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 4rem; font-weight: 900;
        box-shadow: 0 25px 50px rgba(16,185,129,0.4); flex-shrink: 0;
        transform: rotate(-5deg); transition: 0.5s cubic-bezier(0.175,0.885,0.32,1.275);
    }
    .user-avatar-large:hover { transform: rotate(0deg) scale(1.1); }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; margin-bottom: 5rem; }
    .stat-card-ultra {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        border: 1px solid var(--p-border); border-radius: 28px; padding: 2.5rem;
        display: flex; align-items: center; gap: 2rem;
        transition: all 0.5s cubic-bezier(0.175,0.885,0.32,1.275); position: relative; overflow: hidden;
    }
    .stat-card-ultra:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,31,31,0.3); transform: translateY(-8px) scale(1.02); box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
    .stat-icon-wrapper { width: 72px; height: 72px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; background: linear-gradient(135deg, rgba(255,31,31,0.15) 0%, rgba(255,31,31,0.05) 100%); color: var(--p-brand); box-shadow: 0 10px 30px rgba(255,31,31,0.2); }
    .section-title { font-size: 2.8rem; margin-bottom: 3.5rem; display: flex; align-items: center; gap: 1.5rem; color: white; font-weight: 900; letter-spacing: -0.04em; text-shadow: 0 10px 20px rgba(0,0,0,0.5); }
    .section-title::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, rgba(255,255,255,0.1), transparent); }
    .event-card-glass { background: linear-gradient(135deg, rgba(15,15,15,0.9) 0%, rgba(10,10,10,0.8) 100%); backdrop-filter: blur(20px); border: 1px solid var(--p-border); border-radius: 28px; overflow: hidden; transition: all 0.5s cubic-bezier(0.165,0.84,0.44,1); position: relative; }
    .event-card-glass:hover { transform: translateY(-12px); border-color: rgba(255,31,31,0.4); box-shadow: 0 30px 60px rgba(0,0,0,0.5), 0 0 40px rgba(255,31,31,0.1); }
    .card-banner { height: 140px; background: linear-gradient(135deg, rgba(255,31,31,0.15) 0%, rgba(0,0,0,0.3) 100%); padding: 2rem; display: flex; justify-content: flex-end; align-items: flex-start; position: relative; overflow: hidden; }
    .card-content { padding: 2.5rem; }
    .event-meta-item { display: flex; align-items: center; gap: 1rem; color: var(--p-text-dim); font-size: 1rem; margin-bottom: 1rem; transition: all 0.3s ease; }
    .event-meta-item:hover { color: white; transform: translateX(5px); }
    .event-meta-item i { color: var(--p-brand); width: 24px; font-size: 1.1rem; }
    .price-badge { background: linear-gradient(135deg, rgba(255,31,31,0.2) 0%, rgba(255,31,31,0.1) 100%); color: var(--p-brand); padding: 0.6rem 1.5rem; border-radius: 999px; font-weight: 800; font-size: 1.1rem; letter-spacing: 0.05em; border: 1px solid rgba(255,31,31,0.3); box-shadow: 0 5px 20px rgba(255,31,31,0.2); }
    .price-badge.free { background: linear-gradient(135deg, rgba(16,185,129,0.2) 0%, rgba(16,185,129,0.1) 100%); color: #10b981; border-color: rgba(16,185,129,0.3); box-shadow: 0 5px 20px rgba(16,185,129,0.2); }
    @keyframes pulse { 0%{transform:scale(1);opacity:1;} 50%{transform:scale(1.05);opacity:0.8;} 100%{transform:scale(1);opacity:1;} }
    @media (max-width: 768px) { .welcome-card-premium{padding:2rem;flex-direction:column;gap:2rem;} .welcome-text h1{font-size:2.5rem;} .user-avatar-large{width:80px;height:80px;font-size:2.5rem;} }
</style>

<div class="dashboard-container">

    <!-- Welcome Section -->
    <div class="welcome-section reveal" style="margin-bottom: 4rem;">
        <div class="welcome-card-premium">
            <div class="welcome-text">
                <span style="background: rgba(16,185,129,0.2); color: #10b981; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1.5rem; display: inline-block; border: 1px solid rgba(16,185,129,0.3);">
                    <i class="fa-solid fa-star" style="margin-right: 0.5rem;"></i> Guest Pass
                </span>
                <h1>Hi, <?php echo explode(' ', $userName)[0]; ?>! 👋</h1>
                <p>Welcome to your <strong>Guest Experience Hub</strong>. Explore and register for events.</p>
                <button onclick="showEntryQR()" class="btn btn-primary"
                    style="margin-top: 2rem; border-radius: 99px; padding: 0.8rem 2rem; display: inline-flex; align-items: center; gap: 0.6rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; font-weight: 700;">
                    <i class="fa-solid fa-qrcode"></i> View Entry QR Code
                </button>
            </div>
            <div class="user-avatar-large"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card-ultra reveal" style="animation-delay: 0.1s;">
            <div class="stat-icon-wrapper" style="color: #10b981; background: rgba(16,185,129,0.1);"><i class="fa-solid fa-ticket"></i></div>
            <div>
                <div style="color: var(--p-text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">Events Joined</div>
                <div style="font-size: 2rem; font-weight: 800; color: white; line-height: 1;"><?php echo $totalEventsJoined; ?></div>
            </div>
        </div>
        <div class="stat-card-ultra reveal" style="animation-delay: 0.2s;">
            <div class="stat-icon-wrapper" style="color: #ff9800; background: rgba(255,152,0,0.1);"><i class="fa-solid fa-fire-flame-curved"></i></div>
            <div>
                <div style="color: var(--p-text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">Available Events</div>
                <div style="font-size: 2rem; font-weight: 800; color: white; line-height: 1;"><?php echo $upcomingEventsCount; ?></div>
            </div>
        </div>
        <div class="stat-card-ultra reveal" style="animation-delay: 0.3s;">
            <div class="stat-icon-wrapper" style="color: #10b981; background: rgba(16,185,129,0.1);"><i class="fa-solid fa-user-shield"></i></div>
            <div>
                <div style="color: var(--p-text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">Status</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: white; line-height: 1;">EXTERNAL GUEST</div>
            </div>
        </div>
    </div>

    <!-- Hall of Fame -->
    <h2 class="section-title reveal"><i class="fa-solid fa-trophy" style="color: #eab308;"></i> Hall of Fame</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2.5rem; margin-bottom: 5rem;">
        <?php
        $resStmt = $pdo->query("SELECT r.*, e.name as event_name FROM results r JOIN events e ON r.event_id = e.id ORDER BY r.published_at DESC LIMIT 3");
        $recentResults = $resStmt->fetchAll();
        if (empty($recentResults)): ?>
            <div class="glass-panel reveal" style="padding: 4rem; text-align: center; grid-column: 1/-1;">
                <p style="color: var(--p-text-dim);">Historical records will appear here once published.</p>
            </div>
        <?php else: foreach ($recentResults as $idx => $res): ?>
            <div class="glass-panel reveal" style="padding: 2.5rem; border-color: rgba(234,179,8,0.2); background: rgba(234,179,8,0.03); position: relative; overflow: hidden; animation-delay: <?php echo $idx * 0.1; ?>s;">
                <div style="position: absolute; top: -15px; right: -15px; color: rgba(234,179,8,0.1);"><i class="fa-solid fa-medal fa-6x"></i></div>
                <div style="width: 50px; height: 50px; background: rgba(234,179,8,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #eab308; margin-bottom: 1.5rem;"><i class="fa-solid fa-award fa-xl"></i></div>
                <h3 style="color: white; font-weight: 800; font-size: 1.5rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($res['winner_name']); ?></h3>
                <p style="color: #eab308; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 1.5rem;">Winner - <?php echo htmlspecialchars($res['event_name']); ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1.5rem;">
                    <span style="color: var(--p-text-dim); font-size: 0.9rem; font-weight: 600;">Runner Up: <?php echo htmlspecialchars($res['runner_up_name']); ?></span>
                    <a href="results.php" style="color: var(--p-brand); font-size: 0.9rem; font-weight: 700; text-decoration: none;">Explore All <i class="fa-solid fa-arrow-right fa-xs"></i></a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- My Event Passes -->
    <h2 class="section-title reveal"><i class="fa-solid fa-ticket-simple" style="color: var(--p-brand);"></i> My Event Passes</h2>
    <?php if (empty($myRegs)): ?>
        <div class="glass-panel reveal" style="padding: 4rem; text-align: center; margin-bottom: 5rem;">
            <i class="fa-regular fa-folder-open" style="font-size: 3.5rem; color: var(--p-text-muted); margin-bottom: 1.5rem;"></i>
            <p style="color: var(--p-text-dim); font-size: 1.1rem;">You haven't secured any passes yet.</p>
            <a href="#explore" class="btn btn-outline" style="margin-top: 2rem;">Explore Events</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2.5rem; margin-bottom: 6rem;">
            <?php foreach ($myRegs as $idx => $reg): ?>
                <div class="event-card-glass reveal" style="animation-delay: <?php echo $idx * 0.1; ?>s;">
                    <div class="card-banner">
                        <span class="status-badge <?php echo in_array($reg['payment_status'], ['free', 'completed']) ? 'status-inside' : 'status-pending'; ?>">
                            <?php echo strtoupper(in_array($reg['payment_status'], ['free', 'completed']) ? 'Confirmed' : $reg['payment_status']); ?>
                        </span>
                    </div>
                    <div class="card-content">
                        <h3 style="font-size: 1.4rem; margin-bottom: 1.5rem; color: white;"><?php echo htmlspecialchars($reg['event_name']); ?></h3>
                        <div class="event-meta-item"><i class="fa-regular fa-calendar"></i><span><?php echo date('D, M d | h:i A', strtotime($reg['event_date'])); ?></span></div>
                        <div class="event-meta-item"><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($reg['venue']); ?></span></div>
                        <?php if (!empty($reg['team_name'])): ?>
                            <div class="event-meta-item" style="color: #10b981; font-weight: 700;"><i class="fa-solid fa-users"></i><span>Team: <?php echo htmlspecialchars($reg['team_name']); ?></span></div>
                        <?php endif; ?>
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button class="btn btn-primary" style="flex: 2;" onclick="showTicket(<?php echo htmlspecialchars(json_encode($reg)); ?>)">
                                <i class="fa-solid fa-qrcode"></i> View Ticket
                            </button>
                            <button class="btn btn-outline" style="flex: 1; border-color: rgba(239,68,68,0.3); color: #ef4444;"
                                onclick="cancelRegistration(<?php echo $reg['id']; ?>, '<?php echo htmlspecialchars($reg['event_name']); ?>')">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Discover Events -->
    <h2 id="explore" class="section-title reveal"><i class="fa-solid fa-sparkles" style="color: #a855f7;"></i> Discover Upcoming Events</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2.5rem; margin-bottom: 4rem;">
        <?php foreach ($availableEvents as $idx => $event): ?>
            <div class="event-card-glass reveal" style="animation-delay: <?php echo $idx * 0.1; ?>s;">
                <div class="card-banner" style="background: linear-gradient(135deg, rgba(255,31,31,0.1) 0%, rgba(10,10,10,0.5) 100%);">
                    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                        <?php if ($event['capacity'] < 10): ?>
                            <span style="background: #ef4444; color: white; padding: 0.4rem 1rem; border-radius: 99px; font-size: 0.75rem; font-weight: 800; animation: pulse 2s infinite;">
                                <i class="fa-solid fa-fire"></i> ONLY <?php echo $event['capacity']; ?> LEFT
                            </span>
                        <?php else: ?><span></span><?php endif; ?>
                        <?php
                        $isPaid = $event['is_paid'];
                        $displayPrice = floatval($event['base_price']);
                        if ($isPaid && !empty($event['is_gst_enabled']) && in_array($event['gst_target'] ?? '', ['both', 'externals_only'])) {
                            $displayPrice += $displayPrice * (floatval($event['gst_rate']) / 100);
                        }
                        ?>
                        <?php if ($isPaid): ?>
                            <span class="price-badge">₹<?php echo number_format($displayPrice, 2); ?></span>
                        <?php else: ?>
                            <span class="price-badge free">FREE ACCESS</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-content">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <h3 style="font-size: 1.6rem; color: white; font-weight: 800; flex: 1;"><?php echo htmlspecialchars($event['name']); ?></h3>
                        <div style="background: rgba(255,255,255,0.05); padding: 0.4rem 0.8rem; border-radius: 12px; font-size: 0.7rem; color: var(--p-text-dim); border: 1px solid rgba(255,255,255,0.1); white-space: nowrap; margin-left: 0.5rem;">
                            <i class="fa-solid fa-user-group"></i> <?php echo $event['capacity']; ?> Slots
                        </div>
                    </div>
                    <p style="color: var(--p-text-dim); font-size: 1.05rem; line-height: 1.7; margin-bottom: 2.5rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        <?php echo htmlspecialchars($event['description']); ?>
                    </p>
                    <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 20px; margin-bottom: 2rem; border: 1px solid rgba(255,255,255,0.03);">
                        <div class="event-meta-item" style="margin-bottom: 1.2rem;"><i class="fa-regular fa-calendar-check"></i><span style="font-weight: 600;"><?php echo date('l, M d, Y', strtotime($event['event_date'])); ?></span></div>
                        <div class="event-meta-item" style="margin-bottom: 1.2rem;"><i class="fa-regular fa-clock"></i><span style="font-weight: 600;"><?php echo date('h:i A', strtotime($event['event_date'])); ?></span></div>
                        <div class="event-meta-item" style="margin: 0;"><i class="fa-solid fa-location-dot"></i><span style="font-weight: 600;"><?php echo htmlspecialchars($event['venue']); ?></span></div>
                    </div>
                    <button class="btn btn-primary"
                        style="width: 100%; padding: 1.2rem; border-radius: 18px; font-weight: 800; font-size: 1rem; box-shadow: 0 15px 30px rgba(255,31,31,0.2);"
                        onclick="handleRegistration(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                        Register <i class="fa-solid fa-arrow-right-long" style="margin-left: 0.8rem;"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>


<div id="ticketModal"
    style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); z-index: 1000; align-items: flex-start; justify-content: center; padding: 2rem 1rem; overflow-y: auto;">
    <div class="glass-panel"
        style="width: 100%; max-width: 450px; max-height: 90vh; overflow-y: auto; padding: 0; background: white; border-radius: 30px; position: relative; border: none;">
        <button onclick="closeTicket()"
            style="position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.1); border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer;"><i
                class="fa-solid fa-times"></i></button>
        <div id="modalHeader"
            style="background: var(--p-brand); padding: 3rem 2rem; text-align: center; color: white; border-radius: 30px 30px 0 0;">
            <p
                style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.2rem; margin-bottom: 0.5rem; opacity: 0.8;">
                Event Ticket</p>
            <h2 id="modalEventName" style="font-weight: 900; margin: 0; font-size: 2rem;"></h2>
        </div>
        <div style="padding: 3rem 2rem; text-align: center;">
            <div id="modalQr"
                style="display: inline-block; padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 2rem;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <h4 style="color: #0f172a; font-weight: 800; font-size: 1.4rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($userName); ?>
                </h4>
                <div
                    style="display: flex; justify-content: center; gap: 1.5rem; color: #64748b; font-size: 0.9rem; font-weight: 600;">
                    <span id="modalDate"></span>
                    <span id="modalVenue"></span>
                </div>
            </div>
            <!-- One-time use notice -->
            <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 0.9rem 1rem; margin-bottom: 1.5rem; text-align: left;">
                <p style="color: #14532d; font-size: 0.8rem; margin: 0; font-weight: 700; display: flex; align-items: flex-start; gap: 0.5rem;">
                    <i class="fa-solid fa-shield-check" style="color: #16a34a; margin-top: 2px; flex-shrink:0;"></i>
                    <span>This QR code grants <strong>1 entry</strong> and <strong>1 exit</strong> for this event. After both are recorded, the QR is permanently locked.</span>
                </p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-outline"
                    style="flex: 1; border-radius: 12px; font-weight: 700; color: #0f172a; border-color: #e2e8f0; background: none; border-style: solid; border-width: 1px;"
                    onclick="window.print()">
                    <i class="fa-solid fa-download"></i> Download
                </button>
                <button id="cancelBtn" class="btn btn-outline"
                    style="flex: 1; border-radius: 12px; font-weight: 700; color: #ef4444; border-color: #fee2e2; background: #fff1f2; border-style: solid; border-width: 1px;"
                    onclick="">
                    <i class="fa-solid fa-trash"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Entry QR Code Modal -->
<div id="entryQRModal"
    style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); z-index: 1001; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-panel"
        style="width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; padding: 0; background: white; border-radius: 30px; position: relative; border: none;">
        <button onclick="closeEntryQR()"
            style="position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.1); border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; z-index: 10;">
            <i class="fa-solid fa-times"></i>
        </button>
        <div
            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 3rem 2rem; text-align: center; color: white; border-radius: 30px 30px 0 0;">
            <i class="fa-solid fa-shield-halved fa-2x" style="margin-bottom: 1rem; opacity: 0.9;"></i>
            <p
                style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.2rem; margin-bottom: 0.5rem; opacity: 0.9;">
                College Entry Pass</p>
            <h2 style="font-weight: 900; margin: 0; font-size: 1.8rem;">
                <?php echo htmlspecialchars($primaryPass['program_name'] ?? 'General Entry'); ?>
            </h2>
        </div>
        <div style="padding: 3rem 2rem; text-align: center;">
            <div id="entryQrContainer"
                style="display: inline-block; padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 2rem;">
            </div>
            <div style="margin-bottom: 2.5rem;">
                <h4 style="color: #0f172a; font-weight: 800; font-size: 1.4rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($userName); ?>
                </h4>
                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($userEmail); ?>
                </p>
                <p style="color: #64748b; font-size: 0.9rem;">
                    <i class="fa-regular fa-calendar"></i>
                    Registered: <?php echo date('d M Y', strtotime($primaryPass['start_date'] ?? date('Y-m-d'))); ?>
                </p>
            </div>
            <div
                style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 12px; padding: 1rem; margin-bottom: 2rem;">
                <p style="color: #92400e; font-size: 0.85rem; margin: 0; font-weight: 600;">
                    <i class="fa-solid fa-shield-halved"></i> Show this QR code at the college gate for entry
                    verification
                </p>
            </div>
            <button class="btn btn-primary"
                style="width: 100%; border-radius: 12px; font-weight: 700; padding: 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);"
                onclick="window.print()">
                <i class="fa-solid fa-download"></i> Download / Print
            </button>
        </div>
    </div>
</div>

</div>
</div>

<!-- Razorpay Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/qrcode.min.js"></script>
<script>
    // Initialize Entry QR Code (not displayed by default)
    const primaryToken = <?php echo json_encode($primaryPass['qr_token'] ?? ''); ?>;
    let entryQR = null;

    function showEntryQR() {
        const modal = document.getElementById('entryQRModal');
        const container = document.getElementById('entryQrContainer');

        // Generate QR if not already generated
        if (!entryQR && primaryToken) {
            entryQR = new QRCode(container, {
                text: primaryToken,
                width: 220,
                height: 220,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeEntryQR() {
        const modal = document.getElementById('entryQRModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close on backdrop click
    document.getElementById('entryQRModal').onclick = (e) => {
        if (e.target === document.getElementById('entryQRModal')) closeEntryQR();
    };

    // Modal Handling for Event Tickets
    const modal = document.getElementById('ticketModal');
    let activeQr = null;

    function showTicket(reg) {
        document.getElementById('modalEventName').innerText = reg.event_name;
        document.getElementById('modalDate').innerHTML = `<i class="fa-regular fa-calendar"></i> ${reg.event_date}`;
        document.getElementById('modalVenue').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${reg.venue}`;

        // Update cancel button
        const cancelBtn = document.getElementById('cancelBtn');
        cancelBtn.onclick = () => cancelRegistration(reg.id, reg.event_name);

        const modalQrContainer = document.getElementById('modalQr');
        modalQrContainer.innerHTML = '';
        activeQr = new QRCode(modalQrContainer, {
            text: reg.qr_token,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeTicket() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Event Registration Logic â€” Razorpay Checkout
    async function handleRegistration(event) {
        const eventId = typeof event === 'object' ? event.id : event;
        const isPaid = (typeof event === 'object' && event.is_paid == '1');
        const isGroup = (typeof event === 'object' && event.is_group_event == '1');

        let teamData = null;
        if (isGroup) {
            teamData = await collectTeamDetails(event);
            if (!teamData) return; // User cancelled
        }

        if (isPaid) {
            Swal.fire({
                title: 'Preparing Payment...',
                didOpen: () => Swal.showLoading(),
                background: '#0a0a0a',
                color: '#fff',
                allowOutsideClick: false
            });

            try {
                const res = await fetch('../api/payment_gateway.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'event', id: eventId })
                });
                const order = await res.json();

                if (!order.success) throw new Error(order.error || 'Could not create payment order');

                Swal.close();

                const options = {
                    key: order.key,
                    amount: order.amount,
                    currency: 'INR',
                    name: 'EntryX',
                    description: order.item_name,
                    order_id: order.order_id,
                    prefill: {
                        name: order.user_name,
                        email: order.user_email,
                        contact: order.user_contact
                    },
                    theme: { color: '#ff1f1f' },
                    handler: async function (response) {
                        Swal.fire({
                            title: 'Verifying Payment...',
                            didOpen: () => Swal.showLoading(),
                            background: '#0a0a0a',
                            color: '#fff',
                            allowOutsideClick: false
                        });

                        try {
                            const vRes = await fetch('../api/payment_verify.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_signature: response.razorpay_signature,
                                    team_name: teamData ? teamData.team_name : null,
                                    team_members: teamData ? teamData.team_members : null
                                })
                            });
                            const result = await vRes.json();

                            if (result.success) {
                                await Swal.fire({
                                    icon: 'success',
                                    title: 'Payment Successful!',
                                    text: 'You are now registered for the event.',
                                    confirmButtonColor: '#ff1f1f',
                                    background: '#0a0a0a',
                                    color: '#fff',
                                    timer: 3000,
                                    timerProgressBar: true
                                });
                                window.location.href = 'external_dashboard.php?registered=1';
                            } else {
                                throw new Error(result.error || 'Verification failed');
                            }
                        } catch (err) {
                            Swal.fire({ icon: 'error', title: 'Verification Error', text: err.message, confirmButtonColor: '#ff1f1f', background: '#0a0a0a', color: '#fff' });
                        }
                    },
                    modal: {
                        ondismiss: function () {
                            Swal.fire({ icon: 'info', title: 'Payment Cancelled', text: 'You can try again anytime.', confirmButtonColor: '#ff1f1f', background: '#0a0a0a', color: '#fff' });
                        }
                    }
                };

                const rzp = new Razorpay(options);
                rzp.open();

            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Payment Error', text: err.message, confirmButtonColor: '#ff1f1f', background: '#0a0a0a', color: '#fff' });
            }
            return;
        }

        proceedWithRegistration(eventId, null, teamData ? teamData.team_name : null, teamData ? teamData.team_members : null);
    }

    async function collectTeamDetails(event) {
        const min = parseInt(event.min_team_size) || 1;
        const max = parseInt(event.max_team_size) || 1;

        // Step 1: Ask for Member Count
        const { value: memberCount } = await Swal.fire({
            title: 'Team Size',
            text: `How many members are in your team? (Min: ${min}, Max: ${max})`,
            input: 'number',
            inputAttributes: {
                min: min,
                max: max,
                step: 1
            },
            inputValue: min,
            showCancelButton: true,
            confirmButtonText: 'Next',
            confirmButtonColor: '#ff1f1f',
            background: '#0a0a0a',
            color: '#fff',
            inputValidator: (value) => {
                if (!value || value < min || value > max) {
                    return `Please enter a count between ${min} and ${max}`;
                }
            }
        });

        if (!memberCount) return null;

        // Step 2: Collect Team Details
        let membersHtml = '';
        for (let i = 1; i <= memberCount; i++) {
            membersHtml += `
                <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
                    <div style="color: #ff1f1f; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.1em;">
                        ${i === 1 ? 'â­ Member 1 (Team Leader)' : `ðŸ‘¤ Member ${i}`}
                    </div>
                    <input id="swal-member-name-${i}" class="swal2-input" placeholder="Full Name *" 
                        style="margin: 0 0 0.6rem 0; width: 100%; height: 2.8rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;">
                    <div style="display: flex; gap: 0.5rem;">
                        <input id="swal-member-dept-${i}" class="swal2-input" placeholder="Department / College" 
                            style="margin: 0; flex: 1; height: 2.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px; font-size: 0.85rem;">
                        <input id="swal-member-year-${i}" class="swal2-input" placeholder="Year / Sem" 
                            style="margin: 0; width: 110px; height: 2.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px; font-size: 0.85rem;">
                    </div>
                </div>
            `;
        }

        const { value: teamData } = await Swal.fire({
            title: 'Team Details',
            html: `
                <div style="text-align: left; margin-top: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: #94a3b8; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Team Name *</label>
                    <input id="swal-team-name" class="swal2-input" placeholder="Enter Team Name" style="margin: 0 0 1.5rem 0; width: 100%; height: 3.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 10px;">
                    
                    <div style="margin-bottom: 1rem;">
                        <span style="color: #94a3b8; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Member Information</span>
                    </div>
                    ${membersHtml}
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Proceed to Payment',
            confirmButtonColor: '#ff1f1f',
            background: '#0a0a0a',
            color: '#fff',
            preConfirm: () => {
                const teamName = document.getElementById('swal-team-name').value.trim();
                if (!teamName) {
                    Swal.showValidationMessage('Please enter a team name');
                    return false;
                }
                const members = [];
                for (let i = 1; i <= memberCount; i++) {
                    const name = document.getElementById(`swal-member-name-${i}`).value.trim();
                    const dept = document.getElementById(`swal-member-dept-${i}`).value.trim();
                    const year = document.getElementById(`swal-member-year-${i}`).value.trim();
                    if (!name) {
                        Swal.showValidationMessage(`Please enter the full name for Member ${i}`);
                        return false;
                    }
                    let memberStr = name;
                    if (dept) memberStr += ` | ${dept}`;
                    if (year) memberStr += ` | ${year}`;
                    members.push(memberStr);
                }
                return { team_name: teamName, team_members: members };
            }
        });

        return teamData;
    }

    async function proceedWithRegistration(eventId, transactionId = null, teamName = null, teamMembers = null) {
        const confirmResult = await Swal.fire({
            title: 'Confirm Registration?',
            text: 'Would you like to register for this event?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ff1f1f',
            confirmButtonText: 'Confirm Registration',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirmResult.isConfirmed) {
            Swal.fire({
                title: 'Registering...',
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
                        transaction_id: transactionId,
                        team_name: teamName,
                        team_members: teamMembers
                    })
                });

                const result = await response.json();
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: result.payment_needed ? 'Registration submitted for verification.' : 'You have been registered successfully.',
                        confirmButtonColor: '#ff1f1f',
                        background: '#0a0a0a',
                        color: '#fff'
                    });
                    const redirectUrl = result.payment_needed ? 'external_dashboard.php?submitted=1' :
                        'external_dashboard.php?registered=1';
                    window.location.href = redirectUrl;
                } else {
                    throw new Error(result.error || 'Registration failed');
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

    async function cancelRegistration(regId, eventName) {
        const confirmResult = await Swal.fire({
            title: 'Cancel registration?',
            text: `Are you sure you want to remove your spot for ${eventName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, cancel it',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirmResult.isConfirmed) {
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
                        text: 'Registration removed.',
                        background: '#0a0a0a',
                        color: '#fff'
                    });
                    location.reload();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }
    }

    // Show success message if redirected after registration
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('registered') || urlParams.has('submitted')) {
            const isPending = urlParams.has('submitted');
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

            // Clear the URL parameters without refreshing the page
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    });

    // Close on backdrop click
    modal.onclick = (e) => { if (e.target === modal) closeTicket(); }
</script>



<?php require_once '../includes/footer.php'; ?>