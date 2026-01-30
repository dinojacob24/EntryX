<?php
// Session and Authentication Checks MUST come before any includes
session_start();

// Access Control - Admins and Coordinators
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'event_admin'])) {
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
$events = $eventObj->getAllEvents();
?>

<style>
    .dashboard-container {
        padding: 2rem 0;
        position: relative;
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
    .hub-hero-card {
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(10, 10, 10, 0.8) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(79, 70, 229, 0.15);
        border-radius: 32px;
        padding: 4rem;
        margin-bottom: 4rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }

    .hero-text h1 {
        font-size: 3.5rem;
        font-weight: 900;
        letter-spacing: -0.05em;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Event Operational Cards */
    .op-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 2.5rem;
    }

    .op-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--p-border);
        border-radius: 28px;
        padding: 2.5rem;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
    }

    .op-card:hover {
        transform: translateY(-10px);
        border-color: rgba(79, 70, 229, 0.3);
        background: rgba(255, 255, 255, 0.04);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    }

    .stat-mini-box {
        background: rgba(0, 0, 0, 0.3);
        padding: 1.5rem;
        border-radius: 20px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Custom Badges */
    .role-badge-hub {
        padding: 0.3rem 1rem;
        background: rgba(79, 70, 229, 0.2);
        border-radius: 999px;
        color: #818cf8;
        font-size: 0.7rem;
        font-weight: 900;
        text-transform: uppercase;
    }
</style>

<div class="dashboard-container">
    <!-- Top Bar -->
    <div class="dashboard-top-bar reveal">
        <div class="user-info-badge">
            <i class="fa-solid fa-headset" style="color: #6366f1;"></i>
            <span><?php echo htmlspecialchars($userName); ?></span>
            <span class="role-badge-hub">COORDINATOR</span>
        </div>
        <button class="logout-btn-premium" onclick="confirmLogout()">
            <i class="fa-solid fa-power-off"></i>
            <span>Offline</span>
        </button>
    </div>

    <!-- Hero Section -->
    <div class="hub-hero-card reveal">
        <div class="hero-text">
            <h1>Operational <span style="color: #6366f1;">Hub</span></h1>
            <p style="color: var(--p-text-dim); font-size: 1.25rem;">Real-time management for live event nodes.</p>
        </div>
    </div>

    <!-- Operations Grid -->
    <div class="op-grid">
        <?php foreach ($events as $idx => $event):
            $stats = $eventObj->getEventStats($event['id']);
            ?>
            <div class="op-card reveal" style="animation-delay: <?php echo $idx * 0.1; ?>s;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
                    <div>
                        <h2 style="font-size: 1.6rem; color: white; margin-bottom: 0.3rem;">
                            <?php echo htmlspecialchars($event['name']); ?>
                        </h2>
                        <div
                            style="display: flex; align-items: center; gap: 0.6rem; color: var(--p-text-dim); font-size: 0.9rem;">
                            <i class="fa-regular fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                        </div>
                    </div>
                    <span class="status-badge status-inside">LIVE</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem;">
                    <div class="stat-mini-box">
                        <div style="font-size: 2.2rem; font-weight: 900; color: white;">
                            <?php echo $stats['registrations']; ?></div>
                        <div style="font-size: 0.75rem; color: var(--p-text-muted); text-transform: uppercase;">Registered
                        </div>
                    </div>
                    <div class="stat-mini-box" style="border-color: rgba(16, 185, 129, 0.2);">
                        <div style="font-size: 2.2rem; font-weight: 900; color: #10b981;"><?php echo $stats['inside']; ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--p-text-muted); text-transform: uppercase;">Inside</div>
                    </div>
                </div>

                <div style="display: flex; gap: 1.25rem;">
                    <a href="scanner.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary"
                        style="flex: 2; padding: 1.1rem; border-radius: 16px; font-weight: 800;">
                        <i class="fa-solid fa-qrcode"></i> LENS SCANNER
                    </a>
                    <a href="event_list.php?event_id=<?php echo $event['id']; ?>" class="btn btn-outline"
                        style="flex: 1; border-radius: 16px; font-size: 0.85rem; font-weight: 700;">
                        LIST
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    async function confirmLogout() {
        const confirmResult = await Swal.fire({
            title: 'Go Offline?',
            text: 'Are you sure you want to exit the coordinator hub?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Sign Out',
            background: '#0a0a0a',
            color: '#fff'
        });

        if (confirmResult.isConfirmed) {
            window.location.href = '/Project/EntryX/api/auth.php?action=logout';
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>