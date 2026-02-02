<?php
// Session and Authentication Checks MUST come before any includes
session_start();

// Access Control - Staff Only
if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

// Redirect non-staff roles to their respective dashboards
if ($_SESSION['role'] !== 'staff') {
    if (in_array($_SESSION['role'], ['super_admin', 'event_admin', 'security'])) {
        header('Location: admin_dashboard.php');
    } elseif ($_SESSION['role'] === 'external') {
        header('Location: external_dashboard.php');
    } else {
        header('Location: student_dashboard.php');
    }
    exit;
}

// include files
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

<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Top Section -->
        <div class="welcome-section" style="padding-top: 4rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem;">
                <div>
                    <h5
                        style="color: var(--p-brand); font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 0.8rem; font-size: 0.9rem;">
                        <i class="fa-solid fa-user-tie" style="margin-right: 0.6rem;"></i> Staff Portal
                    </h5>
                    <h1 style="color: white; font-size: 3rem; font-weight: 800; letter-spacing: -0.03em;">
                        Welcome back, <span
                            style="background: linear-gradient(to right, white, #ff1f1f); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            <?php echo htmlspecialchars($userName); ?>
                        </span>
                    </h1>
                    <p style="color: var(--p-text-muted); font-size: 1.1rem; margin-top: 0.5rem;">Explore events and
                        participate in the college experience.</p>
                </div>
            </div>

            <!-- Dashboard Stats Grid -->
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 4rem;">
                <!-- Stat Card 1 -->
                <div class="glass-panel" style="padding: 2.5rem; text-align: left; border-left: 4px solid #3b82f6;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div
                            style="width: 50px; height: 50px; background: rgba(59, 130, 246, 0.1); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #3b82f6;">
                            <i class="fa-solid fa-calendar-check fa-lg"></i>
                        </div>
                        <div
                            style="color: var(--p-text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                            Registered</div>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 800; color: white;">
                        <?php echo $totalEventsJoined; ?>
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div class="glass-panel"
                    style="padding: 2.5rem; text-align: left; border-left: 4px solid var(--p-brand);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div
                            style="width: 50px; height: 50px; background: rgba(255, 31, 31, 0.1); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--p-brand);">
                            <i class="fa-solid fa-rocket fa-lg"></i>
                        </div>
                        <div
                            style="color: var(--p-text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                            Available</div>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 800; color: white;">
                        <?php echo $upcomingEventsCount; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Section -->
        <div>
            <h2
                style="color: white; font-size: 1.8rem; font-weight: 800; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                Available Events <span
                    style="font-size: 0.7rem; padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.05); border-radius: 20px; font-weight: 600; vertical-align: middle;">
                    <?php echo count($availableEvents); ?>
                </span>
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem;">
                <?php if (empty($availableEvents)): ?>
                    <p style="color: var(--p-text-dim);">No new events available at the moment.</p>
                <?php else: ?>
                    <?php foreach ($availableEvents as $event): ?>
                        <div class="glass-panel"
                            style="padding: 0; overflow: hidden; display: flex; flex-direction: column; transition: 0.4s;">
                            <!-- Event Image Mockup -->
                            <div
                                style="height: 180px; background: linear-gradient(135deg, rgba(255,31,31,0.1) 0%, rgba(10,10,10,1) 100%); position: relative; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-masks-theater fa-4x" style="color: rgba(255,255,255,0.1);"></i>
                                <div
                                    style="position: absolute; top: 1rem; right: 1rem; padding: 0.5rem 1rem; background: rgba(0,0,0,0.6); border-radius: 10px; font-size: 0.75rem; color: white; font-weight: 600; backdrop-filter: blur(5px);">
                                    <?php echo htmlspecialchars($event['type']); ?>
                                </div>
                            </div>
                            <div style="padding: 2rem;">
                                <h4 style="color: white; font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($event['name']); ?>
                                </h4>
                                <p
                                    style="color: var(--p-text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 2rem;">
                                    <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?>
                                </p>
                                <div style="display: flex; gap: 1rem; margin-top: auto;">
                                    <button
                                        onclick="registerEvent(<?php echo $event['id']; ?>, '<?php echo addslashes($event['name']); ?>')"
                                        class="btn btn-primary"
                                        style="flex: 1; padding: 0.8rem; font-size: 0.9rem; font-weight: 700; border-radius: 12px;">
                                        Register
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Registrations Section -->
        <div style="margin-top: 5rem;">
            <h2 style="color: white; font-size: 1.8rem; font-weight: 800; margin-bottom: 2rem;">My Participations</h2>
            <div class="glass-panel" style="padding: 0; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.02); text-align: left;">
                            <th
                                style="padding: 1.5rem 2rem; color: var(--p-text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em;">
                                Event</th>
                            <th
                                style="padding: 1.5rem 2rem; color: var(--p-text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em;">
                                Status</th>
                            <th
                                style="padding: 1.5rem 2rem; color: var(--p-text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em;">
                                Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myRegs)): ?>
                            <tr>
                                <td colspan="3" style="padding: 3rem; text-align: center; color: var(--p-text-dim);">No
                                    active registrations. Join an event above!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($myRegs as $reg): ?>
                                <tr style="border-top: 1px solid var(--p-border);">
                                    <td style="padding: 1.5rem 2rem; color: white; font-weight: 600;">
                                        <?php echo htmlspecialchars($reg['event_name']); ?>
                                    </td>
                                    <td style="padding: 1.5rem 2rem;">
                                        <span
                                            style="padding: 0.4rem 0.8rem; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                            Confirmed
                                        </span>
                                    </td>
                                    <td style="padding: 1.5rem 2rem; color: var(--p-text-dim); font-size: 0.85rem;">
                                        <?php echo date('M d, Y', strtotime($reg['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    async function registerEvent(eventId, eventName) {
        const result = await Swal.fire({
            title: 'Join Event?',
            text: `Do you want to participate in ${eventName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ff1f1f',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Register'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('../api/events.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event_id: eventId })
                });

                const data = await response.json();
                if (data.success) {
                    Swal.fire('Success!', 'Registration confirmed.', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error || 'Failed to register', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Connection error', 'error');
            }
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>