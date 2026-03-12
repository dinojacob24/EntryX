<?php
// FORCE NO CACHING: This prevents the browser from using the "Back" button to show a cached page.
// The browser will be forced to ask the server for the page again, triggering our session redirects.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
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
        <?php
        $userRole = $_SESSION['role'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        // Determine which nav to show
        $isAdmin = in_array($userRole, ['super_admin', 'event_admin']);
        $isSecurity = ($userRole === 'security');
        $isStaff = ($userRole === 'staff');
        $isStudent = in_array($userRole, ['internal', 'external']);
        $isLoggedIn = !empty($userId);

        // Dashboard links per role
        $dashboardUrl = '/Project/EntryX/pages/student_dashboard.php';
        $dashboardLabel = 'My Dashboard';
        if ($isAdmin) {
            $dashboardUrl = '/Project/EntryX/pages/admin_dashboard.php';
            $dashboardLabel = 'Admin Panel';
        }
        if ($isSecurity) {
            $dashboardUrl = '/Project/EntryX/pages/security_dashboard.php';
            $dashboardLabel = 'Security Terminal';
        }
        if ($isStaff) {
            $dashboardUrl = '/Project/EntryX/pages/staff_dashboard.php';
            $dashboardLabel = 'Staff Panel';
        }
        ?>
        <nav class="nav-standard">
            <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
                <?php if ($isLoggedIn): ?>
                    <!-- LOGGED IN: clicking logo goes to their dashboard -->
                    <a href="<?php echo $dashboardUrl; ?>"
                        style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none;">
                    <?php else: ?>
                        <!-- GUEST: clicking logo goes to public home -->
                        <a href="/Project/EntryX/index.php"
                            style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none;">
                        <?php endif; ?>
                        <div
                            style="width: 42px; height: 42px; background: var(--grad-crimson); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(255,31,31,0.3);">
                            <i class="fa-solid fa-bolt" style="color: white; font-size: 1.2rem;"></i>
                        </div>
                        <span
                            style="font-size: 1.4rem; font-weight: 900; color: white; letter-spacing: -0.05em; font-family: 'Plus Jakarta Sans';">ENTRY<span
                                style="color: var(--p-brand);">X</span></span>
                    </a>

                    <div style="display: flex; gap: 2.5rem; align-items: center;">

                        <?php if ($isAdmin): ?>
                            <!-- ADMIN NAV -->
                            <div style="display: flex; gap: 2rem;" class="nav-links-main">
                                <a href="<?php echo $dashboardUrl; ?>" class="nav-link-premium">
                                    <i class="fa-solid fa-gauge-high" style="margin-right: 0.3rem;"></i>Dashboard
                                </a>
                                <a href="/Project/EntryX/pages/results.php" class="nav-link-premium">Results</a>
                            </div>

                        <?php elseif ($isSecurity): ?>
                            <!-- SECURITY NAV -->
                            <div style="display: flex; gap: 2rem;" class="nav-links-main">
                                <a href="<?php echo $dashboardUrl; ?>" class="nav-link-premium">
                                    <i class="fa-solid fa-camera" style="margin-right: 0.3rem;"></i>Security Terminal
                                </a>
                            </div>

                        <?php elseif ($isStaff): ?>
                            <!-- STAFF NAV -->
                            <div style="display: flex; gap: 2rem;" class="nav-links-main">
                                <a href="<?php echo $dashboardUrl; ?>" class="nav-link-premium">
                                    <i class="fa-solid fa-id-badge" style="margin-right: 0.3rem;"></i>Staff Panel
                                </a>
                            </div>

                        <?php elseif ($isStudent): ?>
                            <!-- STUDENT NAV -->
                            <div style="display: flex; gap: 2rem;" class="nav-links-main">
                                <a href="<?php echo $dashboardUrl; ?>" class="nav-link-premium">
                                    <i class="fa-solid fa-gauge-high" style="margin-right: 0.3rem;"></i>Dashboard
                                </a>
                                <a href="/Project/EntryX/pages/results.php" class="nav-link-premium">Results</a>
                                <a href="<?php echo $dashboardUrl; ?>" class="nav-link-premium">
                                    <i class="fa-solid fa-ticket" style="margin-right: 0.3rem;"></i>My Events
                                </a>
                            </div>

                        <?php else: ?>
                            <!-- PUBLIC / GUEST NAV -->
                            <div style="display: flex; gap: 2rem;" class="nav-links-main">
                                <a href="/Project/EntryX/index.php" class="nav-link-premium">Home</a>
                                <a href="/Project/EntryX/pages/results.php" class="nav-link-premium">Results</a>
                            </div>
                        <?php endif; ?>

                        <!-- Right side: user info + logout OR login/signup buttons -->
                        <?php if ($isLoggedIn): ?>
                            <div style="width: 1px; height: 20px; background: rgba(255,255,255,0.1);"></div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="text-align: right;" class="user-meta-hide">
                                    <div style="font-size: 0.85rem; font-weight: 700; color: white;">
                                        <?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?>
                                    </div>
                                    <div
                                        style="font-size: 0.65rem; color: var(--p-brand); text-transform: uppercase; font-weight: 800; letter-spacing: 0.1em;">
                                        <?php echo str_replace('_', ' ', $userRole); ?>
                                    </div>
                                </div>
                                <!-- Avatar: goes to dashboard (not logout) -->
                                <div class="user-avatar-nav" title="Go to Dashboard"
                                    onclick="window.location.href='<?php echo $dashboardUrl; ?>'">
                                    <i class="fa-solid fa-user-astronaut"></i>
                                </div>
                                <!-- Separate logout button -->
                                <button onclick="confirmNavLogout()" title="Logout"
                                    style="background: rgba(255,31,31,0.08); border: 1px solid rgba(255,31,31,0.2); border-radius: 10px; color: #ef4444; padding: 0.5rem 0.9rem; cursor: pointer; font-size: 0.8rem; font-weight: 700; transition: all 0.3s;"
                                    onmouseover="this.style.background='rgba(255,31,31,0.18)';"
                                    onmouseout="this.style.background='rgba(255,31,31,0.08)';">
                                    <i class="fa-solid fa-power-off"></i>
                                </button>
                            </div>

                        <?php else: ?>
                            <!-- Guest: Login + Sign Up -->
                            <div style="display: flex; gap: 1rem;">
                                <a href="/Project/EntryX/pages/user_login.php" class="btn btn-outline"
                                    style="padding: 0.6rem 1.4rem; font-size: 0.85rem; border-radius: 12px;">Login</a>
                            </div>
                        <?php endif; ?>

                    </div>
            </div>
        </nav>

        <script>
            // Logout confirmation from nav
            async function confirmNavLogout() {
                const res = await Swal.fire({
                    title: 'Logout?',
                    text: 'Are you sure you want to end your session?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: 'rgba(255,255,255,0.1)',
                    confirmButtonText: '<i class="fa-solid fa-power-off"></i> Logout',
                    cancelButtonText: 'Stay',
                    background: '#0a0a0a',
                    color: '#fff'
                });
                if (res.isConfirmed) {
                    window.location.href = '/Project/EntryX/api/auth.php?action=logout';
                }
            }

            // Prevent going back to protected pages after logout (or going back to public pages while logged in as admin)
            <?php if ($isAdmin || $isSecurity || $isStaff): ?>
                // Disable browser back button for admin/security/staff on their active pages
                history.pushState(null, document.title, location.href);
                window.addEventListener('popstate', function () {
                    history.pushState(null, document.title, location.href);
                });
            <?php endif; ?>

            // Global BFCache (Back-Forward Cache) Buster
            // Forces a hard reload if the user uses the browser Back button,
            // ensuring PHP session checks trigger and redirect them correctly.
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        </script>
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