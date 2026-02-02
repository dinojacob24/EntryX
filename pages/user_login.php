<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: student_dashboard.php');
    exit;
}

require_once '../includes/header.php';
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
            <p style="color: var(--p-text-dim); font-size: 1rem; max-width: 380px; margin: 0 auto;">Sign in securely
                with your Google account to access your dashboard.</p>
        </div>

        <!-- Google Sign-in Button -->
        <a href="../api/auth.php?action=google_login"
            style="width: 100%; padding: 1.3rem; display: flex; align-items: center; justify-content: center; gap: 1rem; border-radius: 12px; text-decoration: none; background: white; color: #3c4043; font-size: 1rem; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24); transition: all 0.2s; border: none;"
            onmouseover="this.style.boxShadow='0 3px 8px rgba(0,0,0,0.15), 0 3px 6px rgba(0,0,0,0.1)'; this.style.transform='translateY(-1px)';"
            onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)'; this.style.transform='translateY(0)';">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20" height="20"
                alt="Google">
            Sign in with Google
        </a>

        <div
            style="margin: 3rem 0; padding: 1.5rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,31,31,0.1); border-radius: 16px;">
            <p style="color: var(--p-text-dim); font-size: 0.85rem; margin: 0; line-height: 1.6;">
                <i class="fa-solid fa-info-circle" style="color: var(--p-brand); margin-right: 0.5rem;"></i>
                <strong style="color: white;">Internal Users:</strong> Use your @ajce.in or @ac.in email<br>
                <i class="fa-solid fa-info-circle" style="color: var(--p-brand); margin-right: 0.5rem;"></i>
                <strong style="color: white;">External Users:</strong> Register first, then sign in with the same email
            </p>
        </div>

        <p style="text-align: center; color: var(--p-text-muted); font-size: 0.95rem;">
            Need an account? <a href="register.php"
                style="color: white; font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--p-brand);">Register</a>
        </p>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    passwordInput.type = 'text';
    toggleIcon.classList.remove('fa-eye');
    toggleIcon.classList.add('fa-eye-slash');
        } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
    }

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
                        } else if (result.role === 'external') {
                            window.location.href = 'external_dashboard.php';
                        } else if (result.role === 'staff') {
                            window.location.href = 'staff_dashboard.php';
                        } else {
                            window.location.href = 'student_dashboard.php';
                        }
                    } else if (result.error === 'registration_required') {
                        // Guest trying to log in directly without registering
                        Swal.fire({
                            icon: 'info',
                            title: 'Guest Registration Required',
                            text: 'It looks like you haven\'t registered for an external program yet. Please sign up first to access your dashboard.',
                            showCancelButton: true,
                            confirmButtonText: 'Sign Up Now',
                            confirmButtonColor: '#ff1f1f',
                            background: '#0a0a0a',
                            color: '#fff'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'register.php';
                            }
                        });
                        loginBtn.disabled = false;
                        loginBtn.innerHTML = originalBtnHtml;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: result.error || 'Invalid email or password.',
                            confirmButtonColor: '#ff1f1f',
                            background: '#0a0a0a',
                            color: '#fff'
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

        // Check for Google Login Redirection Errors
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        if (error) {
            let errorMsg = 'An error occurred during Google authentication.';
            if (error === 'google_internal_only') errorMsg = 'Google Sign-in is reserved for College Students & Staff with official Email IDs. Guests must use their Registered Email & Password.';
            if (error === 'linking_failed') errorMsg = 'This Google account cannot be linked to an admin profile. Please use your credentials.';
            if (error === 'auth_failed') errorMsg = 'Google authentication failed. Please try again.';

            Swal.fire({
                icon: 'warning',
                title: 'Access Restricted',
                text: errorMsg,
                confirmButtonColor: '#ff1f1f',
                background: '#0a0a0a',
                color: '#fff'
            });
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../includes/footer.php'; ?>