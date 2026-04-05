<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require __DIR__ . '/../../config/dbConnect.php';
require_once __DIR__ . '/badgeChecker.php';
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
$currentPoints = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Check and award any earned badges the user may be missing
checkAndAwardBadges($conn, $userId);

$allBadgesResult = $conn->query("SELECT * FROM badges ORDER BY points_required ASC");
$sqlUserBadges = "SELECT badge_id, earned_at FROM user_badges WHERE user_id = ?";
$stmt = $conn->prepare($sqlUserBadges);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userBadgesResult = $stmt->get_result();

$earnedBadges = [];
while ($row = $userBadgesResult->fetch_assoc()) { $earnedBadges[$row['badge_id']] = $row['earned_at']; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Badges - EcoTrack</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .badge-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .badge-card { background: white; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s; display: flex; flex-direction: column; justify-content: space-between; }
        .badge-card:hover { transform: translateY(-5px); }
        .badge-icon { margin-bottom: 15px; display: flex; justify-content: center; align-items: center; height: 80px; }
        .badge-card.locked .badge-icon { filter: grayscale(100%); opacity: 0.5; }
        .badge-card h3 { margin: 10px 0 5px 0; font-size: 1.1em; }
        .badge-card p { font-size: 0.9em; color: #777; margin-bottom: 15px; }
        .progress-track { background-color: #eee; border-radius: 10px; height: 8px; width: 100%; margin-top: 10px; overflow: hidden; }
        .progress-fill { background-color: #2ecc71; height: 100%; border-radius: 10px; transition: width 0.5s ease; }
        .progress-text { font-size: 0.8em; color: #888; margin-top: 5px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="overlay" onclick="toggleSidebar()"></div>

    <nav class="sidebar">
        <div class="logo"><i class="fa-solid fa-leaf"></i> EcoTrack</div>
        <ul>
            <li><a href="studentDashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="leaderboard.php"><i class="fa-solid fa-trophy"></i> Leaderboard</a></li>
            <li><a href="submitAction.php"><i class="fa-solid fa-camera"></i> Log Activity</a></li>
            <li><a href="myBadges.php" class="active"><i class="fa-solid fa-medal"></i> My Badges</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <button class="menu-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>

        <header>
            <h1>My Achievements 🏅</h1>
            <p>Current Points: <strong><?php echo $currentPoints; ?></strong></p>
        </header>

        <section class="badge-grid">
            <?php while($badge = $allBadgesResult->fetch_assoc()) { 
                $isEarned = array_key_exists($badge['badge_id'], $earnedBadges);
                $cardClass = $isEarned ? 'unlocked' : 'locked';
                $required = $badge['points_required'];
                $percent = ($currentPoints > 0 && $required > 0) ? min(($currentPoints / $required) * 100, 100) : 0;
            ?>
                <div class="badge-card <?php echo $cardClass; ?>">
                    <div>
                        <div class="badge-icon">
                            <?php 
                            // Get just the filename from the database to avoid path conflicts
                            $fileName = basename($badge['icon_path']);
                            $webPath = "/ecotrack/assets/badges/" . $fileName;
                            
                            // Check local server path for existence
                            $serverPath = $_SERVER['DOCUMENT_ROOT'] . $webPath;

                            if (!empty($fileName) && file_exists($serverPath)) { 
                                echo '<img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($badge['name']) . '" style="width: 80px; height: 80px; object-fit: contain;">';
                            } else { 
                                // Fallback icon if file is missing or path is wrong
                                echo '<i class="fa-solid fa-medal" style="font-size: 60px; color: #f1c40f;"></i>'; 
                            } 
                            ?>
                        </div>
                        <h3><?php echo htmlspecialchars($badge['name']); ?></h3>
                        <p><?php echo htmlspecialchars($badge['description']); ?></p>
                    </div>
                    <div>
                        <?php if ($isEarned) { ?>
                            <div style="background: #e8f8f5; padding: 8px; border-radius: 5px; color: #27ae60; font-weight: bold; font-size: 0.9em;">
                                <i class="fa-solid fa-check-circle"></i> Earned on <?php echo date('d M', strtotime($earnedBadges[$badge['badge_id']])); ?>
                            </div>
                        <?php } else { ?>
                            <div class="progress-track"><div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div></div>
                            <div class="progress-text"><?php echo $currentPoints; ?> / <?php echo $required; ?> pts</div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </section>
    </main>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.overlay').classList.toggle('active');
        }
    </script>
</body>
</html>