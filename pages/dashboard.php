<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../classes/Event.php';
require_once '../classes/Registration.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit;
}

// Redirect Admins to their respective dashboards
if ($_SESSION['role'] === 'super_admin') {
    header('Location: admin_dashboard.php');
    exit;
}
if ($_SESSION['role'] === 'event_admin') {
    header('Location: coordinator_dashboard.php');
    exit;
}
if ($_SESSION['role'] === 'security') {
    header('Location: security_dashboard.php');
    exit;
}

// For students/externals, redirect to the premium student dashboard
if (in_array($_SESSION['role'], ['internal', 'external'])) {
    header('Location: student_dashboard.php');
    exit;
}

// This code should never execute, but fallback just in case
header('Location: user_login.php');
exit;
?>