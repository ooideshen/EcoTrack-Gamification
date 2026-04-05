<?php
// verifyProcess.php
session_start();
require_once __DIR__ . '/../../config/dbConnect.php';

if (!isset($_SESSION['userId']) || ($_SESSION['role'] !== 'organizer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $proofId = $_POST['proof_id'];
    $action = $_POST['action'];

    if ($action == 'reject') {
        $stmt = $conn->prepare("UPDATE submission_proof SET status='rejected' WHERE proof_id=?");
        $stmt->bind_param("i", $proofId);
        $stmt->execute();
        header("Location: organizerDashboard.php?msg=Submission Rejected.");
        exit();
    }

    if ($action == 'approve') {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE submission_proof SET status='approved' WHERE proof_id=?");
            $stmt->bind_param("i", $proofId);
            $stmt->execute();

            // UPDATED SELECT: Fetch quantity as well
            $sqlDetails = "SELECT sp.user_id, sp.activity_id, sp.challenge_id, sp.quantity,
                                   ea.points_awarded, 
                                   COALESCE(wc.bonus_points, 0) as bonus 
                            FROM submission_proof sp
                            JOIN eco_activities ea ON sp.activity_id = ea.activity_id
                            LEFT JOIN weekly_challenges wc ON sp.challenge_id = wc.challenge_id
                            WHERE sp.proof_id = ?";
            
            $stmt = $conn->prepare($sqlDetails);
            $stmt->bind_param("i", $proofId);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();

            // UPDATED MATH: Points * Quantity + Bonus
            $basePoints = $data['points_awarded'] * $data['quantity'];
            $finalPoints = $basePoints + $data['bonus'];

            // UPDATED INSERT: Save quantity to log too
            $sqlLog = "INSERT INTO user_activity_log (user_id, activity_id, challenge_id, proof_id, quantity, points_earned) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sqlLog);
            // Types: iiiiii
            $stmt->bind_param("iiiiii", $data['user_id'], $data['activity_id'], $data['challenge_id'], $proofId, $data['quantity'], $finalPoints);
            $stmt->execute();

            $conn->commit();

            require 'badgeChecker.php';
            checkAndAwardBadges($conn, $data['user_id']);
            
            header("Location: organizerDashboard.php?msg=Approved! Points awarded.");

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: organizerDashboard.php?error=System Error.");
        }
    }
}
?>