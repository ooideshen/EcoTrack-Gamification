<?php
// includes/dashboard_functions.php
declare(strict_types=1);

require __DIR__ . '/../../config/dbConnect.php';
require_once __DIR__ . '/../../config/auth.php';
require_role('organizer');
/**
 * Count currently active challenges (date range).
 * Table: weekly_challenges (start_date, end_date)
 */
function getOrganizerName(mysqli $conn): string {
    $uid = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? null); // support either key
    if (!$uid) return 'organizer';

    $sql = "SELECT name FROM users WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    $name = $row['name'] ?? '';
    return ($name !== '') ? $name : 'organizer';
}

function getActiveChallengesCount(mysqli $conn): int {
    $sql = "SELECT COUNT(*) AS c FROM weekly_challenges WHERE CURDATE() BETWEEN start_date AND end_date";
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

/**
 * Nearest due date among active challenges (days left).
 * Fallback: days until the next upcoming challenge start.
 */
function getNearestDueDays(mysqli $conn) {
    // Try active challenges' min days_left
    $sql1 = "SELECT MIN(DATEDIFF(end_date, CURDATE())) AS days_left
             FROM weekly_challenges
             WHERE CURDATE() BETWEEN start_date AND end_date";
    $res1 = $conn->query($sql1);
    $row1 = $res1 ? $res1->fetch_assoc() : null;
    if ($row1 && $row1['days_left'] !== null) {
        return (int)$row1['days_left'];
    }
    
// Fallback: days until the next upcoming challenge start
    $sql2 = "SELECT MIN(DATEDIFF(start_date, CURDATE())) AS days_until_start
             FROM weekly_challenges
             WHERE start_date >= CURDATE()";
    $res2 = $conn->query($sql2);
    $row2 = $res2 ? $res2->fetch_assoc() : null;
    if ($row2 && $row2['days_until_start'] !== null) {
        return (int)$row2['days_until_start'];
    }
    return null;
}

/** Count submissions with status 'Pending'. */
function getPendingSubmissionsCount(mysqli $conn): int {
    $sql = "SELECT COUNT(*) AS c FROM submission_proof WHERE status = 'pending'";
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

/** Count submissions approved in the current month. */
function getApprovedThisMonthCount(mysqli $conn): int { 
    $sql = "SELECT COUNT(*) AS c
            FROM submission_proof
            WHERE status = 'approved'
                AND DATE_FORMAT(submitted_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')";
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

/** Active challenges list (for notifications). */
function getActiveChallengesList(mysqli $conn, int $limit = 5): array {
    $sql = "SELECT challenge_id, title, end_date
            FROM weekly_challenges
            WHERE CURDATE() BETWEEN start_date AND end_date
            ORDER BY end_date ASC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/** Latest pending submissions (for review cards). */
function getPendingSubmissionCards(mysqli $conn, int $limit = 6): array {
    $sql = "SELECT proof_id, notes, submitted_at
            FROM submission_proof
            WHERE status = 'pending'
            ORDER BY submitted_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/** Latest announcements. */
function getAnnouncements(mysqli $conn, int $limit = 5): array {
    $sql = "SELECT title, content, created_at
            FROM announcements
            ORDER BY created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/** Next upcoming challenge (optional event card). */
function getNextUpcomingChallenge(mysqli $conn): ?array {
    $sql = "SELECT challenge_id, title, start_date
            FROM weekly_challenges
            WHERE start_date > CURDATE()
            ORDER BY start_date ASC
            LIMIT 1";
    $res = $conn->query($sql);
    if (!$res) return null;
    $row = $res->fetch_assoc();
    return $row ?: null;
}
$data = [
    'activeChallenges'   => getActiveChallengesCount($conn),
    'nearestDueDays'     => getNearestDueDays($conn),
    'pendingSubmissions' => getPendingSubmissionsCount($conn),
    'approvedThisMonth'  => getApprovedThisMonthCount($conn),
    'activeChallengeList'=> getActiveChallengesList($conn, 5),
    'pendingCards'       => getPendingSubmissionCards($conn, 6),
    'announcements'      => getAnnouncements($conn, 5),
    'nextChallenge'      => getNextUpcomingChallenge($conn),
];

$activeChallenges    = (int)($data['activeChallenges'] ?? 0);
$nearestDueDays      = $data['nearestDueDays'] ?? null;
$pendingSubmissions  = (int)($data['pendingSubmissions'] ?? 0);
$approvedThisMonth   = (int)($data['approvedThisMonth'] ?? 0);
$activeChallengeList = $data['activeChallengeList'] ?? [];
$pendingCards        = $data['pendingCards'] ?? [];
$nextChallenge       = $data['nextChallenge'] ?? null;

// If you pass ?json=1, output pure JSON (backend-first workflow)
if (isset($_GET['json']) && $_GET['json'] === '1') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit;
}
include __DIR__ . '/../../assets/header.php';
?>    
<section class="card" style="padding:18px; margin-bottom: 24px;">
    <h2 style="margin:0 0 12px;">
        Welcome <?= htmlspecialchars(getOrganizerName($conn), ENT_QUOTES, 'UTF-8') ?>!
    </h2>

    <p class="text-success">
        You have <?= htmlspecialchars((string)$activeChallenges, ENT_QUOTES, 'UTF-8') ?>
        active challenge<?= $activeChallenges === 1 ? '' : 's' ?>.
    </p>
</section>

<main class="container">

    <!-- Two-column grid -->
    <section class="grid cols-1 md:cols-2 gap-3">
        <!-- Left: Quick Stats -->
        <div>
            <h2 class="h3">Quick Stats</h2>
            <!-- Auto-fit cards: min 240px columns -->
            <div class="grid grid-auto gap-3" style="margin-top: var(--space-2);">                
                <!-- Active Challenges -->
                <article class="card" aria-label="Active Challenges">
                    <div class="h5" style="display:flex;align-items:center;gap:10px;">
                        <span class="badge"><i class="ri-target-line" aria-hidden="true"></i></span>
                        <span>Active Challenges</span>
                    </div>
                    <div class="text-3xl" style="font-weight:800; margin-top:8px;">
                        <?= htmlspecialchars((string)$activeChallenges) ?>
                    </div>
                    <p class="text-sm" style="color: var(--color-text-muted);">
                        Nearest due:
                        <?php
                            if ($nearestDueDays === null) {
                                echo '—';
                            } elseif (is_numeric($nearestDueDays)) {
                                $d = (int)$nearestDueDays;
                                echo htmlspecialchars((string)$d) . ' ' . ($d === 1 ? 'day' : 'days');
                            } else {
                                echo htmlspecialchars((string)$nearestDueDays);
                            }
                        ?>
                    </p>
                </article>

                <!-- Pending Submissions -->
                <article class="card" aria-label="Pending Submissions">
                    <div class="h5" style="display:flex;align-items:center;gap:10px;">
                        <span class="badge"><i class="ri-time-line" aria-hidden="true"></i></span>
                        <span>Pending Submissions</span>
                    </div>
                    <div class="text-3xl" style="font-weight:800; margin-top:8px;">
                        <?= htmlspecialchars((string)$pendingSubmissions) ?>
                    </div>
                    <p class="text-sm" style="color: var(--color-text-muted);">
                        <?= htmlspecialchars((string)$approvedThisMonth) ?> Approved this month
                    </p>
                </article>
            </div>
        </div>

    <!-- RIGHT: Notifications -->
    <div>
        <h2 class="h3">Notifications</h2>

        <div class="grid gap-3" style="margin-top: var(--space-2); grid-template-columns: 1fr;">


            <!-- Banner: Submission Pending (only show if any pending) -->
            <?php if ($pendingSubmissions > 0): ?>
                <article class="card bg-warning" aria-label="Submission pending">
                    <div class="h5" style="display:flex;align-items:center;gap:10px;">
                        <span class="badge"><i class="ri-time-line" aria-hidden="true"></i></span>
                        <span>Submission</span>
                    </div>
                    <p><strong>Pending</strong> — You have submissions awaiting verification.</p>
                </article>
            <?php endif; ?>
        
            <!-- Challenge cards (from DB) -->
            <?php if (!empty($activeChallengeList)): ?>
                <?php foreach ($activeChallengeList as $c): ?>
                    <article class="card" aria-label="Challenge" style="overflow: hidden; padding: 0;">
                        <?php if (!empty($c['image_path'])): ?>
                            <div style="width: 100%; height: 60px; overflow: hidden;">
                                <img src="../../<?= h($c['image_path']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        <?php endif; ?>

                        <div style="padding: 16px;">
                            <div class="h5" style="display:flex;align-items:center;gap:10px;">
                                <span class="badge"><i class="ri-flag-line" aria-hidden="true"></i></span>
                                <span>Challenge</span>
                                <a class="button" href="createChallenge.php?edit=<?= urlencode((string)$c['challenge_id']) ?>" style="margin-left:auto; padding: 6px 12px; font-size: var(--fs-sm);">Manage</a>
                            </div>
                            <p class="text-base" style="color: var(--color-text-muted); margin-top: 8px;"><?= htmlspecialchars($c['title']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Pending submission cards (from DB) -->
            <?php foreach ($pendingCards as $p): ?>
                <article class="card" aria-label="Submission">
                        <div class="h5" style="display:flex;align-items:center;gap:10px;">
                            <span class="badge"><i class="ri-file-line" aria-hidden="true"></i></span>
                            <span>Submission #<?= htmlspecialchars((string)$p['proof_id']) ?></span>
                            <a class="button" href="verifySubmissions.php?proof_id=<?= urlencode((string)$p['proof_id']) ?>" style="margin-left:auto;">Review</a>
                        </div>
                        <p class="text-base" style="color: var(--color-text-muted);">
                            <?= htmlspecialchars($p['notes'] ?? 'No description') ?>
                            — <?= htmlspecialchars($p['submitted_at'] ?? '') ?>
                        </p>
                </article>
            <?php endforeach; ?>
       
            <!-- Event card (next upcoming challenge) -->
            <?php if (!empty($nextChallenge)): ?>
                <article class="card" aria-label="Event">
                    <div class="h5" style="display:flex;align-items:center;gap:10px;">
                        <span class="badge"><i class="ri-calendar-event-line" aria-hidden="true"></i></span>
                        <span>Event</span>
                        <a class="button" href="createChallenge.php?edit=<?= urlencode((string)$nextChallenge['challenge_id']) ?>" style="margin-left:auto;">Join</a>
                    </div>
                    <p class="text-base" style="color: var(--color-text-muted);">
                    <?= htmlspecialchars($nextChallenge['title']) ?>
                    </p>
                </article>
            <?php endif; ?>
        </div>
      </div>
    </section>
</main>

  <!-- Optional: Tiny JS for dismiss buttons (no framework) -->
  <script>
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.close');
      if(!btn) return;
      const card = btn.closest('.notif');
      if(card) card.remove();
    });
  </script>
<?php
include __DIR__ . '/../../assets/footer.php'; // closes </main></body></html>