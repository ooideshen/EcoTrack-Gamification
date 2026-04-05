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

$activitiesResult = $conn->query("SELECT * FROM eco_activities");
$today = date('Y-m-d');
$challengesSql = "SELECT * FROM weekly_challenges WHERE '$today' BETWEEN start_date AND end_date";
$challengesResult = $conn->query($challengesSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Activity - EcoTrack</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        label { font-weight: bold; display: block; margin-top: 15px; }
        select, input[type="text"], input[type="number"], textarea, input[type="file"] {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-family: inherit; font-size: 14px;
        }
        .btn-submit { background-color: #2ecc71; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; margin-top: 20px; font-size: 16px; }
        .btn-submit:hover { background-color: #27ae60; }
    </style>
</head>
<body>

    <div class="overlay" onclick="toggleSidebar()"></div>

    <nav class="sidebar">
        <div class="logo"><i class="fa-solid fa-leaf"></i> EcoTrack</div>
        <ul>
            <li><a href="studentDashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="leaderboard.php"><i class="fa-solid fa-trophy"></i> Leaderboard</a></li>
            <li><a href="submitAction.php" class="active"><i class="fa-solid fa-camera"></i> Log Activity</a></li>
            <li><a href="myBadges.php"><i class="fa-solid fa-medal"></i> My Badges</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <button class="menu-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>

        <header><h1>Log New Activity 📸</h1></header>

        <div class="form-container">
            <?php if(isset($_GET['msg'])) echo "<p style='color: green; text-align: center; font-weight: bold;'>" . htmlspecialchars($_GET['msg']) . "</p>"; ?>
            <?php if(isset($_GET['error'])) echo "<p style='color: red; text-align: center;'>" . htmlspecialchars($_GET['error']) . "</p>"; ?>

            <form action="submitProcess.php" method="POST" enctype="multipart/form-data">
                <label>Select Activity</label>
                <select name="activity_id" required>
                    <option value="">-- Choose an action --</option>
                    <?php while($row = $activitiesResult->fetch_assoc()) { ?>
                        <option value="<?php echo $row['activity_id']; ?>"><?php echo $row['activity_name']; ?> (+<?php echo $row['points_awarded']; ?> pts/unit)</option>
                    <?php } ?>
                </select>

                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>Quantity / Amount</label>
                        <input type="number" name="quantity" min="1" value="1" required>
                    </div>
                    <div style="flex: 2;">
                        <label>Unit (Reference)</label>
                        <input type="text" disabled placeholder="items / km / hours" style="background: #f9f9f9;">
                    </div>
                </div>

                <label>Link to Challenge (Optional)</label>
                <select name="challenge_id">
                    <option value="">-- No Challenge --</option>
                    <?php if ($challengesResult->num_rows > 0) {
                        while($row = $challengesResult->fetch_assoc()) { ?>
                            <option value="<?php echo $row['challenge_id']; ?>"><?php echo $row['title']; ?> (Bonus +<?php echo $row['bonus_points']; ?>)</option>
                    <?php } } else { echo "<option disabled>No active challenges</option>"; } ?>
                </select>

                <label>Upload Proof (Photo)</label>
                <input type="file" name="proof_photo" accept="image/*" required>

                <label>Notes / Description</label>
                <textarea name="notes" rows="3" placeholder="E.g., I walked 5km to campus today..."></textarea>

                <button type="submit" class="btn-submit">Submit for Verification</button>
            </form>
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