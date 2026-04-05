<?php  // analyzePerformance.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/dbConnect.php';
require_role('organizer'); // or ['organizer','admin'] for shared pages
require_once __DIR__ . '/organizerData.php'; // initializes session data arrays

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }


// Fetch top participants by total points
$topParticipantsQuery = "
    SELECT 
        u.user_id,
        u.name,
        u.email,
        SUM(ual.points_earned) as total_points,
        COUNT(ual.log_id) as total_activities
    FROM users u
    JOIN user_activity_log ual ON ual.user_id = u.user_id
    GROUP BY u.user_id
    ORDER BY total_points DESC, u.name ASC
";
$topParticipantsResult = $conn->query($topParticipantsQuery);
$topParticipants = $topParticipantsResult ? $topParticipantsResult->fetch_all(MYSQLI_ASSOC) : [];

// Fetch top participants by total points
$topParticipantsQuery = "
    SELECT 
        u.user_id,
        u.name,
        u.email,
        SUM(ual.points_earned) as total_points,
        COUNT(ual.log_id) as total_activities
    FROM users u
    JOIN user_activity_log ual ON ual.user_id = u.user_id
    GROUP BY u.user_id
    ORDER BY total_points DESC, u.name ASC
";
$topParticipantsResult = $conn->query($topParticipantsQuery);
$topParticipants = $topParticipantsResult ? $topParticipantsResult->fetch_all(MYSQLI_ASSOC) : [];

// Fetch activity breakdown (which activities are most popular)
$activityBreakdownQuery = "
    SELECT
        ea.activity_id,
        ea.activity_name,
        COUNT(ual.log_id) AS times_completed,
        SUM(ual.points_earned) AS total_points_given
    FROM eco_activities ea
    LEFT JOIN user_activity_log ual ON ual.activity_id = ea.activity_id
    GROUP BY ea.activity_id
    ORDER BY times_completed DESC
";
$activityBreakdownResult = $conn->query($activityBreakdownQuery);
$activityBreakdown = $activityBreakdownResult ? $activityBreakdownResult->fetch_all(MYSQLI_ASSOC) : [];

// Calculate overall statistics
$overallStatsQuery = "
    SELECT
        COUNT(DISTINCT u.user_id) as total_users,
        COUNT(DISTINCT ual.log_id) as total_activities_completed,
        SUM(ual.points_earned) as total_points_distributed,
        COUNT(DISTINCT wc.challenge_id) as total_challenges
    FROM users u
    LEFT JOIN user_activity_log ual ON ual.user_id = u.user_id
    CROSS JOIN weekly_challenges wc
";
$overallStatsResult = $conn->query($overallStatsQuery);
$overallStats = $overallStatsResult ? $overallStatsResult->fetch_assoc() : [];

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ecotrack_performance_' . date('Y-m-d') . '.csv');

    $out = fopen('php://output', 'w');

    // Export Top Participants
    fputcsv($out, ['TOP PARTICIPANTS']);
    fputcsv($out, ['User ID', 'Name', 'Email', 'Total Points', 'Total Activities']);
    foreach ($topParticipants as $row) {
        fputcsv($out, [
            $row['user_id'],
            $row['name'],
            $row['email'],
            $row['total_points'],
            $row['total_activities']
        ]);
    }

    fputcsv($out, []); // spacer

    // Export Challenge Trends
    fputcsv($out, ['CHALLENGE TRENDS']);
    fputcsv($out, ['Challenge ID', 'Title', 'Start Date', 'End Date', 'Total Submissions', 'Approved', 'Pending', 'Rejected', 'Points Awarded']);
    foreach ($challengeTrends as $t) {
        fputcsv($out, [
            $t['challenge_id'],
            $t['title'],
            $t['start_date'],
            $t['end_date'],
            $t['total_submissions'],
            $t['approved_count'],
            $t['pending_count'],
            $t['rejected_count'],
            $t['total_points_awarded']
        ]);
    }

    fputcsv($out, []); // spacer

    // Export Activity Breakdown
    fputcsv($out, ['ACTIVITY BREAKDOWN']);
    fputcsv($out, ['Activity ID', 'Activity Name', 'Times Completed', 'Total Points Given']);
    foreach ($activityBreakdown as $a) {
        fputcsv($out, [
            $a['activity_id'],
            $a['activity_name'],
            $a['times_completed'],
            $a['total_points_given']
        ]);
    }

    fclose($out);
    exit;
}

