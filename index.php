<?php
require_once 'includes/header.php';
require_once 'config/db_connect.php';

// Check if external registration is enabled and which program is active
$externalRegEnabled = false;
$activeProgramId = null;
$externalProgramName = 'New Registration';
$externalProgramDesc = 'Click to register for the upcoming campus event.';

try {
    // 1. Check global system setting
    $stmtSettings = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'external_registration_enabled'");
    $globalEnabled = $stmtSettings->fetchColumn();

    if ($globalEnabled === '1') {
        // 2. Fetch the currently active program from external_programs table
        $stmtProgram = $pdo->prepare("SELECT id, program_name, program_description FROM external_programs WHERE is_active = 1 LIMIT 1");
        $stmtProgram->execute();
        $activeProgram = $stmtProgram->fetch(PDO::FETCH_ASSOC);

        if ($activeProgram) {
            $externalRegEnabled = true;
            $activeProgramId = $activeProgram['id'];
            $externalProgramName = $activeProgram['program_name'];
            $externalProgramDesc = $activeProgram['program_description'] ?: $externalProgramDesc;
        }
    }
} catch (Exception $e) {
    // Fallback to legacy/default settings if table structure is older or error occurs
    $externalRegEnabled = false;
}
?>

<!-- High-Impact Hero -->
<section
    style="position: relative; min-height: 85vh; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 2rem 0;">

    <!-- Dynamic Glow Effects -->
    <div
        style="position: absolute; top: 10%; left: 5%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255,31,31,0.08) 0%, transparent 70%); filter: blur(80px); animation: pulse 8s infinite alternate;">
    </div>
    <div
        style="position: absolute; bottom: 10%; right: 5%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255,31,31,0.05) 0%, transparent 70%); filter: blur(100px); animation: pulse 12s infinite alternate-reverse;">
    </div>

    <div style="position: relative; z-index: 10; text-align: center;" class="reveal">
        <div
            style="display: inline-flex; align-items: center; gap: 0.8rem; padding: 0.6rem 1.4rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 999px; margin-bottom: 2.5rem;">
            <div
                style="width: 8px; height: 8px; background: var(--p-brand); border-radius: 50%; box-shadow: 0 0 12px var(--p-brand);">
            </div>
            <span
                style="font-size: 0.7rem; font-weight: 800; letter-spacing: 0.2em; color: var(--p-text-dim); text-transform: uppercase;">Next-Gen
                Campus Ecosystem</span>
        </div>

        <h1
            style="font-size: clamp(3.2rem, 10vw, 6rem); line-height: 0.9; margin-bottom: 2rem; background: linear-gradient(to bottom, #ffffff 40%, #94a3b8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; max-width: 1000px; margin-left: auto; margin-right: auto;">
            Engineering Elite<br>Event Experiences.
        </h1>

        <p
            style="max-width: 650px; margin: 0 auto 3.5rem; color: var(--p-text-dim); font-size: 1.25rem; font-weight: 500; line-height: 1.6;">
            The premier platform for university management. Smart registrations, biometric-grade QR security, and
            real-time elite analytics.
        </p>

        <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; margin-bottom: 6rem;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                $dest = 'pages/dashboard.php';
                if (isset($_SESSION['role']) && ($_SESSION['role'] === 'internal' || $_SESSION['role'] === 'external')) {
                    $dest = 'pages/student_dashboard.php';
                } elseif (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'event_admin')) {
                    $dest = 'pages/admin_dashboard.php';
                }
                ?>
                <a href="<?php echo $dest; ?>" class="btn btn-primary" style="padding: 1.2rem 3rem; font-size: 1.1rem;">
                    Enter Control Center <i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i>
                </a>
            <?php else: ?>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                    <a href="pages/user_login.php" class="btn btn-primary" style="padding: 1.2rem 3rem; font-size: 1.1rem;">
                        <i class="fa-solid fa-right-to-bracket"></i> Access Portal
                    </a>

                    <?php if ($externalRegEnabled): ?>
                        <a href="pages/register.php" class="btn btn-outline" style="padding: 1.2rem 3rem; font-size: 1.1rem;"
                            title="<?php echo htmlspecialchars($externalProgramDesc); ?>">
                            <i class="fa-solid fa-user-plus"></i> <?php echo htmlspecialchars($externalProgramName); ?>
                        </a>
                    <?php endif; ?>

                    <a href="pages/sub_admin_login.php" class="btn"
                        style="padding: 1.2rem 3rem; font-size: 1.1rem; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2);">
                        <i class="fa-solid fa-camera"></i> Security Terminal
                    </a>
                    <a href="pages/admin_login.php" class="btn"
                        style="padding: 1.2rem 3rem; font-size: 1.1rem; background: rgba(255, 31, 31, 0.1); color: #ef4444; border: 1px solid rgba(255, 31, 31, 0.2);">
                        <i class="fa-solid fa-shield-halved"></i> Admin Console
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tiered Features -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            <div class="glass-panel" style="padding: 3rem; text-align: left;">
                <div
                    style="width: 60px; height: 60px; background: rgba(255,31,31,0.1); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: var(--p-brand); margin-bottom: 2rem;">
                    <i class="fa-solid fa-qrcode fa-2xl"></i>
                </div>
                <h3 style="margin-bottom: 1rem; font-size: 1.5rem;">Smart Verification</h3>
                <p style="color: var(--p-text-muted); line-height: 1.7;">Military-grade QR encryption ensures
                    zero-latency entrance control and maximum security protocols.</p>
            </div>

            <div class="glass-panel" style="padding: 3rem; text-align: left;">
                <div
                    style="width: 60px; height: 60px; background: rgba(255,255,255,0.03); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: white; margin-bottom: 2rem;">
                    <i class="fa-solid fa-chart-pie fa-2xl"></i>
                </div>
                <h3 style="margin-bottom: 1rem; font-size: 1.5rem;">Live Intelligence</h3>
                <p style="color: var(--p-text-muted); line-height: 1.7;">Monitor campus density and registration metrics
                    in real-time with our proprietary analytics engine.</p>
            </div>

            <div class="glass-panel" style="padding: 3rem; text-align: left;">
                <div
                    style="width: 60px; height: 60px; background: var(--grad-crimson); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: white; margin-bottom: 2rem;">
                    <i class="fa-solid fa-trophy fa-2xl"></i>
                </div>
                <h3 style="margin-bottom: 1rem; font-size: 1.5rem;">Instant Recognition</h3>
                <p style="color: var(--p-text-muted); line-height: 1.7;">Winner results and achievements are broadcasted
                    site-wide the millisecond they are finalized.</p>
            </div>
        </div>
    </div>
</section>

<style>
    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 0.4;
        }

        100% {
            transform: scale(1.15);
            opacity: 0.7;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>