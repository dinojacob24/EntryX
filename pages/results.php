<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

$search = $_GET['search'] ?? '';

$sql = "SELECT r.*, e.name as event_name, e.event_date 
        FROM results r 
        JOIN events e ON r.event_id = e.id 
        WHERE e.name LIKE ? 
        ORDER BY r.published_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%"]);
$results = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <div style="text-align: center; margin-bottom: 5rem;" class="reveal">
        <h1
            style="font-size: 4rem; margin-bottom: 1rem; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Hall of <span style="color: var(--p-brand);">Fame</span>
        </h1>
        <p style="color: var(--p-text-muted); font-size: 1.2rem;">Celebrating Elite Achievements & Campus Legends</p>

        <form style="max-width: 500px; margin: 3rem auto 0; display: flex; gap: 1rem;">
            <div style="flex: 1; position: relative;">
                <input type="text" name="search" placeholder="Search event records..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="width: 100%; padding: 1rem 1.5rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 14px; color: white;">
            </div>
            <button class="btn btn-primary" style="padding: 0 2rem;">Search</button>
        </form>
    </div>

    <?php if (empty($results)): ?>
        <div class="glass-panel reveal" style="padding: 5rem; text-align: center;">
            <i class="fa-solid fa-hourglass-empty"
                style="font-size: 3rem; color: var(--p-text-muted); margin-bottom: 2rem;"></i>
            <p style="color: var(--p-text-dim); font-size: 1.1rem;">No historical records found for this query.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 3rem;">
            <?php foreach ($results as $idx => $res): ?>
                <div class="glass-panel reveal"
                    style="padding: 0; overflow: hidden; position: relative; animation-delay: <?php echo $idx * 0.1; ?>s;">
                    <div
                        style="position: absolute; top: 1.5rem; right: 1.5rem; background: var(--grad-crimson); color: white; padding: 0.4rem 1.2rem; font-weight: 800; border-radius: 99px; font-size: 0.75rem; letter-spacing: 0.1em; box-shadow: 0 5px 15px rgba(255,31,31,0.3);">
                        GOLD RECORD
                    </div>

                    <div
                        style="padding: 3.5rem 2rem; text-align: center; background: radial-gradient(circle at top, rgba(234,179,8,0.05), transparent 70%);">
                        <div
                            style="width: 80px; height: 80px; background: rgba(234,179,8,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; color: #eab308; box-shadow: 0 0 30px rgba(234,179,8,0.2);">
                            <i class="fa-solid fa-trophy fa-2xl"></i>
                        </div>
                        <h2 style="font-size: 2rem; margin-bottom: 0.5rem; color: white; font-weight: 800;">
                            <?php echo htmlspecialchars($res['winner_name']); ?>
                        </h2>
                        <p
                            style="color: #eab308; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; font-size: 0.85rem;">
                            Grand Champion</p>
                    </div>

                    <div
                        style="padding: 2.5rem; background: rgba(255,255,255,0.01); border-top: 1px solid rgba(255,255,255,0.03);">
                        <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span
                                    style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">Event</span>
                                <span
                                    style="color: white; font-weight: 600;"><?php echo htmlspecialchars($res['event_name']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span
                                    style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">Runner
                                    Up</span>
                                <span
                                    style="color: var(--p-text-dim);"><?php echo htmlspecialchars($res['runner_up_name']); ?></span>
                            </div>
                            <?php if ($res['consolation_prize']): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span
                                        style="color: var(--p-text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">Special
                                        Mention</span>
                                    <span
                                        style="color: var(--p-text-dim);"><?php echo htmlspecialchars($res['consolation_prize']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($res['description']): ?>
                            <div
                                style="margin-top: 2rem; padding: 1.5rem; background: rgba(0,0,0,0.2); border-radius: 16px; border: 1px solid rgba(255,255,255,0.02); font-size: 0.95rem; color: var(--p-text-dim); font-style: italic; line-height: 1.6;">
                                "<?php echo htmlspecialchars($res['description']); ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>