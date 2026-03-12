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
    $winner = trim(htmlspecialchars($_POST['winner'] ?? ''));
    $runnerUp = trim(htmlspecialchars($_POST['runner_up'] ?? '')) ?: null;
    $consolation = trim(htmlspecialchars($_POST['consolation'] ?? '')) ?: null;
    $desc = trim(htmlspecialchars($_POST['description'] ?? ''));

    if (!$winner) {
        $error = "Please select a Winner.";
    } else {
        // Check if results already published for this event
        $checkStmt = $pdo->prepare("SELECT id FROM results WHERE event_id = ?");
        $checkStmt->execute([$eventId]);

        if ($checkStmt->fetch()) {
            $error = "Results for this event have already been published.";
        } else {
            $sql = "INSERT INTO results (event_id, winner_name, runner_up_name, consolation_prize, description, published_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute([$eventId, $winner, $runnerUp, $consolation, $desc, $_SESSION['user_id']]);
                $message = "Results have been published successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
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
        Back to Dashboard
    </a>

    <div class="result-card-premium reveal">
        <div class="hero-text">
            <h4
                style="color: var(--p-brand); text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.75rem; margin-bottom: 0.8rem; font-weight: 800;">
                Publish Results</h4>
            <h1>Select <span style="color: var(--p-brand);">Winners</span></h1>
            <p style="color: var(--p-text-dim); font-size: 1.2rem;">Enter winners for the selected event.</p>
        </div>

        <form method="POST" class="form-section">
            <div style="margin-bottom: 2.5rem;">
                <label>Select Event</label>
                <select name="event_id" id="eventSelect" required onchange="fetchCandidates(this.value)">
                    <option value="" disabled selected>Select an event...</option>
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
                    <label>🥇 Winner</label>
                    <select name="winner" id="winnerSelect" required>
                        <option value="" disabled selected>Select winner...</option>
                    </select>
                </div>
                <div>
                    <label>🥈 Runner Up <span
                            style="color: var(--p-text-muted); font-weight: 400; font-size: 0.75rem;">(Optional)</span></label>
                    <select name="runner_up" id="runnerUpSelect">
                        <option value="">-- Skip Runner Up --</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 2.5rem;">
                <label>🥉 Consolation <span
                        style="color: var(--p-text-muted); font-weight: 400; font-size: 0.75rem;">(Optional)</span></label>
                <select name="consolation" id="consolationSelect">
                    <option value="">-- No Consolation --</option>
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Brief announcement..."></textarea>
            </div>

            <button type="submit" class="btn-publish">
                <i class="fa-solid fa-bullhorn"></i>
                Publish Result
            </button>
        </form>
    </div>

    <!-- History Section -->
    <div class="result-card-premium reveal" style="margin-top: 3rem; animation-delay: 0.2s;">
        <div class="hero-text" style="margin-bottom: 2rem;">
            <h4
                style="color: var(--p-brand); text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.75rem; margin-bottom: 0.8rem; font-weight: 800;">
                Broadcast History</h4>
            <h2 style="color: white; font-size: 2rem;">Published <span style="color: var(--p-brand);">Records</span>
            </h2>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 1rem;">
                <thead>
                    <tr
                        style="text-align: left; color: var(--p-text-muted); font-size: 0.8rem; text-transform: uppercase;">
                        <th>Event</th>
                        <th>Winner</th>
                        <th>Published Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="resultsTableBody">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
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

    // Load History
    async function loadHistory() {
        try {
            const res = await fetch('../api/results.php?action=list');
            const data = await res.json();
            const tbody = document.getElementById('resultsTableBody');

            if (data.results && data.results.length > 0) {
                tbody.innerHTML = data.results.map(r => `
                    <tr style="background: rgba(255,255,255,0.02);">
                        <td style="padding: 1.2rem; border-radius: 12px 0 0 12px; color: white; font-weight: 600;">
                            ${r.event_title}
                        </td>
                        <td style="padding: 1.2rem; color: #eab308;">
                            <i class="fa-solid fa-crown"></i> ${r.winner_name}
                        </td>
                        <td style="padding: 1.2rem; color: var(--p-text-dim); font-size: 0.9rem;">
                            ${new Date(r.published_at).toLocaleDateString()}
                        </td>
                        <td style="padding: 1.2rem; text-align: right; border-radius: 0 12px 12px 0;">
                            <button onclick="deleteResult(${r.id})" 
                                style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2rem; color: var(--p-text-muted);">No published results found.</td></tr>';
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function deleteResult(id) {
        const confirm = await Swal.fire({
            title: 'Revoke Victory?',
            text: "This will remove the result from the public Hall of Fame.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            background: '#0a0a0a',
            color: '#fff',
            confirmButtonText: 'Yes, Revoke'
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch(`../api/results.php?action=delete&id=${id}`);
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Revoked',
                        background: '#0a0a0a',
                        color: '#fff'
                    });
                    loadHistory();
                } else {
                    throw new Error(data.error);
                }
            } catch (e) {
                Swal.fire('Error', e.message, 'error');
            }
        }
    }

    async function fetchCandidates(eventId) {
        if (!eventId) return;

        const winnerSelect = document.getElementById('winnerSelect');
        const runnerUpSelect = document.getElementById('runnerUpSelect');
        const consolationSelect = document.getElementById('consolationSelect');

        const loadingOption = '<option value="" disabled selected>Loading candidates...</option>';
        winnerSelect.innerHTML = loadingOption;
        runnerUpSelect.innerHTML = loadingOption;
        consolationSelect.innerHTML = loadingOption;

        try {
            const res = await fetch(`../api/results.php?action=get_candidates&event_id=${eventId}`);
            const data = await res.json();

            if (data.success && data.candidates) {
                const options = data.candidates.map(c => `<option value="${c.name}">${c.name} (${c.email})</option>`).join('');

                const winnerDefault = '<option value="" disabled selected>Select winner...</option>';
                const skipDefault = '<option value="">-- Skip / None --</option>';

                winnerSelect.innerHTML = winnerDefault + options;
                runnerUpSelect.innerHTML = skipDefault + options;
                consolationSelect.innerHTML = skipDefault + options;
            } else {
                const noneOption = '<option value="" disabled selected>No candidates found</option>';
                const skipDefault = '<option value="">-- No Candidates --</option>';
                winnerSelect.innerHTML = noneOption;
                runnerUpSelect.innerHTML = skipDefault;
                consolationSelect.innerHTML = skipDefault;
            }
        } catch (e) {
            console.error('Error fetching candidates:', e);
            winnerSelect.innerHTML = '<option value="" disabled selected>Error loading candidates</option>';
            runnerUpSelect.innerHTML = '<option value="" disabled selected>Error loading candidates</option>';
        }
    }

    loadHistory();

    // Professional Logout logic (keeping consistency)
    async function confirmLogout() {
        // ... (same as dashboard)
    }
</script>

<?php require_once '../includes/footer.php'; ?>