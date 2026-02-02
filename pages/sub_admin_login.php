<?php
require_once '../includes/header.php';

if (isset($_SESSION['user_id'])) {
    if (in_array($_SESSION['role'], ['super_admin', 'event_admin'])) {
        header('Location: admin_dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'security') {
        header('Location: security_dashboard.php');
        exit;
    }
}
?>

<section
    style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 2rem 0; position: relative; overflow: hidden;">
    <!-- Background Glow -->
    <div
        style="position: absolute; top: 20%; right: 30%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%); filter: blur(100px); pointer-events: none;">
    </div>

    <div class="glass-panel reveal"
        style="width: 100%; max-width: 500px; padding: 4rem; border-color: rgba(59, 130, 246, 0.15); box-shadow: 0 30px 60px rgba(59,130,246,0.1); position: relative;">
        <!-- Back Link -->
        <a href="../index.php"
            style="position: absolute; top: 1.5rem; left: 1.5rem; color: var(--p-text-muted); text-decoration: none; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; display: flex; align-items: center; gap: 0.5rem; transition: 0.3s;"
            onmouseover="this.style.color='white'; this.style.transform='translateX(-5px)';"
            onmouseout="this.style.color='var(--p-text-muted)'; this.style.transform='translateX(0)';">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
        <div style="text-align: center; margin-bottom: 3.5rem;">
            <div
                style="width: 80px; height: 80px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white; box-shadow: 0 15px 30px rgba(59, 130, 246, 0.3);">
                <i class="fa-solid fa-user-shield fa-2xl"></i>
            </div>
            <h2
                style="color: white; margin-bottom: 0.5rem; font-size: 2.5rem; font-weight: 900; letter-spacing: -0.02em;">
                SUB ADMIN <span style="color: #3b82f6;">TERMINAL</span>
            </h2>
            <p
                style="color: var(--p-text-dim); font-size: 1rem; text-transform: uppercase; letter-spacing: 0.2em; font-weight: 700;">
                Secure Staff Access Required</p>
        </div>

        <form id="subAdminLoginForm" autocomplete="off">
            <div style="margin-bottom: 2rem;">
                <label
                    style="display: block; color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.8rem;">Administrative
                    Email</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-envelope"
                        style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: #3b82f6;"></i>
                    <input type="email" name="email" required placeholder="staff@entryx.system" autocomplete="off"
                        style="width: 100%; padding: 1.2rem 1.5rem 1.2rem 4rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 16px; color: white; transition: 0.3s;"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.background='rgba(59,130,246,0.05)';"
                        onblur="this.style.borderColor='var(--p-border)'; this.style.background='rgba(255,255,255,0.03)';">
                </div>
            </div>

            <div style="margin-bottom: 2.5rem;">
                <label
                    style="display: block; color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.8rem;">Security
                    Key</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-key"
                        style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: #3b82f6;"></i>
                    <input type="password" name="password" required placeholder="••••••••" autocomplete="new-password"
                        style="width: 100%; padding: 1.2rem 1.5rem 1.2rem 4rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 16px; color: white; transition: 0.3s;"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.background='rgba(59,130,246,0.05)';"
                        onblur="this.style.borderColor='var(--p-border)'; this.style.background='rgba(255,255,255,0.03)';">
                </div>
            </div>

            <button type="submit" class="btn btn-primary"
                style="width: 100%; padding: 1.3rem; font-size: 1.1rem; font-weight: 900; border-radius: 16px; text-transform: uppercase; letter-spacing: 0.1em; background: #3b82f6; border: none;">
                <i class="fa-solid fa-unlock"></i> Access Terminal
            </button>

            <div style="margin: 3rem 0; text-align: center;">
                <div
                    style="display: inline-block; background: rgba(59,130,246,0.05); border: 1px solid rgba(59,130,246,0.1); border-radius: 12px; padding: 1rem 2rem;">
                    <i class="fa-solid fa-circle-info" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                    <span style="color: var(--p-text-dim); font-size: 0.85rem; font-weight: 600;">Staff & Security
                        Only</span>
                </div>
            </div>

            <p style="text-align: center; margin-top: 2rem; color: var(--p-text-muted); font-size: 0.9rem;">
                Super Admin? <a href="admin_login.php"
                    style="color: #ef4444; font-weight: 700; text-decoration: none; border-bottom: 1px solid rgba(239, 68, 68, 0.3);">Login
                    Here</a>
            </p>
        </form>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const subAdminLoginForm = document.getElementById('subAdminLoginForm');
        const loginBtn = subAdminLoginForm.querySelector('button[type="submit"]');

        subAdminLoginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = e.target.email.value.trim();
            const password = e.target.password.value;

            if (!email || !password) return;

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
                    if (result.role === 'super_admin' || result.role === 'event_admin' || result.role === 'security') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Access Granted',
                            text: 'Welcome to the Sub Admin Terminal',
                            background: '#0a0a0a',
                            color: '#fff',
                            confirmButtonColor: '#3b82f6',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        if (result.role === 'super_admin' || result.role === 'event_admin') {
                            window.location.href = 'admin_dashboard.php';
                        } else {
                            window.location.href = 'security_dashboard.php';
                        }
                    } else {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Restricted Access',
                            text: 'Your account does not have staff permissions.',
                            background: '#0a0a0a',
                            color: '#fff',
                            confirmButtonColor: '#3b82f6'
                        });
                        loginBtn.disabled = false;
                        loginBtn.innerHTML = '<i class="fa-solid fa-unlock"></i> Access Terminal';
                    }
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Authentication Failed',
                        text: result.error || 'Invalid credentials',
                        background: '#0a0a0a',
                        color: '#fff',
                        confirmButtonColor: '#3b82f6'
                    });
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<i class="fa-solid fa-unlock"></i> Access Terminal';
                }
            } catch (error) {
                console.error('Login error:', error);
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fa-solid fa-unlock"></i> Access Terminal';
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../includes/footer.php'; ?>