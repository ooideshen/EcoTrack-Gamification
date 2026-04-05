<?php
// Determine the current script name (e.g., "createChallenge.php")
$current = basename($_SERVER['PHP_SELF']);

// Helper to echo 'active' for the matched href
function nav_active(string $hrefBase, string $current): string {
    return ($hrefBase === $current) ? ' active' : '';
}

// Get organizer name helper
function organizer_display_name(mysqli $conn): string {
    $uid = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? null);
    if (!$uid) return 'Organizer';
    
    $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    
    return $row['name'] ?? 'Organizer';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>EcoTrack – Organizer Dashboard</title>

    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.8.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- Top Navigation -->
    <header class="header" role="banner">
        <div class="container header-bar">
            <strong class="brand">EcoTrack • Organizer</strong>

            <!-- Hamburger (visible on small screens) -->
            <button
                class="nav-toggle"
                type="button"
                aria-label="Open menu"
                aria-expanded="false"
                aria-controls="primary-nav"
            >
                <span class="nav-toggle-box" aria-hidden="true">
                    <span class="nav-toggle-bar"></span>
                    <span class="nav-toggle-bar"></span>
                    <span class="nav-toggle-bar"></span>
                </span>
            </button>

            <!-- Collapsible nav -->
            <nav id="primary-nav" class="topnav" aria-label="Primary">
                <a href="organizerDashboard.php" class="nav-link <?= nav_active('organizerDashboard.php', $current) ?>">Dashboard</a>
                <a href="createChallenge.php" class="nav-link <?= nav_active('createChallenge.php', $current) ?>">Create Challenge</a>
                <a href="verifySubmissions.php" class="nav-link <?= nav_active('verifySubmissions.php', $current) ?>">Verify Submissions</a>
                <a href="postAnnouncement.php" class="nav-link <?= nav_active('postAnnouncement.php', $current) ?>">Post News/Events</a>
                <a href="analyzePerformance.php" class="nav-link <?= nav_active('analyzePerformance.php', $current) ?>">Analyze Performance</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Hamburger Menu Toggle Script -->
    <script>
        (function () {
            const toggleBtn = document.querySelector('.nav-toggle');
            const nav = document.getElementById('primary-nav');

            if (!toggleBtn || !nav) return;

            const openMenu = () => {
                nav.classList.add('is-open');
                toggleBtn.setAttribute('aria-expanded', 'true');
                toggleBtn.setAttribute('aria-label', 'Close menu');
                // move focus to first link for accessibility
                const firstLink = nav.querySelector('a');
                if (firstLink) firstLink.focus();
                document.addEventListener('keydown', onKeyDown);
                document.addEventListener('click', onDocClick, {capture:true});
            };

            const closeMenu = () => {
                nav.classList.remove('is-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.setAttribute('aria-label', 'Open menu');
                document.removeEventListener('keydown', onKeyDown);
                document.removeEventListener('click', onDocClick, {capture:false});
                toggleBtn.focus();
            };

            const onKeyDown = (e) => {
                if (e.key === 'Escape') closeMenu();
            };

            const onDocClick = (e) => {
                if (!nav.contains(e.target) && !toggleBtn.contains(e.target)) {
                    closeMenu();
                }
            };

            toggleBtn.addEventListener('click', () => {
                const isOpen = nav.classList.contains('is-open');
                if (isOpen) closeMenu(); else openMenu();
            });
        })();
    </script>
    
    <main class="container">