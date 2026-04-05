<?php // index.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config/auth.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user']['id']);
}

if (!isLoggedIn()) {
    // Not logged in → show login
    header('Location: controllers/login.php');
    exit;
}

// Logged in → route by role
$role = strtolower(trim(current_user()['role'] ?? 'student'));
switch ($role) {
    case 'organizer':
        header('Location: pages/organizer/organizerDashboard.php');
        break;
    case 'admin':
        header('Location: pages/admin/adminDashboard.php');
        break;
    default:
        header('Location: pages/student/studentDashboard.php');
        break;
}
exit;

// If not logged in, show login page
header("Location: /ecotrack/controllers/login.php");
exit;