include __DIR__ . '/../../assets/header.php';
?>

<h1 class="h1">Analyze Challenge Performance</h1>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: var(--space-4);">
    <p style="margin:0; color: var(--color-text-muted);">Overview of participation, challenges, and activities</p>
    <a href="analyzePerformance.php?export=csv" class="button">📊 Export to CSV</a>
</div>

<!-- Overall Statistics Cards -->
<h2 class="h2">Overall Statistics</h2>
<div class="grid cols-2 md:cols-4 gap-3" style="margin-bottom: var(--space-5);">
    <article class="card">
        <div class="h5" style="color: var(--color-text-muted);">Total Users</div>
        <div class="text-3xl" style="font-weight:800; margin-top: var(--space-1);">
            <?= h((string)($overallStats['total_users'] ?? 0)) ?>
        </div>
    </article>
    
    <article class="card">
        <div class="h5" style="color: var(--color-text-muted);">Activities Completed</div>
        <div class="text-3xl" style="font-weight:800; margin-top: var(--space-1);">
            <?= h((string)($overallStats['total_activities_completed'] ?? 0)) ?>
        </div>
    </article>
    
    <article class="card">
        <div class="h5" style="color: var(--color-text-muted);">Points Distributed</div>
        <div class="text-3xl" style="font-weight:800; margin-top: var(--space-1); color: var(--color-success);">
            <?= h((string)($overallStats['total_points_distributed'] ?? 0)) ?>
        </div>
    </article>
    
    <article class="card">
        <div class="h5" style="color: var(--color-text-muted);">Total Challenges</div>
        <div class="text-3xl" style="font-weight:800; margin-top: var(--space-1);">
            <?= h((string)($overallStats['total_challenges'] ?? 0)) ?>
        </div>
    </article>
</div>

<!-- Top Participants -->
<h2 class="h2">Top Participants</h2>
<?php if (empty($topParticipants)): ?>
  <div class="card">
    <p style="margin:0; color: var(--color-text-muted);">No participant data available yet.</p>
  </div>
