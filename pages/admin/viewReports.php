<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/dbConnect.php';
require_role('admin');

$username = $_SESSION['user']['name'] ?? 'Administrator';

// Available reports
$reports = [
    [
        'id' => 0,
        'name' => 'User Activity Report',
        'summary' => 'Total users, submissions, and points earned',
        'icon' => '👥'
    ],
    [
        'id' => 1,
        'name' => 'Eco-Actions Report',
        'summary' => 'Most popular activities and completion rates',
        'icon' => '🌱'
    ],
    [
        'id' => 2,
        'name' => 'Challenge Participation',
        'summary' => 'Challenge engagement and completion statistics',
        'icon' => '🏆'
    ],
    [
        'id' => 3,
        'name' => 'Monthly Summary',
        'summary' => 'Month-over-month growth and trends',
        'icon' => '📊'
    ],
    [
        'id' => 4,
        'name' => 'Leaderboard Report',
        'summary' => 'Top performers and point distribution',
        'icon' => '⭐'
    ]
];

// Get selected report
$selected_report = isset($_GET['report']) ? (int)$_GET['report'] : 0;

// Fetch data for selected report
$report_data = [];
$report_columns = [];

switch($selected_report) {
    case 0: // User Activity Report
        $sql = "SELECT u.name, u.email, u.role, 
                COUNT(ual.log_id) as activities,
                COALESCE(SUM(ual.points_earned), 0) as total_points
                FROM users u
                LEFT JOIN user_activity_log ual ON u.user_id = ual.user_id
                GROUP BY u.user_id
                ORDER BY total_points DESC";
        $report_columns = ['Name', 'Email', 'Role', 'Activities', 'Total Points'];
        break;
        
    case 1: // Eco-Actions Report
        $sql = "SELECT ea.activity_name as 'Activity Name', 
                ea.points_awarded as 'Base Points',
                COUNT(sp.proof_id) as 'Total Submissions',
                SUM(CASE WHEN sp.status='approved' THEN 1 ELSE 0 END) as 'Approved'
                FROM eco_activities ea
                LEFT JOIN submission_proof sp ON ea.activity_id = sp.activity_id
                GROUP BY ea.activity_id
                ORDER BY COUNT(sp.proof_id) DESC";
        $report_columns = ['Activity Name', 'Base Points', 'Total Submissions', 'Approved'];
        break;
        
    case 2: // Challenge Participation
        $sql = "SELECT wc.title as 'Challenge', 
                wc.bonus_points as 'Bonus Points',
                COUNT(DISTINCT sp.user_id) as 'Participants',
                COUNT(sp.proof_id) as 'Submissions'
                FROM weekly_challenges wc
                LEFT JOIN submission_proof sp ON wc.challenge_id = sp.challenge_id
                GROUP BY wc.challenge_id
                ORDER BY COUNT(DISTINCT sp.user_id) DESC";
        $report_columns = ['Challenge', 'Bonus Points', 'Participants', 'Submissions'];
        break;
        
    case 3: // Monthly Summary
        $sql = "SELECT 
                DATE_FORMAT(ual.logged_at, '%Y-%m') as 'Month',
                COUNT(DISTINCT ual.user_id) as 'Active Users',
                COUNT(ual.log_id) as 'Total Activities',
                SUM(ual.points_earned) as 'Points Awarded'
                FROM user_activity_log ual
                GROUP BY DATE_FORMAT(ual.logged_at, '%Y-%m')
                ORDER BY Month DESC
                LIMIT 12";
        $report_columns = ['Month', 'Active Users', 'Total Activities', 'Points Awarded'];
        break;
        
    case 4: // Leaderboard Report
        $sql = "SELECT u.name as 'Name',
                u.role as 'Role',
                COUNT(ual.log_id) as 'Activities',
                SUM(ual.points_earned) as 'Total Points'
                FROM users u
                JOIN user_activity_log ual ON u.user_id = ual.user_id
                GROUP BY u.user_id
                ORDER BY SUM(ual.points_earned) DESC
                LIMIT 50";
        $report_columns = ['Name', 'Role', 'Activities', 'Total Points'];
        break;
}

