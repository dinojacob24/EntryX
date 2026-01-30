<?php
require_once '../includes/header.php';
?>

<section
    style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 2rem 0; position: relative; overflow: hidden;">
    <!-- Background Glow -->
    <div
        style="position: absolute; top: 30%; left: 20%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255,31,31,0.04) 0%, transparent 70%); filter: blur(80px); pointer-events: none;">
    </div>

    <div class="glass-panel reveal"
        style="width: 100%; max-width: 500px; padding: 4rem; border-color: rgba(255, 31, 31, 0.1);">
        <div style="text-align: center; margin-bottom: 3.5rem;">
            <div
                style="width: 72px; height: 72px; background: rgba(234, 179, 8, 0.1); border-radius: 22px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #eab308; box-shadow: 0 10px 20px rgba(234,179,8,0.15);">
                <i class="fa-solid fa-key fa-2xl"></i>
            </div>
            <h2
                style="color: white; margin-bottom: 0.5rem; font-size: 2.2rem; font-weight: 800; letter-spacing: -0.02em;">
                Access Recovery</h2>
            <p style="color: var(--p-text-dim); font-size: 1rem;">Reset your security credentials securely.</p>
        </div>

        <form id="forgotForm">
            <div style="margin-bottom: 2.5rem;">
                <label
                    style="display: block; color: var(--p-text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.8rem;">Registered
                    Email</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-envelope"
                        style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--p-text-muted);"></i>
                    <input type="email" name="email" required placeholder="your@email.com"
                        style="width: 100%; padding: 1.2rem 1.5rem 1.2rem 4rem; background: rgba(255,255,255,0.03); border: 1px solid var(--p-border); border-radius: 16px; color: white; transition: 0.3s;"
                        onfocus="this.style.borderColor='var(--p-brand)'; this.style.background='rgba(255,255,255,0.06)';"
                        onblur="this.style.borderColor='var(--p-border)'; this.style.background='rgba(255,255,255,0.03)';">
                </div>
            </div>

            <div id="alertBox"
                style="display:none; padding: 1.5rem; border-radius: 14px; margin-bottom: 2rem; font-size: 0.95rem;">
            </div>

            <button type="submit" class="btn btn-primary"
                style="width: 100%; padding: 1.2rem; font-size: 1.1rem; font-weight: 800; border-radius: 16px;">
                <i class="fa-solid fa-paper-plane"></i> Send Reset Link
            </button>

            <a href="user_login.php" class="btn btn-outline"
                style="width: 100%; margin-top: 1rem; padding: 1.2rem; border-radius: 16px; text-decoration: none; color: white;">
                <i class="fa-solid fa-arrow-left"></i> Back to Login
            </a>
        </form>

        <div
            style="margin-top: 3rem; text-align: center; padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px solid rgba(255,255,255,0.03);">
            <p style="color: var(--p-text-muted); font-size: 0.85rem; line-height: 1.6; margin: 0;">
                <i class="fa-solid fa-circle-info" style="color: var(--p-brand); margin-right: 0.5rem;"></i>
                A secure reset link will be sent to your email address. The link expires in 1 hour.
            </p>
        </div>
    </div>
</section>

<script>
    document.getElementById('forgotForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const alertBox = document.getElementById('alertBox');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        alertBox.style.display = 'none';

        try {
            const formData = new FormData(e.target);
            const response = await fetch('../api/auth.php?action=forgot_password', {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (jsonError) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid server response');
            }

            alertBox.style.display = 'block';
            if (result.success) {
                if (result.email_success) {
                    alertBox.style.background = 'rgba(16, 185, 129, 0.1)';
                    alertBox.style.color = '#10b981';
                    alertBox.style.border = '1px solid rgba(16, 185, 129, 0.2)';
                    alertBox.innerHTML = `<i class='fa-solid fa-circle-check' style='margin-right: 0.5rem;'></i> ${result.message}`;
                } else {
                    // Presentation Mode / Localhost Fallback
                    alertBox.style.background = 'rgba(234, 179, 8, 0.1)';
                    alertBox.style.color = '#eab308';
                    alertBox.style.border = '1px solid rgba(234, 179, 8, 0.2)';
                    alertBox.innerHTML = `
                        <div style='margin-bottom: 1rem; font-weight: 700; font-size: 1rem;'>
                            <i class='fa-solid fa-triangle-exclamation'></i> Development Mode Active
                        </div>
                        <p style='font-size: 0.85rem; margin-bottom: 1rem; color: var(--p-text-dim);'>Email sending is disabled on localhost. Use the direct link below:</p>
                        <a href='${result.debug_link}' class='btn btn-primary' style='display: block; padding: 0.8rem; text-decoration: none; color: white; font-weight: 800; font-size: 0.9rem;'>
                            <i class='fa-solid fa-external-link-alt'></i> RESET PASSWORD NOW
                        </a>
                    `;
                }
            } else {
                alertBox.style.background = 'rgba(239, 68, 68, 0.1)';
                alertBox.style.color = 'var(--p-brand)';
                alertBox.style.border = '1px solid rgba(255, 31, 31, 0.2)';
                alertBox.innerHTML = `<i class='fa-solid fa-circle-xmark' style='margin-right: 0.5rem;'></i> ${result.error || 'Failed to send request.'}`;
            }
        } catch (error) {
            console.error(error);
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(239, 68, 68, 0.1)';
            alertBox.style.color = 'var(--p-brand)';
            alertBox.style.border = '1px solid rgba(255, 31, 31, 0.2)';
            alertBox.textContent = 'An error occurred. Check console for details.';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Reset Link';
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>