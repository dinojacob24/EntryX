<?php require_once 'includes/header.php'; ?>

<div
    style="min-height: 80vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">

    <div style="margin-bottom: 3rem;">
        <h1
            style="font-size: 3.5rem; margin-bottom: 1rem; background: linear-gradient(to right, #4f46e5, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            ENTRY X
        </h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">Event Management & Results System</p>
    </div>

    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; width: 100%; max-width: 900px;">

        <!-- Admin/Staff Login -->
        <a href="pages/login.php" class="glass-panel"
            style="padding: 2.5rem; text-decoration: none; color: white; transition: transform 0.2s;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
            <h2 style="margin-bottom: 0.5rem; color: var(--primary);">Staff Login</h2>
            <p style="color: var(--text-muted);">Admin & Security Access</p>
        </a>

        <!-- Public Results -->
        <a href="pages/results.php" class="glass-panel"
            style="padding: 2.5rem; text-decoration: none; color: white; transition: transform 0.2s;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🏆</div>
            <h2 style="margin-bottom: 0.5rem; color: var(--secondary);">View Results</h2>
            <p style="color: var(--text-muted);">Event Winners & Standings</p>
        </a>

        <!-- Student Portal (Optional) -->
        <a href="pages/login.php" class="glass-panel"
            style="padding: 2.5rem; text-decoration: none; color: white; transition: transform 0.2s;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎓</div>
            <h2 style="margin-bottom: 0.5rem; color: #4ade80;">Student Login</h2>
            <p style="color: var(--text-muted);">View My History</p>
        </a>

    </div>
</div>

<style>
    .glass-panel:hover {
        transform: translateY(-5px);
        background: rgba(30, 41, 59, 0.9);
    }
</style>

<?php require_once 'includes/footer.php'; ?>