<?php
// organizerData.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Seed demo data structures if not set
$_SESSION['weeklyChallenges']  = $_SESSION['weeklyChallenges']  ?? [];
$_SESSION['submissionProof']   = $_SESSION['submissionProof']   ?? [];
$_SESSION['announcements']     = $_SESSION['announcements']     ?? [];
$_SESSION['pointsLedger']      = $_SESSION['pointsLedger']      ?? [];
$_SESSION['currentUserId']     = $_SESSION['currentUserId']     ?? 'USR-ORG1';

// Helper: get default points from a challenge ID
function getChallengePoints(string $challengeId): int {
    foreach ($_SESSION['weeklyChallenges'] as $c) {
        if ($c['challengeId'] === $challengeId) {
            return (int)$c['points'];
        }
    }
    return 0;
}
?>
