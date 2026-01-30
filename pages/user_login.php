<?php
require_once '../includes/header.php';

if (isset($_SESSION['user_id'])) {
    header('Location: student_dashboard.php');
    exit;
}
?>

<section
    style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 2rem 0; position: relative; overflow: hidden;">
    <!-- Background Glow -->
    <div
        style="position: absolute; top: 20%; left: 30%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255,31,31,0.05) 0%, transparent 70%); filter: blur(100px); pointer-events: none;">
    </div>

    <div class="glass-panel reveal"
        style="width: 100%; max-width: 500px; padding: 4rem; border-color: rgba(255, 31, 31, 0.1); position: relative;">
        <!-- Back Link -->
        <a href="../index.php"
            style="position: absolute; top: 1.5rem; left: 1.5rem; color: var(--p-text-muted); text-decoration: none; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; display: flex; align-items: center; gap: 0.5rem; transition: 0.3s;"
            onmouseover="this.style.color='white'; this.style.transform='translateX(-5px)';"
            onmouseout="this.style.color='var(--p-text-muted)'; this.style.transform='translateX(0)';">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
        <div style="text-align: center; margin-bottom: 3.5rem;">
            <div
                style="width: 72px; height: 72px; background: rgba(255, 31, 31, 0.1); border-radius: 22px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--p-brand); box-shadow: 0 10px 20px rgba(255,31,31,0.15);">
                <i class="fa-solid fa-shield-halved fa-2xl"></i>
            </div>
            <h2
                style="color: white; margin-bottom: 0.5rem; font-size: 2.2rem; font-weight: 800; letter-spacing: -0.02em;">
                Login</h2>
            <p style="color: var(--p-text-dim); font-size: 1rem;">Enter your credentials to access your dashboard.</p>
        </div>

        <form id="loginForm" autocomplete="off">
            <div style="margin-bottom: 2rem;">
                <label
                    style="display: block; color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.8rem;">University
                    Email</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-envelope"
                        style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--p-text-muted);"></i>
                    <input type="email" name="email" required placeholder="name@university.edu"
                        style="width: 100%; padding: 1.2rem 1.5rem 1.2rem 4rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 16px; color: white; transition: 0.3s;"
                        onfocus="this.style.borderColor='var(--p-brand)'; this.style.background='rgba(255,255,255,0.06)';"
                        onblur="this.style.borderColor='var(--p-border)'; this.style.background='rgba(255,255,255,0.03)';">
                </div>
            </div>

            <div style="margin-bottom: 2.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                    <label
                        style="color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Password</label>
                    <a href="forgot_password.php"
                        style="color: var(--p-brand); font-size: 0.8rem; font-weight: 600; text-decoration: none;">Forgot
                        Password?</a>
                </div>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock"
                        style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--p-text-muted);"></i>
                    <input type="password" name="password" required placeholder="••••••••"
                        style="width: 100%; padding: 1.2rem 1.5rem 1.2rem 4rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 16px; color: white; transition: 0.3s;"
                        onfocus="this.style.borderColor='var(--p-brand)'; this.style.background='rgba(255,255,255,0.06)';"
                        onblur="this.style.borderColor='var(--p-border)'; this.style.background='rgba(255,255,255,0.03)';">
                </div>
            </div>

            <button type="submit" class="btn btn-primary"
                style="width: 100%; padding: 1.2rem; font-size: 1.1rem; font-weight: 800; border-radius: 16px;">
                Login <i class="fa-solid fa-arrow-right-to-bracket" style="margin-left: 0.5rem;"></i>
            </button>

            <div style="margin: 2.5rem 0; display: flex; align-items: center; gap: 1rem;">
                <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.05);"></div>
                <span
                    style="color: var(--p-text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">or
                    continue with</span>
                <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.05);"></div>
            </div>

            <a href="../api/auth.php?action=google_login" class="btn btn-outline"
                style="width: 100%; padding: 1.2rem; gap: 1rem; border-color: rgba(255,255,255,0.1); border-radius: 16px; text-decoration: none; color: white;">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20" height="20"
                    alt="Google">
                Sign in with Google
            </a>

            <p style="text-align: center; margin-top: 3rem; color: var(--p-text-muted); font-size: 0.95rem;">
                Need an account? <a href="register.php"
                    style="color: white; font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--p-brand);">Register</a>
            </p>
        </form>
    </div>
</section>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Email/Password Login
        const loginForm = document.getElementById('loginForm');
        const loginBtn = loginForm.querySelector('button[type="submit"]');

        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const email = e.target.email.value.trim();
                const password = e.target.password.value;

                if (!email || !password) return;

                // Show loading state
                const originalBtnHtml = loginBtn.innerHTML;
                loginBtn.disabled = true;
                loginBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Logging In...';

                try {
                    const res = await fetch('/Project/EntryX/api/auth.php?action=login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email, password })
                    });

                    const result = await res.json();

                    if (result.success) {
                        // Intelligent Redirection based on role
                        if (result.role === 'super_admin' || result.role === 'event_admin') {
                            window.location.href = 'admin_dashboard.php';
                        } else if (result.role === 'security') {
                            window.location.href = 'security_dashboard.php';
                        } else {
                            window.location.href = 'student_dashboard.php';
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: result.error || 'Invalid email or password.',
                            confirmButtonColor: '#ff1f1f'
                        });
                        loginBtn.disabled = false;
                        loginBtn.innerHTML = originalBtnHtml;
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire('Error', 'Connection error. Please try again.', 'error');
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = originalBtnHtml;
                }
            });
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../includes/footer.php'; ?>