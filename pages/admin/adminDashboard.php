<?php
session_start();
require_once __DIR__ . '/../../config/dbConnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../controllers/login.php?error=Access denied');
    exit;
}

$username = $_SESSION['user']['name'] ?? 'Administrator';

function get_stat_count($conn, $query) {
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_row()) {
        return $row[0];
    }
    return 0; 
}

$userCount          = get_stat_count($conn, "SELECT COUNT(*) FROM users");
$challengeCount     = get_stat_count($conn, "SELECT COUNT(*) FROM weekly_challenges");
$pendingSubmissions = get_stat_count($conn, "SELECT COUNT(*) FROM user_activity_log");

include __DIR__ . '/../../assets/adminHeader.php';
?>
<section style="margin-bottom: var(--space-5);">
    <h1 class="h2">Welcome back, <?= htmlspecialchars($_SESSION['name']); ?></h1>
    <p class="text-muted">Here is today's overview of the EcoTrack platform.</p>
</section>

<section style="margin-bottom: var(--space-6);">
    <h2 class="h4" style="margin-bottom: var(--space-3);">Platform Metrics</h2>
    <div class="grid md:cols-3 gap-3">
        
        <article class="card stat-card">
            <div class="stat-icon bg-primary-soft text-primary">
                <i class="ri-user-smile-line"></i>
            </div>
            <div>
                <p class="text-sm text-muted" style="margin:0;">Total Users</p>
                <p class="h2" style="margin:0;"><?= $userCount ?></p>
            </div>
        </article>

        <article class="card stat-card">
            <div class="stat-icon bg-accent-soft text-accent">
                <i class="ri-flag-line"></i>
            </div>
            <div>
                <p class="text-sm text-muted" style="margin:0;">Total Challenges</p>
                <p class="h2" style="margin:0;"><?= $challengeCount ?></p>
            </div>
        </article>

        <article class="card stat-card">
            <div class="stat-icon bg-warning-soft text-warning">
                <i class="ri-time-line"></i>
            </div>
            <div>
                <p class="text-sm text-muted" style="margin:0;">Pending Actions</p>
                <p class="h2" style="margin:0;"><?= $pendingSubmissions ?></p>
            </div>
        </article>
    </div>
</section>


<section style="margin-bottom: var(--space-6);">
    <h2 class="h4" style="margin-bottom: var(--space-3);">Quick Actions</h2>
    <div class="grid md:cols-2 gap-3">

        <a href="manageAccounts.php" class="card action-card">
            <div class="action-icon">
                <i class="ri-user-settings-line"></i>
            </div>
            <div style="flex: 1;">
                <h3 class="h5" style="margin:0;">Manage Accounts</h3>
                <p class="text-sm text-muted" style="margin:0;">View, edit, or suspend user accounts.</p>
            </div>
            <i class="ri-arrow-right-s-line chevron"></i>
        </a>

        <a href="manageChallenges.php" class="card action-card">
            <div class="action-icon">
                <i class="ri-trophy-line"></i>
            </div>
            <div style="flex: 1;">
                <h3 class="h5" style="margin:0;">Manage Challenges</h3>
                <p class="text-sm text-muted" style="margin:0;">Create or modify weekly tasks.</p>
            </div>
            <i class="ri-arrow-right-s-line chevron"></i>
        </a>

        <a href="viewSubmissions.php" class="card action-card">
            <div class="action-icon">
                <i class="ri-checkbox-multiple-line"></i>
            </div>
            <div style="flex: 1;">
                <h3 class="h5" style="margin:0;">Verify Submissions</h3>
                <p class="text-sm text-muted" style="margin:0;">Review pending user activities.</p>
            </div>
            <i class="ri-arrow-right-s-line chevron"></i>
        </a>

        <a href="viewReports.php" class="card action-card">
            <div class="action-icon">
                <i class="ri-file-warning-line"></i>
            </div>
            <div style="flex: 1;">
                <h3 class="h5" style="margin:0;">View Reports</h3>
                <p class="text-sm text-muted" style="margin:0;">Handle user complaints and issues.</p>
            </div>
            <i class="ri-arrow-right-s-line chevron"></i>
        </a>

    </div>
</section>
<?php include __DIR__ . '/../../assets/footer.php';