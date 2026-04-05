<?php
// verifySubmissions.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/dbConnect.php';
require_role('organizer'); // or ['organizer','admin'] for shared pages
require_once __DIR__ . '/organizerData.php'; // initializes session data arrays
require_once __DIR__ . '/../student/badgeChecker.php'; // badge awarding logic

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$actionFeedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $actionFeedback = 'Invalid CSRF token. Please refresh and try again.';
    } else {
        $decision = $_POST['decision'] ?? '';
        $proofId  = (int)($_POST['proofId'] ?? 0);
        $customPoints = (int)($_POST['award'] ?? 0);

        if ($proofId > 0 && in_array($decision, ['approved', 'rejected'], true)) {
            
            if ($decision === 'approved') {
                // Get submission details
                $stmt = $conn->prepare("
                    SELECT sp.user_id, sp.activity_id, sp.challenge_id, sp.quantity,
                           ea.points_awarded, wc.bonus_points
                    FROM submission_proof sp
                    JOIN eco_activities ea ON ea.activity_id = sp.activity_id
                    LEFT JOIN weekly_challenges wc ON wc.challenge_id = sp.challenge_id
                    WHERE sp.proof_id = ?
                ");
                $stmt->bind_param("i", $proofId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $userId = (int)$row['user_id'];
                    $activityId = (int)$row['activity_id'];
                    $challengeId = $row['challenge_id'] ? (int)$row['challenge_id'] : null;
                    $quantity = (int)$row['quantity'];
                    $basePoints = (int)$row['points_awarded'];
                    $bonusPoints = $row['bonus_points'] ? (int)$row['bonus_points'] : 0;
                    
                    // Calculate total points
                    if ($customPoints > 0) {
                        $finalPoints = $customPoints;
                    } else {
                        $finalPoints = ($basePoints * $quantity) + $bonusPoints;
                    }
                    
                    // Update submission status
                    $updateStmt = $conn->prepare("UPDATE submission_proof SET status = 'approved' WHERE proof_id = ?");
                    $updateStmt->bind_param("i", $proofId);
                    $updateStmt->execute();
                    
                    // Insert into user_activity_log
                    $logStmt = $conn->prepare("
                        INSERT INTO user_activity_log 
                        (user_id, activity_id, challenge_id, proof_id, quantity, points_earned)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $logStmt->bind_param("iiiiii", $userId, $activityId, $challengeId, $proofId, $quantity, $finalPoints);
                    $logStmt->execute();
                    
                    // Check and award any earned badges
                    checkAndAwardBadges($conn, $userId);
                    
                    $actionFeedback = "Approved submission #$proofId - Awarded $finalPoints points.";
                } else {
                    $actionFeedback = "Submission #$proofId not found.";
                }
                
            } else {
                // Rejected
                $updateStmt = $conn->prepare("UPDATE submission_proof SET status = 'rejected' WHERE proof_id = ?");
                $updateStmt->bind_param("i", $proofId);
                $updateStmt->execute();
                $actionFeedback = "Rejected submission #$proofId.";
            }
            
        } else {
            $actionFeedback = 'Invalid input. Please try again.';
        }
    }
}

$pendingQuery = "
    SELECT 
        sp.proof_id,
        sp.user_id,
        u.name AS user_name,
        ea.activity_name,
        sp.quantity,
        ea.points_awarded,
        wc.title AS challenge_title,
        wc.bonus_points,
        sp.file_path,
        sp.notes,
        sp.submitted_at
    FROM submission_proof sp
    JOIN users u ON u.user_id = sp.user_id
    JOIN eco_activities ea ON ea.activity_id = sp.activity_id
    LEFT JOIN weekly_challenges wc ON wc.challenge_id = sp.challenge_id
    WHERE sp.status = 'pending'
    ORDER BY sp.submitted_at DESC
";
$pendingResult = $conn->query($pendingQuery);
$pending = $pendingResult ? $pendingResult->fetch_all(MYSQLI_ASSOC) : [];

$leaderboardQuery = "
    SELECT u.name, SUM(ual.points_earned) as total_points
    FROM user_activity_log ual
    JOIN users u ON u.user_id = ual.user_id
    GROUP BY ual.user_id
    ORDER BY total_points DESC
";
$leaderboardResult = $conn->query($leaderboardQuery);
$leaderboard = $leaderboardResult ? $leaderboardResult->fetch_all(MYSQLI_ASSOC) : [];

include __DIR__ . '/../../assets/header.php';
?>

<h1 class="h1">Verify Submissions</h1>

<?php if ($actionFeedback !== ''): ?>
  <div class="card bg-success" style="margin-bottom: var(--space-3);">
    <p style="margin:0;"><?= h($actionFeedback) ?></p>
  </div>
<?php endif; ?>

<h2 class="h2">Pending Submissions</h2>

<?php if (empty($pending)): ?>
  <div class="card">
    <p style="margin:0; color: var(--color-text-muted);">No pending submissions at the moment.</p>
  </div>
<?php else: ?>
  <div class="card" style="overflow-x: auto;">
    <table style="width:100%; border-collapse: collapse;">
      <thead>
        <tr style="border-bottom: 2px solid var(--color-border-strong);">
          <th style="padding: var(--space-2); text-align: left;">ID</th>
          <th style="padding: var(--space-2); text-align: left;">User</th>
          <th style="padding: var(--space-2); text-align: left;">Activity</th>
          <th style="padding: var(--space-2); text-align: left;">Qty</th>
          <th style="padding: var(--space-2); text-align: left;">Base Pts</th>
          <th style="padding: var(--space-2); text-align: left;">Challenge</th>
          <th style="padding: var(--space-2); text-align: left;">Bonus</th>
          <th style="padding: var(--space-2); text-align: left;">Notes</th>
          <th style="padding: var(--space-2); text-align: left;">Proof</th>
          <th style="padding: var(--space-2); text-align: left;">Submitted</th>
          <th style="padding: var(--space-2); text-align: left;">Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($pending as $p): ?>
        <tr style="border-bottom: 1px solid var(--color-border);">
          <td style="padding: var(--space-2);"><?= h((string)$p['proof_id']) ?></td>
          <td style="padding: var(--space-2);"><?= h($p['user_name']) ?></td>
          <td style="padding: var(--space-2);"><?= h($p['activity_name']) ?></td>
          <td style="padding: var(--space-2);"><?= h((string)$p['quantity']) ?></td>
          <td style="padding: var(--space-2);"><strong><?= h((string)($p['points_awarded'] * $p['quantity'])) ?></strong></td>
          <td style="padding: var(--space-2); font-size: var(--fs-sm);"><?= $p['challenge_title'] ? h($p['challenge_title']) : '—' ?></td>
          <td style="padding: var(--space-2);"><?= $p['bonus_points'] ? h((string)$p['bonus_points']) : '—' ?></td>
          <td style="padding: var(--space-2); font-size: var(--fs-sm); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= $p['notes'] ? h($p['notes']) : '—' ?></td>
          <td style="padding: var(--space-2);">
            <?php if ($p['file_path']): ?>
              <a href="../../<?= h($p['file_path']) ?>" target="_blank">View</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td style="padding: var(--space-2); font-size: var(--fs-sm);"><?= h(date('M j, Y', strtotime($p['submitted_at']))) ?></td>
          <td style="padding: var(--space-2);">
            <?php 
              $autoPoints = ($p['points_awarded'] * $p['quantity']) + ($p['bonus_points'] ?? 0);
            ?>
            <form action="verifySubmissions.php" method="post" style="display:flex; flex-direction:column; gap:6px;">
              <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
              <input type="hidden" name="proofId" value="<?= h((string)$p['proof_id']) ?>">
              <div style="font-size: var(--fs-sm); font-weight: bold; color: var(--color-success);">
                Award: <?= h((string)$autoPoints) ?> pts
              </div>
              <div style="display:flex; gap:4px;">
                <button name="decision" value="approved" class="button" style="background: var(--color-success); padding: 6px 12px; font-size: var(--fs-sm);">✓ Approve</button>
                <button name="decision" value="rejected" class="button" style="background: var(--color-error); padding: 6px 12px; font-size: var(--fs-sm);">✗ Reject</button>
              </div>
              <input type="number" name="award" min="0" placeholder="Override pts (optional)" style="width:140px; padding:4px; font-size: var(--fs-xs); opacity: 0.7;">
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<h2 class="h2" style="margin-top: var(--space-5);">Points Leaderboard</h2>
<?php if (empty($leaderboard)): ?>
  <div class="card">
    <p style="margin:0; color: var(--color-text-muted);">No points awarded yet.</p>
  </div>
<?php else: ?>
  <div class="grid cols-1 md:cols-2 lg:cols-3 gap-3">
    <?php foreach ($leaderboard as $entry): ?>
      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <strong><?= h($entry['name']) ?></strong>
          <span class="badge"><?= h((string)$entry['total_points']) ?> pts</span>
        </div>
      </div>
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