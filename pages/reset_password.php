<?php
require_once '../includes/header.php';
$token = $_GET['token'] ?? '';
?>

<div class="row justify-content-center"
    style="min-height: 80vh; display: flex; align-items: center; justify-content: center;">
    <div class="glass-panel animate-fade-in"
        style="width: 100%; max-width: 400px; padding: 2.5rem; background: var(--bg-surface); border: 1px solid var(--border-color);">

        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 style="color: var(--primary); margin-bottom: 0.5rem; letter-spacing: -0.05em;">New Password</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Set your new secure password</p>
        </div>

        <form id="resetForm">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label>New Password</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock"
                        style="position: absolute; left: 1rem; top: 1rem; color: var(--text-tertiary);"></i>
                    <input type="password" name="password" required placeholder="••••••••"
                        style="padding-left: 2.8rem;">
                </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <label>Confirm Password</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-check-double"
                        style="position: absolute; left: 1rem; top: 1rem; color: var(--text-tertiary);"></i>
                    <input type="password" id="confirm_password" required placeholder="••••••••"
                        style="padding-left: 2.8rem;">
                </div>
            </div>

            <div id="alertBox"
                style="display:none; padding: 10px; border-radius: 8px; margin-top: 1rem; font-size: 0.9rem;"></div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">
                Reset Password
            </button>
        </form>
    </div>
</div>

<script>
    document.getElementById('resetForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const alertBox = document.getElementById('alertBox');
        const pass = e.target.password.value;
        const confirm = document.getElementById('confirm_password').value;

        if (pass !== confirm) {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(239, 68, 68, 0.1)';
            alertBox.style.color = '#ef4444';
            alertBox.textContent = 'Passwords do not match.';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resetting...';
        alertBox.style.display = 'none';

        try {
            const formData = new FormData(e.target);
            const response = await fetch('../api/auth.php?action=reset_password', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            alertBox.style.display = 'block';
            if (result.success) {
                alertBox.style.background = 'rgba(239, 68, 68, 0.1)';
                alertBox.style.color = '#ef4444';
                alertBox.innerHTML = result.message + '<br><br><a href="user_login.php" style="color: white; font-weight: bold;">Login Now</a>';
                e.target.style.display = 'none';
            } else {
                alertBox.style.background = 'rgba(239, 68, 68, 0.1)';
                alertBox.style.color = '#ef4444';
                alertBox.textContent = result.error || 'Failed to reset password.';
            }
        } catch (error) {
            console.error(error);
            alertBox.style.display = 'block';
            alertBox.textContent = 'An error occurred.';
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Reset Password';
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>