<?php else: ?>
    <div class="card" style="overflow-x: auto;">
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-border-strong);">
                    <th style="padding: var(--space-2); text-align: left;">Rank</th>
                    <th style="padding: var(--space-2); text-align: left;">User ID</th>
                    <th style="padding: var(--space-2); text-align: left;">Name</th>
                    <th style="padding: var(--space-2); text-align: left;">Email</th>
                    <th style="padding: var(--space-2); text-align: right;">Total Points</th>
                    <th style="padding: var(--space-2); text-align: right;">Activities</th>
                </tr>
            </thead>
            <tbody>
            <?php $rank = 1; foreach ($topParticipants as $p): ?>
                <tr style="border-bottom: 1px solid var(--color-border);">
                    <td style="padding: var(--space-2);"><strong><?= $rank++ ?></strong></td>
                    <td style="padding: var(--space-2);"><?= h((string)$p['user_id']) ?></td>
                    <td style="padding: var(--space-2);"><?= h($p['name']) ?></td>
                    <td style="padding: var(--space-2); font-size: var(--fs-sm);"><?= h($p['email']) ?></td>
                    <td style="padding: var(--space-2); text-align: right;"><strong><?= h((string)$p['total_points']) ?></strong></td>
                    <td style="padding: var(--space-2); text-align: right;"><?= h((string)$p['total_activities']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="card" style="margin-top: var(--space-4);">
    <h3 class="h4">Participation Distribution</h3>
    <canvas id="activityChart" style="max-height: 300px;"></canvas>
</div>

<!-- Challenge Trends -->
<h2 class="h2" style="margin-top: var(--space-5);">Challenge Trends</h2>
<?php if (empty($challengeTrends)): ?>
  <div class="card">
    <p style="margin:0; color: var(--color-text-muted);">No challenge data available yet.</p>
  </div>
<?php else: ?>
    <div class="card" style="overflow-x: auto;">
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-border-strong);">
                    <th style="padding: var(--space-2); text-align: left;">ID</th>
                    <th style="padding: var(--space-2); text-align: left;">Challenge Title</th>
                    <th style="padding: var(--space-2); text-align: left;">Duration</th>
                    <th style="padding: var(--space-2); text-align: center;">Total</th>
                    <th style="padding: var(--space-2); text-align: center;">✓ Approved</th>
                    <th style="padding: var(--space-2); text-align: center;">⏳ Pending</th>
                    <th style="padding: var(--space-2); text-align: center;">✗ Rejected</th>
                    <th style="padding: var(--space-2); text-align: right;">Points</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($challengeTrends as $c): ?>
                <tr style="border-bottom: 1px solid var(--color-border);">
                    <td style="padding: var(--space-2);"><?= h((string)$c['challenge_id']) ?></td>
                    <td style="padding: var(--space-2);"><strong><?= h($c['title']) ?></strong></td>
                    <td style="padding: var(--space-2); font-size: var(--fs-sm);">
                        <?= h(date('M j', strtotime($c['start_date']))) ?> - <?= h(date('M j, Y', strtotime($c['end_date']))) ?>
                    </td>
                    <td style="padding: var(--space-2); text-align: center;"><?= h((string)$c['total_submissions']) ?></td>
                    <td style="padding: var(--space-2); text-align: center; color: var(--color-success);">
                        <strong><?= h((string)$c['approved_count']) ?></strong>
                    </td>
                    <td style="padding: var(--space-2); text-align: center; color: var(--color-warning);">
                        <?= h((string)$c['pending_count']) ?>
                    </td>
                    <td style="padding: var(--space-2); text-align: center; color: var(--color-error);">
                        <?= h((string)$c['rejected_count']) ?>
                    </td>
                    <td style="padding: var(--space-2); text-align: right;"><strong><?= h((string)$c['total_points_awarded']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Activity Breakdown -->
<h2 class="h2" style="margin-top: var(--space-5);">Activity Breakdown</h2>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('activityChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($activityBreakdown, 'activity_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($activityBreakdown, 'times_completed')) ?>,
                backgroundColor: ['#15803D', '#2C7A7B', '#1E4DB7', '#B7791F', '#B91C1C']
            }]
        }
    });
</script>

<?php if (empty($activityBreakdown)): ?>
  <div class="card">
    <p style="margin:0; color: var(--color-text-muted);">No activity data available yet.</p>
  </div>
<?php else: ?>
    <div class="grid cols-1 md:cols-2 lg:cols-3 gap-3">
    <?php foreach ($activityBreakdown as $a): ?>
        <article class="card">
            <div class="h4"><?= h($a['activity_name']) ?></div>
            <div style="display:flex; justify-content:space-between; margin-top: var(--space-2); font-size: var(--fs-sm); color: var(--color-text-muted);">
                <span>Completed: <strong><?= h((string)$a['times_completed']) ?>×</strong></span>
                <span>Points: <strong style="color: var(--color-success);"><?= h((string)$a['total_points_given']) ?></strong></span>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<div style="margin-top: var(--space-4);">
  <a href="organizerDashboard.php" class="button">
    <i class="ri-arrow-left-line" aria-hidden="true"></i>
    Back to Dashboard
  </a>
</div>

<?php include __DIR__ . '/../../assets/footer.php'; ?>