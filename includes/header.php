<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENTRY X | Premium Event Management</title>
    <link rel="stylesheet" href="/Project/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <nav class="glass-panel"
        style="margin: 1rem; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div
            style="font-size: 1.5rem; font-weight: 700; background: linear-gradient(to right, #4f46e5, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            ENTRY X
        </div>
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <a href="/Project/" style="color: var(--text-light); text-decoration: none; font-weight: 500;">Events</a>
            <a href="/Project/pages/results.php"
                style="color: var(--text-light); text-decoration: none; font-weight: 500;">Results</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="/Project/pages/dashboard.php"
                        style="color: var(--text-light); text-decoration: none; font-weight: 500;">Admin Dashboard</a>
                <?php elseif ($_SESSION['role'] === 'gatekeeper'): ?>
                    <a href="/Project/pages/scanner.php"
                        style="color: var(--text-light); text-decoration: none; font-weight: 500;">Scanner</a>
                <?php else: ?>
                    <a href="/Project/pages/dashboard.php"
                        style="color: var(--text-light); text-decoration: none; font-weight: 500;">My Tickets</a>
                <?php endif; ?>
                <a href="/Project/api/auth.php?action=logout" class="btn btn-primary"
                    style="padding: 0.5rem 1rem;">Logout</a>
            <?php else: ?>
                <a href="/Project/pages/login.php"
                    style="color: var(--text-light); text-decoration: none; font-weight: 500;">Login</a>
                <a href="/Project/pages/register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container">