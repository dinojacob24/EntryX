<?php
// Session and Authentication Checks MUST come before any includes
session_start();

// Access Control - Admins Only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'event_admin'])) {
    header('Location: admin_login.php');
    exit;
}

require_once '../config/db_connect.php';
require_once '../classes/Event.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$userRole = $_SESSION['role'];

$eventObj = new Event($pdo);
$events = $eventObj->getAllEvents(true);

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = $_POST['event_id'];
    $winner = trim(htmlspecialchars($_POST['winner']));
    $runnerUp = trim(htmlspecialchars($_POST['runner_up']));
    $consolation = trim(htmlspecialchars($_POST['consolation']));
    $desc = trim(htmlspecialchars($_POST['description']));

    // Check if results already published for this event
    $checkStmt = $pdo->prepare("SELECT id FROM results WHERE event_id = ?");
    $checkStmt->execute([$eventId]);

    if ($checkStmt->fetch()) {
        $error = "Results for this node have already been finalized and broadcasted.";
    } else {
        $sql = "INSERT INTO results (event_id, winner_name, runner_up_name, consolation_prize, description, published_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([$eventId, $winner, $runnerUp, $consolation, $desc, $_SESSION['user_id']]);
            $message = "Operational Victory! Results have been broadcasted to all terminals.";
        } catch (PDOException $e) {
            $error = "System Failure: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<style>
    .publish-container {
        padding: 4rem 0;
        max-width: 800px;
        margin: 0 auto;
    }

    .result-card-premium {
        background: linear-gradient(135deg, rgba(255, 31, 31, 0.05) 0%, rgba(10, 10, 10, 0.8) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 31, 31, 0.15);
        border-radius: 32px;
        padding: 4rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }

    .result-card-premium::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(255, 31, 31, 0.08), transparent 60%);
        filter: blur(60px);
    }

    .hero-text h1 {
        font-size: 3rem;
        font-weight: 900;
        letter-spacing: -0.05em;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .form-section {
        margin-top: 3rem;
        background: rgba(255, 255, 255, 0.02);
        padding: 2.5rem;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .input-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    label {
        display: block;
        color: var(--p-text-muted);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        margin-bottom: 0.8rem;
        font-weight: 700;
    }

    select,
    input,
    textarea {
        width: 100%;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 14px;
        padding: 1.2rem;
        color: white;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    select:focus,
    input:focus,
    textarea:focus {
        border-color: var(--p-brand);
        background: rgba(255, 31, 31, 0.05);
        outline: none;
        box-shadow: 0 0 20px rgba(255, 31, 31, 0.1);
    }

    .btn-publish {
        background: var(--grad-crimson);
        color: white;
        border: none;
        padding: 1.5rem;
        border-radius: 16px;
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        cursor: pointer;
        width: 100%;
        margin-top: 2rem;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }

    .btn-publish:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px var(--p-brand-glow);
    }

    .back-nav {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--p-text-dim);
        text-decoration: none;
        margin-bottom: 2rem;
        font-weight: 600;
        transition: color 0.3s;
    }

    .back-nav:hover {
        color: white;
    }

    /* Professional Top Bar */
    .dashboard-top-bar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
</style>

<div class="publish-container">
    <!-- Top Bar -->
    <div class="dashboard-top-bar reveal">
        <div class="user-info-badge">
            <i class="fa-solid fa-trophy" style="color: #eab308;"></i>
            <span><?php echo htmlspecialchars($userName); ?></span>
            <span class="role-badge"
                style="background: rgba(234, 179, 8, 0.1); color: #eab308; border-radius: 999px; padding: 0.2rem 0.8rem; font-size: 0.7rem; font-weight: 800;">RECOGNITION
                UNIT</span>
        </div>
    </div>

    <a href="admin_dashboard.php" class="back-nav reveal">
        <i class="fa-solid fa-arrow-left"></i>
        Return to Terminal Alpha
    </a>

    <div class="result-card-premium reveal">
        <div class="hero-text">
            <h4
                style="color: var(--p-brand); text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.75rem; margin-bottom: 0.8rem; font-weight: 800;">
                Recognition Protocol</h4>
            <h1>Finalize <span style="color: var(--p-brand);">Victory</span></h1>
            <p style="color: var(--p-text-dim); font-size: 1.2rem;">Record and broadcast official event outcomes to the
                entire campus network.</p>
        </div>

        <form method="POST" class="form-section">
            <div style="margin-bottom: 2.5rem;">
                <label>Operational Node (Event)</label>
                <select name="event_id" required>
                    <option value="" disabled selected>Select a target node...</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['id']; ?>">
                            <?php echo htmlspecialchars($event['name']); ?> —
                            <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-grid">
                <div>
                    <label>🥇 Primary Victor (Winner)</label>
                    <input type="text" name="winner" required placeholder="Name or Team Handle">
                </div>
                <div>
                    <label>🥈 Second Tier (Runner Up)</label>
                    <input type="text" name="runner_up" required placeholder="Name or Team Handle">
                </div>
            </div>

            <div style="margin-bottom: 2.5rem;">
                <label>🥉 Special Recognition (Consolation)</label>
                <input type="text" name="consolation" placeholder="Optional handle...">
            </div>

            <div style="margin-bottom: 1rem;">
                <label>Victory Transcript (Description)</label>
                <textarea name="description" rows="4" placeholder="Official announcement text..."></textarea>
            </div>

            <button type="submit" class="btn-publish">
                <i class="fa-solid fa-bullhorn"></i>
                Broadcast Recognition
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if ($message): ?>
        Swal.fire({
            icon: 'success',
            title: 'Victory Broadcasted!',
            text: '<?php echo $message; ?>',
            background: '#0a0a0a',
            color: '#fff',
            confirmButtonColor: '#ff1f1f'
        }).then(() => {
            window.location.href = 'admin_dashboard.php';
        });
    <?php endif; ?>

    <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Transmission Blocked',
            text: '<?php echo $error; ?>',
            background: '#0a0a0a',
            color: '#fff',
            confirmButtonColor: '#ff1f1f'
        });
    <?php endif; ?>

    // Professional Logout logic (keeping consistency)
    async function confirmLogout() {
        // ... (same as dashboard)
    }
</script>

<?php require_once '../includes/footer.php'; ?>