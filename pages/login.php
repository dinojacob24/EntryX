<?php
require_once '../includes/header.php';

// Force clear session on visiting login page
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    session_start(); // Restart for flash messages if needed
}
?>

<div style="max-width: 400px; margin: 4rem auto;" class="glass-panel">
    <div style="padding: 2rem;">
        <h2 style="text-align: center; margin-bottom: 2rem;">Login</h2>
        <form id="loginForm">
            <div>
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>

            <div style="margin: 1.5rem 0; display: flex; align-items: center; gap: 1rem;">
                <div style="height: 1px; background: rgba(255,255,255,0.2); flex-grow: 1;"></div>
                <span style="color: var(--text-muted); font-size: 0.9rem;">OR</span>
                <div style="height: 1px; background: rgba(255,255,255,0.2); flex-grow: 1;"></div>
            </div>

            <div id="buttonDiv"></div>
        </form>
        <p style="margin-top: 1rem; text-align: center; color: var(--text-muted);">
            Don't have an account? <a href="register.php" style="color: var(--primary);">Register</a>
        </p>
    </div>
</div>

<!-- Google Config -->
<?php require_once '../config/google_config.php'; ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>

<script>
    // Standard Login
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await fetch('/Project/api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();
        if (result.success) handleRedirect(result.role);
        else alert(result.error);
    });

    // Google Login
    function handleCredentialResponse(response) {
        fetch('/Project/api/auth.php?action=google_login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: response.credential })
        })
            .then(res => res.json())
            .then(result => {
                if (result.success) handleRedirect(result.role);
                else alert(result.error || 'Login Failed');
            });
    }

    function handleRedirect(role) {
        if (role === 'admin') window.location.href = 'dashboard.php';
        else if (role === 'gatekeeper') window.location.href = 'terminal.php';
        else window.location.href = 'dashboard.php'; // Default to Dashboard for users
    }

    window.onload = function () {
        google.accounts.id.initialize({
            client_id: "<?php echo GOOGLE_CLIENT_ID; ?>",
            callback: handleCredentialResponse,
            auto_select: false
        });
        // Calculate optimal width
        const containerWidth = document.getElementById("buttonDiv").clientWidth || 320;

        google.accounts.id.renderButton(
            document.getElementById("buttonDiv"),
            {
                theme: "filled_blue",
                size: "large",
                shape: "pill",
                width: containerWidth + "", // String width
                logo_alignment: "left",
                text: "signin_with"
            }
        );
    }
</script>

<?php require_once '../includes/footer.php'; ?>