$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }
}

include __DIR__ . '/../../assets/adminHeader.php';
?>

<style>
.two-column-layout {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}
.report-list {
    display: grid;
    gap: var(--space-2);
}
.report-item {
    padding: var(--space-3);
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: var(--space-2);
}
.report-item:hover {
    border-color: var(--color-primary);
    transform: translateX(4px);
}
.report-item.selected {
    border-color: var(--color-primary);
    background: var(--color-primary-soft);
}
.report-icon {
    font-size: 2rem;
    flex-shrink: 0;
}
.report-info {
    flex: 1;
}
.report-name {
    font-weight: 600;
    margin-bottom: var(--space-1);
}
.report-summary {
    font-size: var(--fs-sm);
    color: var(--color-text-muted);
}
.export-section {
    margin-top: var(--space-4);
    display: flex;
    justify-content: flex-end;
}
@media (max-width: 960px) {
    .two-column-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<h1 class="h1">Analytics & Reports</h1>

<div class="two-column-layout">
    <!-- Left: Report List -->
    <div>
        <div class="card">
            <h2 class="h3" style="margin-bottom: var(--space-3);">Available Reports</h2>
            <div class="report-list">
                <?php foreach($reports as $report): ?>
                <div class="report-item <?= $selected_report === $report['id'] ? 'selected' : '' ?>" 
                     onclick="location.href='viewReports.php?report=<?= $report['id'] ?>'">
                    <div class="report-icon"><?= $report['icon'] ?></div>
                    <div class="report-info">
                        <div class="report-name"><?= htmlspecialchars($report['name']) ?></div>
                        <div class="report-summary"><?= htmlspecialchars($report['summary']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Report View -->
    <div>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                <h2 class="h3" style="margin: 0;">
                    <?= $reports[$selected_report]['icon'] ?> 
                    <?= htmlspecialchars($reports[$selected_report]['name']) ?>
                </h2>
                <button class="button" onclick="exportReport()">📊 Export CSV</button>
            </div>

            <?php if (!empty($report_data)): ?>
                <div style="overflow-x: auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--color-border-strong);">
                                <?php foreach(array_keys($report_data[0]) as $header): ?>
                                <th style="padding: var(--space-2); text-align: left;">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $header))) ?>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($report_data as $row): ?>
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <?php foreach($row as $key => $value): ?>
                                <td style="padding: var(--space-2);">
                                    <?php 
                                    // Format numbers with commas
                                    if (is_numeric($value) && strpos($key, 'point') !== false) {
                                        echo '<strong>' . number_format($value) . '</strong>';
                                    } elseif (is_numeric($value)) {
                                        echo number_format($value);
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: var(--space-3); padding: var(--space-3); background: var(--color-elevated); border-radius: var(--radius-md);">
                    <p style="margin: 0; font-size: var(--fs-sm); color: var(--color-text-muted);">
                        <strong>Total Records:</strong> <?= count($report_data) ?> | 
                        <strong>Generated:</strong> <?= date('M d, Y h:i A') ?>
                    </p>
                </div>
            <?php else: ?>
                <div style="padding: var(--space-5); text-align: center; color: var(--color-text-muted);">
                    <p>No data available for this report.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportReport() {
    // Create CSV content
    const reportId = <?= $selected_report ?>;
    const reportName = <?= json_encode($reports[$selected_report]['name']) ?>;
    
    // Get table data
    const table = document.querySelector('table');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    let csv = [];
    
    // Add report title
    csv.push([reportName]);
    csv.push(['Generated: ' + new Date().toLocaleString()]);
    csv.push([]); // Empty row
    
    // Add headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers);
    
    // Add data rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push(td.textContent.trim());
        });
        csv.push(row);
    });
    
    // Convert to CSV string
    const csvContent = csv.map(row => 
        row.map(cell => `"${cell}"`).join(',')
    ).join('\n');
    
    // Download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `ecotrack_${reportName.toLowerCase().replace(/\s+/g, '_')}_${Date.now()}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include __DIR__ . '/../../assets/footer.php'; ?>