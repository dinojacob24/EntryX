<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENTRY X | Premium Event Management</title>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/Project/EntryX/assets/css/style.css">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body>
    <?php if (!isset($hideHeaderNav) || !$hideHeaderNav): ?>
        <nav class="nav-standard">
            <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
                <a href="/Project/EntryX/index.php"
                    style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none;">
                    <div
                        style="width: 42px; height: 42px; background: var(--grad-crimson); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(255,31,31,0.3);">
                        <i class="fa-solid fa-bolt" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span
                        style="font-size: 1.4rem; font-weight: 900; color: white; letter-spacing: -0.05em; font-family: 'Plus Jakarta Sans';">ENTRY<span
                            style="color: var(--p-brand);">X</span></span>
                </a>

                <div style="display: flex; gap: 2.5rem; align-items: center;">
                    <div style="display: flex; gap: 2rem;" class="nav-links-main">
                        <a href="/Project/EntryX/index.php" class="nav-link-premium">Home</a>
                        <a href="/Project/EntryX/pages/results.php" class="nav-link-premium">Results</a>
                        <a href="/Project/EntryX/pages/admin_login.php" class="nav-link-premium"
                            style="color: var(--p-brand);">
                            <i class="fa-solid fa-shield-halved" style="margin-right: 0.3rem;"></i>Admin
                        </a>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="width: 1px; height: 20px; background: rgba(255,255,255,0.1);"></div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="text-align: right;" class="user-meta-hide">
                                <div style="font-size: 0.85rem; font-weight: 700; color: white;">
                                    <?php echo explode(' ', $_SESSION['name'])[0]; ?>
                                </div>
                                <div
                                    style="font-size: 0.65rem; color: var(--p-brand); text-transform: uppercase; font-weight: 800; letter-spacing: 0.1em;">
                                    <?php echo str_replace('_', ' ', $_SESSION['role']); ?>
                                </div>
                            </div>
                            <div class="user-avatar-nav"
                                onclick="window.location.href='/Project/EntryX/api/auth.php?action=logout'">
                                <i class="fa-solid fa-user-astronaut"></i>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; gap: 1rem;">
                            <a href="/Project/EntryX/pages/user_login.php" class="btn btn-outline"
                                style="padding: 0.6rem 1.4rem; font-size: 0.85rem; border-radius: 12px;">Login</a>
                            <a href="/Project/EntryX/pages/register.php" class="btn btn-primary"
                                style="padding: 0.6rem 1.4rem; font-size: 0.85rem; border-radius: 12px;">Sign Up</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <style>
        .nav-link-premium {
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
        }

        .nav-link-premium:hover {
            color: white;
        }

        .nav-link-premium::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--p-brand);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 2px;
        }

        .nav-link-premium:hover::after {
            width: 100%;
        }

        .user-avatar-nav {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: #94a3b8;
        }

        .user-avatar-nav:hover {
            border-color: var(--p-brand);
            color: white;
            background: rgba(255, 31, 31, 0.05);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {

            .nav-links-main,
            .user-meta-hide {
                display: none;
            }
        }

        .main-content-wrapper {
            flex: 1;
            padding: 2rem 0;
        }
    </style>

    <?php if (!isset($useCustomLayout) || !$useCustomLayout): ?>
        <div class="main-content-wrapper">
            <div class="container">
            <?php endif; ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const observerOptions = {
                        threshold: 0.1,
                        rootMargin: '0px 0px -50px 0px'
                    };

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('active');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, observerOptions);

                    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
                });
            </script>