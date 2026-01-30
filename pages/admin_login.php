<?php
require_once '../includes/header.php';

if (isset($_SESSION['user_id'])) {
    if (in_array($_SESSION['role'], ['super_admin', 'event_admin'])) {
        header('Location: admin_dashboard.php');
        exit;
    }
    // If a 'security' role user is logged in, redirect them to sub_admin_login.php as per instruction
    if (in_array($_SESSION['role'], ['security'])) {
        header('Location: sub_admin_login.php');
        exit;
    }
}
?>

<section
    style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 2rem 0; position: relative; overflow: hidden;">
    <!-- Background Glow -->
    <div
        style="position: absolute; top: 20%; right: 30%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255,31,31,0.08) 0%, transparent 70%); filter: blur(100px); pointer-events: none;">
    </div>

    <div class="glass-panel reveal"
        style="width: 100%; max-width: 500px; padding: 4rem; border-color: rgba(255, 31, 31, 0.15); box-shadow: 0 30px 60px rgba(255,31,31,0.1); position: relative;">
        <!-- Back Link -->
        <a href="../index.php"
            style="position: absolute; top: 1.5rem; left: 1.5rem; color: var(--p-text-muted); text-decoration: none; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; display: flex; align-items: center; gap: 0.5rem; transition: 0.3s;"
            onmouseover="this.style.color='white'; this.style.transform='translateX(-5px)';"
            onmouseout="this.style.color='var(--p-text-muted)'; this.style.transform='translateX(0)';">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
        <div style="text-align: center; margin-bottom: 3.5rem;">
            <div
                style="width: 80px; height: 80px; background: var(--grad-crimson); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white; box-shadow: 0 15px 30px var(--p-brand-glow);">
                <i class="fa-solid fa-shield-halved fa-2xl"></i>
            </div>
            <h2
                style="color: white; margin-bottom: 0.5rem; font-size: 2.5rem; font-weight: 900; letter-spacing: -0.02em;">
                ADMINISTRATION <span style="color: var(--p-brand);">TERMINAL</span>
            </h2>
            <p
                style="color: var(--p-text-dim); font-size: 1rem; text-transform: uppercase; letter-spacing: 0.2em; font-weight: 700;">
                Highest Level Authorization Required</p>
        </div>

        <form id="adminLoginForm" autocomplete="off">
            <div style="margin-bottom: 2rem;">
                <label
                    style="display: block; color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.8rem;">Administrative
                    Email</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-user-shield"
                        style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--p-brand);"></i>
                    <input type="email" name="email" required placeholder="admin@entryx.system"
                        style="width: 100%; padding: 1.2rem 1.5rem 1.2rem 4rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 16px; color: white; transition: 0.3s;"
                        onfocus="this.style.borderColor='var(--p-brand)'; this.style.background='rgba(255,31,31,0.05)';"
                        onblur="this.style.borderColor='var(--p-border)'; this.style.background='rgba(255,255,255,0.03)';">
                </div>
            </div>

            <div style="margin-bottom: 2.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                    <label
                        style="color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Security
                        Key</label>
                    <a href="forgot_password.php"
                        style="color: var(--p-brand); font-size: 0.8rem; font-weight: 600; text-decoration: none;">Reset
                        Access?</a>
                </div>
                <div style="position: relative;">
                    <i class="fa-solid fa-key"
                        style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--p-brand);"></i>
                    <input type="password" name="password" required placeholder="••••••••"
                        style="width: 100%; padding: 1.2rem 1.5rem 1.2rem 4rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 16px; color: white; transition: 0.3s;"
                        onfocus="this.style.borderColor='var(--p-brand)'; this.style.background='rgba(255,31,31,0.05)';"
                        onblur="this.style.borderColor='var(--p-border)'; this.style.background='rgba(255,255,255,0.03)';">
                </div>
            </div>

            <button type="submit" class="btn btn-primary"
                style="width: 100%; padding: 1.3rem; font-size: 1.1rem; font-weight: 900; border-radius: 16px; text-transform: uppercase; letter-spacing: 0.1em;">
                <i class="fa-solid fa-lock-open"></i> Authorize Access
            </button>

            <div style="margin: 3rem 0; text-align: center;">
                <div
                    style="display: inline-block; background: rgba(255,31,31,0.05); border: 1px solid rgba(255,31,31,0.1); border-radius: 12px; padding: 1rem 2rem;">
                    <i class="fa-solid fa-triangle-exclamation"
                        style="color: var(--p-brand); margin-right: 0.5rem;"></i>
                    <span style="color: var(--p-text-dim); font-size: 0.85rem; font-weight: 600;">Administrators
                        Only</span>
                </div>
            </div>

            <p style="text-align: center; margin-top: 2rem; color: var(--p-text-muted); font-size: 0.9rem;">
                Sub Admin Login? <a href="sub_admin_login.php"
                    style="color: #3b82f6; font-weight: 700; text-decoration: none;">Click Here</a> |
                Student? <a href="user_login.php"
                    style="color: white; font-weight: 700; text-decoration: none;">Login</a>
            </p>
        </form>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const adminLoginForm = document.getElementById('adminLoginForm');
        const loginBtn = adminLoginForm.querySelector('button[type="submit"]');

        adminLoginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = e.target.email.value.trim();
            const password = e.target.password.value;

            if (!email || !password) return;

            // Show loading state
            const originalBtnHtml = loginBtn.innerHTML;
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Verifying...';

            try {
                const res = await fetch('/Project/EntryX/api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const result = await res.json();

                if (result.success) {
                    // Redirect based on role
                    if (result.role === 'super_admin' || result.role === 'event_admin') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Access Granted',
                            text: 'Welcome to the Administration Portal',
                            background: '#0a0a0a',
                            color: '#fff',
                            confirmButtonColor: '#ff1f1f',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.href = 'admin_dashboard.php';
                    } else if (result.role === 'security') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Access Granted',
                            text: 'Security Protocol Initialized',
                            background: '#0a0a0a',
                            color: '#fff',
                            confirmButtonColor: '#ff1f1f',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.href = 'security_dashboard.php';
                    } else {
                        // Non-authorized user
                        await Swal.fire({
                            icon: 'error',
                            title: 'Access Denied',
                            text: 'This terminal is restricted to authorized personnel only.',
                            background: '#0a0a0a',
                            color: '#fff',
                            confirmButtonColor: '#ff1f1f'
                        });
                        loginBtn.disabled = false;
                        loginBtn.innerHTML = originalBtnHtml;
                    }
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Authentication Failed',
                        text: result.error || 'Invalid credentials',
                        background: '#0a0a0a',
                        color: '#fff',
                        confirmButtonColor: '#ff1f1f'
                    });
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = originalBtnHtml;
                }
            } catch (error) {
                console.error('Login error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Connection to server failed',
                    background: '#0a0a0a',
                    color: '#fff',
                    confirmButtonColor: '#ff1f1f'
                });
                loginBtn.disabled = false;
                loginBtn.innerHTML = originalBtnHtml;
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../includes/footer.php'; ?>