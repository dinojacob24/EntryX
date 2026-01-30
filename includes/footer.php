<?php
// If in custom layout mode (like security terminal), close nothing and return nothing (just body/html end)
if (isset($useCustomLayout) && $useCustomLayout === true) {
    echo '</body></html>';
    return;
} else {
    echo '</div></div>'; // Close container and wrapper
}

$hideExpandedFooter = isset($hideFooter) && $hideFooter === true;
// Also auto-hide for admin-only pages
$currentPage = $_SERVER['SCRIPT_NAME'];
if (
    strpos($currentPage, 'admin_dashboard.php') !== false ||
    strpos($currentPage, 'coordinator_dashboard.php') !== false ||
    strpos($currentPage, 'publish_result.php') !== false ||
    strpos($currentPage, 'student_dashboard.php') !== false
) {
    $hideExpandedFooter = true;
}
?>

<footer
    style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.05); background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(10px);">
    <div class="container" style="padding: <?php echo $hideExpandedFooter ? '1.5rem 2rem' : '3rem 2rem'; ?>;">
        <?php if (!$hideExpandedFooter): ?>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 3rem; margin-bottom: 3rem;">
                <div>
                    <h3
                        style="margin-bottom: 1rem; background: var(--gradient-main); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        ENTRY X</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        The ultimate platform for college event management, combining secure QR entry, real-time analytics,
                        and seamless registrations.
                    </p>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem;">Quick Links</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem;">
                        <li><a href="/Project/EntryX/" style="color: var(--text-muted); font-size: 0.9rem;">Home</a></li>
                        <li><a href="/Project/EntryX/pages/results.php"
                                style="color: var(--text-muted); font-size: 0.9rem;">Results</a></li>
                        <li><a href="/Project/EntryX/pages/user_login.php"
                                style="color: var(--text-muted); font-size: 0.9rem;">Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem; color: white;">Contact Support</h4>
                    <div
                        style="color: var(--text-muted); font-size: 0.9rem; display: flex; flex-direction: column; gap: 0.8rem;">
                        <a href="mailto:support@entryx.edu"
                            style="color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem; transition: color 0.3s;">
                            <i class="fa-solid fa-envelope" style="color: var(--primary);"></i> support@entryx.edu
                        </a>
                        <a href="tel:+919778720724"
                            style="color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem; transition: color 0.3s;">
                            <i class="fa-solid fa-phone" style="color: var(--primary);"></i> +91 97787 20724
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div
            style="text-align: center; <?php echo !$hideExpandedFooter ? 'padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05);' : ''; ?> color: var(--text-muted); font-size: 0.85rem;">
            &copy; <?php echo date('Y'); ?> EntryX Event Systems. Built for MCA Mini Project.
        </div>
    </div>
</footer>
</body>

</html>