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

$timeFilter = isset($_GET['time']) ? $_GET['time'] : 'all'; 
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'points'; 

$whereClause = "";
$titleSuffix = "All Time";

if ($timeFilter == 'weekly') {
    $whereClause = "WHERE YEARWEEK(ual.logged_at, 1) = YEARWEEK(CURDATE(), 1)";
    $titleSuffix = "This Week";
}

$sql = "SELECT u.name, u.intake_code, SUM(ual.points_earned) as total_points
        FROM users u
        JOIN user_activity_log ual ON u.user_id = ual.user_id
        $whereClause
        GROUP BY u.user_id
        ORDER BY total_points DESC
        LIMIT 20";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - EcoTrack</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .leaderboard-container { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .rank-icon { font-size: 1.2em; }
        .gold { color: #f1c40f; } .silver { color: #95a5a6; } .bronze { color: #d35400; }
        tr:hover { background-color: #f9f9f9; }
        .highlight-me { background-color: #e8f8f5; font-weight: bold; border-left: 5px solid #2ecc71; }
        .filter-bar { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 10px; }
        .toggle-group { background: #f1f1f1; padding: 5px; border-radius: 25px; display: inline-flex; }
        .toggle-btn { padding: 8px 20px; border-radius: 20px; text-decoration: none; color: #666; font-weight: 500; font-size: 0.9em; transition: 0.3s; }
        .toggle-btn.active { background: #2ecc71; color: white; box-shadow: 0 2px 5px rgba(46, 204, 113, 0.3); }
        select { padding: 8px 15px; border-radius: 5px; border: 1px solid #ddd; background: #fff; color: #555; cursor: pointer; }
    </style>
</head>
<body>

    <div class="overlay" onclick="toggleSidebar()"></div>

    <nav class="sidebar">
        <div class="logo"><i class="fa-solid fa-leaf"></i> EcoTrack</div>
        <ul>
            <li><a href="studentDashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="leaderboard.php" class="active"><i class="fa-solid fa-trophy"></i> Leaderboard</a></li>
            <li><a href="submitAction.php"><i class="fa-solid fa-camera"></i> Log Activity</a></li>
            <li><a href="myBadges.php"><i class="fa-solid fa-medal"></i> My Badges</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <button class="menu-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>

        <header>
            <h1>Campus Leaderboard 🏆</h1>
            <p>Rankings for: <strong><?php echo $titleSuffix; ?></strong></p>
        </header>

        <div class="filter-bar">
            <div class="toggle-group">
                <a href="leaderboard.php?time=all&sort=<?php echo $sortBy; ?>" class="toggle-btn <?php echo ($timeFilter == 'all') ? 'active' : ''; ?>">All Time</a>
                <a href="leaderboard.php?time=weekly&sort=<?php echo $sortBy; ?>" class="toggle-btn <?php echo ($timeFilter == 'weekly') ? 'active' : ''; ?>">This Week</a>
            </div>

            <form method="GET" action="leaderboard.php" style="margin:0;">
                <input type="hidden" name="time" value="<?php echo $timeFilter; ?>">
                <label style="margin-right: 10px; font-weight:bold; color:#555;">Sort by:</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="points" <?php echo ($sortBy == 'points') ? 'selected' : ''; ?>>Total Points</option>
                    <option value="co2" <?php echo ($sortBy == 'co2') ? 'selected' : ''; ?>>CO2 Saved</option>
                </select>
            </form>
        </div>

        <div class="table-container">
            <div class="leaderboard-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 10%;">Rank</th>
                            <th>Student Name</th>
                            <th>Intake</th>
                            <th style="width: 20%; text-align: right;">
                                <?php echo ($sortBy == 'co2') ? 'CO2 Saved (kg)' : 'Total Points'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result->num_rows > 0) {
                            $rank = 1;
                            while($row = $result->fetch_assoc()) { 
                                $isMe = ($row['name'] === ($_SESSION['user']['name'] ?? '')) ? 'highlight-me' : '';
                                $displayValue = ($sortBy == 'co2') ? round($row['total_points'] * 0.15, 2) . " kg" : $row['total_points'] . " pts";
                                ?>
                                <tr class="<?php echo $isMe; ?>">
                                    <td><?php if ($rank == 1) echo '<i class="fa-solid fa-trophy rank-icon gold"></i>'; elseif ($rank == 2) echo '<i class="fa-solid fa-medal rank-icon silver"></i>'; elseif ($rank == 3) echo '<i class="fa-solid fa-medal rank-icon bronze"></i>'; else echo "#" . $rank; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><span style="font-size: 0.9em; color: #777;"><?php echo htmlspecialchars($row['intake_code']); ?></span></td>
                                    <td style="text-align: right; font-weight: bold; color: #2ecc71;"><?php echo $displayValue; ?></td>
                                </tr>
                            <?php $rank++; } 
                        } else { echo "<tr><td colspan='4' style='text-align:center; padding:20px;'>No data found.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.overlay').classList.toggle('active');
        }
    </script>
</body>
</html>