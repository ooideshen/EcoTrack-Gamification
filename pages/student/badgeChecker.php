<?php
// badgeChecker.php
declare(strict_types=1);

function checkAndAwardBadges($conn, $userId) { // CHANGED: $userId
    
    // 1. Get user's current total points
    $stmt = $conn->prepare("SELECT SUM(points_earned) as total FROM user_activity_log WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $totalPoints = $stmt->get_result()->fetch_assoc()['total'] ?? 0; // CHANGED

    // 2. Get list of badges the user does NOT have yet
    $sql = "SELECT * FROM badges 
            WHERE badge_id NOT IN (SELECT badge_id FROM user_badges WHERE user_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $unearnedBadges = $stmt->get_result(); // CHANGED

    // 3. Loop and check if any can be awarded
    while ($badge = $unearnedBadges->fetch_assoc()) {
        if ($totalPoints >= $badge['points_required']) {
            // Award the badge!
            $insertSql = "INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)"; // CHANGED
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("ii", $userId, $badge['badge_id']);
            $insertStmt->execute();
        }
    }
}
?>