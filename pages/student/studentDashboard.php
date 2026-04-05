<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require __DIR__ . '/../../config/dbConnect.php';
require_role('student');

$userId = $_SESSION['user']['id'] ?? null;
$userName = $_SESSION['user']['name'] ?? 'student';

if (!$userId) {
    header("Location: /ecotrack/controllers/login.php");
    exit();
}

$sqlPoints = "SELECT SUM(points_earned) as total FROM user_activity_log WHERE user_id = ?";
$stmt = $conn->prepare($sqlPoints);
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalPoints = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$co2Saved = round($totalPoints * 0.15, 2);

$sqlBadges = "SELECT COUNT(*) as count FROM user_badges WHERE user_id = ?";
$stmt = $conn->prepare($sqlBadges);
$stmt->bind_param("i", $userId);
$stmt->execute();
$badgeCount = $stmt->get_result()->fetch_assoc()['count'];

$weeklyLabels = []; $weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weeklyLabels[] = date('D', strtotime("-$i days")); 
    $sqlChart = "SELECT SUM(points_earned) as daily_total FROM user_activity_log WHERE user_id = ? AND DATE(logged_at) = ?";
    $stmt = $conn->prepare($sqlChart);
    $stmt->bind_param("is", $userId, $date);
    $stmt->execute();
    $weeklyData[] = $stmt->get_result()->fetch_assoc()['daily_total'] ?? 0;
}

$monthlyLabels = []; $monthlyData = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $monthlyLabels[] = date('d M', strtotime("-$i days")); 
    $sqlChart = "SELECT SUM(points_earned) as daily_total FROM user_activity_log WHERE user_id = ? AND DATE(logged_at) = ?";
    $stmt = $conn->prepare($sqlChart);
    $stmt->bind_param("is", $userId, $date);
    $stmt->execute();
    $monthlyData[] = $stmt->get_result()->fetch_assoc()['daily_total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EcoTrack</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .chart-toggles { background: #f1f1f1; border-radius: 20px; padding: 3px; display: flex; }
        .chart-btn { border: none; background: transparent; padding: 5px 15px; border-radius: 15px; cursor: pointer; color: #666; font-weight: bold; transition: 0.3s; }
        .chart-btn.active { background: white; color: #2ecc71; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <!-- NEW: Overlay Element -->
    <div class="overlay" onclick="toggleSidebar()"></div>

    <nav class="sidebar">
        <div class="logo"><i class="fa-solid fa-leaf"></i> EcoTrack</div>
        <ul>
            <li><a href="studentDashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="leaderboard.php"><i class="fa-solid fa-trophy"></i> Leaderboard</a></li>
            <li><a href="submitAction.php"><i class="fa-solid fa-camera"></i> Log Activity</a></li>
            <li><a href="myBadges.php"><i class="fa-solid fa-medal"></i> My Badges</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <button class="menu-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>

        <header>
            <h1>Hello, <?php echo htmlspecialchars($userName); ?>! 👋</h1>
        </header>

        <section class="stats-grid">
            <div class="card green-card">
                <div class="icon-box"><i class="fa-solid fa-star"></i></div>
                <div><h3>Total Points</h3><p class="big-number"><?php echo $totalPoints; ?></p></div>
            </div>
            <div class="card blue-card">
                <div class="icon-box"><i class="fa-solid fa-cloud"></i></div>
                <div><h3>CO2 Saved</h3><p class="big-number"><?php echo $co2Saved; ?> kg</p></div>
            </div>
            <div class="card purple-card">
                <div class="icon-box"><i class="fa-solid fa-medal"></i></div>
                <div><h3>Badges</h3><p class="big-number"><?php echo $badgeCount; ?></p></div>
            </div>
        </section>

        <section class="chart-section" style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div class="chart-header">
                <h2 style="margin:0;">Progress Trends 📈</h2>
                <div class="chart-toggles">
                    <button class="chart-btn active" onclick="updateChart('weekly')" id="btnWeekly">Weekly</button>
                    <button class="chart-btn" onclick="updateChart('monthly')" id="btnMonthly">Monthly</button>
                </div>
            </div>
            <div style="height: 300px;">
                <canvas id="progressChart"></canvas>
            </div>
        </section>

        <section class="recent-activity">
            <h2>Recent History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Activity</th><th>Status</th><th>Points</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $sqlHistory = "SELECT sp.status, sp.submitted_at, ea.activity_name, ual.points_earned, ea.points_awarded
                                       FROM submission_proof sp
                                       JOIN eco_activities ea ON sp.activity_id = ea.activity_id
                                       LEFT JOIN user_activity_log ual ON sp.proof_id = ual.proof_id
                                       WHERE sp.user_id = ? ORDER BY sp.submitted_at DESC LIMIT 5";
                        $stmt = $conn->prepare($sqlHistory);
                        $stmt->bind_param("i", $userId);
                        $stmt->execute();
                        $history = $stmt->get_result();

                        if ($history->num_rows > 0) {
                            while($row = $history->fetch_assoc()) {
                                $statusColor = ($row['status'] == 'approved') ? 'green' : (($row['status'] == 'rejected') ? 'red' : 'orange');
                                $pointsDisplay = ($row['status'] == 'approved') ? "+".$row['points_earned'] : (($row['status'] == 'rejected') ? '0' : "+".$row['points_awarded']."?");
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['activity_name']); ?></td>
                                    <td><span style="color: <?php echo $statusColor; ?>; font-weight: bold; padding: 5px 10px; background: rgba(0,0,0,0.05); border-radius: 5px;"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php echo $pointsDisplay; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='4'>No activities yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        // UPDATED FUNCTION: Toggles both Sidebar and Overlay
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.overlay').classList.toggle('active');
        }

        const ctx = document.getElementById('progressChart').getContext('2d');
        const weeklyLabels = <?php echo json_encode($weeklyLabels); ?>;
        const weeklyData = <?php echo json_encode($weeklyData); ?>;
        const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
        const monthlyData = <?php echo json_encode($monthlyData); ?>;

        let myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: weeklyLabels,
                datasets: [{
                    label: 'Points Earned',
                    data: weeklyData,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4 
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });

        function updateChart(period) {
            const btnWeekly = document.getElementById('btnWeekly');
            const btnMonthly = document.getElementById('btnMonthly');
            if (period === 'weekly') {
                myChart.data.labels = weeklyLabels;
                myChart.data.datasets[0].data = weeklyData;
                btnWeekly.classList.add('active');
                btnMonthly.classList.remove('active');
            } else {
                myChart.data.labels = monthlyLabels;
                myChart.data.datasets[0].data = monthlyData;
                btnMonthly.classList.add('active');
                btnWeekly.classList.remove('active');
            }
            myChart.update();
        }
    </script>
</body>
</html>