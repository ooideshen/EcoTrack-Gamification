<?php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/dbConnect.php';
require_role('admin');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../controllers/login.php?error=Access denied');
    exit;
}

$username = $_SESSION['user']['name'] ?? 'administrator';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$feedback = '';
$feedbackType = '';
// Handle Approve
if (isset($_GET['approve'])) {
    $proof_id = (int)$_GET['approve'];
    
    // Get submission details
    $stmt = $conn->prepare("SELECT user_id, activity_id, challenge_id, quantity FROM submission_proof WHERE proof_id = ?");
    $stmt->bind_param("i", $proof_id);
    $stmt->execute();
    $submission = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($submission) {
        // Get activity points
        $stmt = $conn->prepare("SELECT points_awarded FROM eco_activities WHERE activity_id = ?");
        $stmt->bind_param("i", $submission['activity_id']);
        $stmt->execute();
        $activity = $stmt->get_result()->fetch_assoc();
        $base_points = $activity['points_awarded'];
        $stmt->close();
        
        // Calculate points
        $points_earned = $base_points * $submission['quantity'];
        
        // Add bonus if challenge
        if ($submission['challenge_id']) {
            $stmt = $conn->prepare("SELECT bonus_points FROM weekly_challenges WHERE challenge_id = ?");
            $stmt->bind_param("i", $submission['challenge_id']);
            $stmt->execute();
            $challenge = $stmt->get_result()->fetch_assoc();
            $points_earned += $challenge['bonus_points'];
            $stmt->close();
        }
        
        // Update submission status
        $stmt = $conn->prepare("UPDATE submission_proof SET status='approved' WHERE proof_id=?");
        $stmt->bind_param("i", $proof_id);
        $stmt->execute();
        $stmt->close();
        
        // Add to activity log
        $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_id, challenge_id, proof_id, quantity, points_earned) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiii", $submission['user_id'], $submission['activity_id'], $submission['challenge_id'], $proof_id, $submission['quantity'], $points_earned);
        $stmt->execute();
        $stmt->close();
        
        $feedback = "Submission approved successfully!";
        $feedbackType = 'success';
    }
}

// Handle Reject
if (isset($_GET['reject'])) {
    $proof_id = (int)$_GET['reject'];
    $stmt = $conn->prepare("UPDATE submission_proof SET status='rejected' WHERE proof_id=?");
    $stmt->bind_param("i", $proof_id);
    
    if ($stmt->execute()) {
        $feedback = "Submission rejected.";
        $feedbackType = 'success';
    }
    $stmt->close();
}

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE 1=1";
if ($filter !== 'all') {
    $filterEscaped = $conn->real_escape_string($filter);
    $where .= " AND sp.status = '$filterEscaped'";
}
if ($search) {
    $searchEscaped = $conn->real_escape_string($search);
    $where .= " AND (u.name LIKE '%$searchEscaped%' OR ea.activity_name LIKE '%$searchEscaped%')";
}

// Fetch submissions
$sql = "SELECT sp.*, u.name as user_name, ea.activity_name, ea.points_awarded,
        wc.title as challenge_title, wc.bonus_points
        FROM submission_proof sp
        JOIN users u ON sp.user_id = u.user_id
        JOIN eco_activities ea ON sp.activity_id = ea.activity_id
        LEFT JOIN weekly_challenges wc ON sp.challenge_id = wc.challenge_id
        $where
        ORDER BY sp.submitted_at DESC";
$result = $conn->query($sql);

include __DIR__ . '/../../assets/adminHeader.php';
?>

<style>
.action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
    flex-wrap: wrap;
}
.filter-buttons {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
}
.filter-btn {
    background: var(--color-elevated);
    color: var(--color-text);
    border: 2px solid transparent;
    transition: all 0.3s ease;
}
.filter-btn.active {
    background: var(--color-primary);
    color: var(--btn-text);
    border-color: var(--color-primary);
}
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: var(--color-overlay);
}
.modal-content {
    background: var(--card-bg);
    margin: 5% auto;
    padding: var(--space-5);
    border-radius: var(--radius-lg);
    max-width: 600px;
    box-shadow: var(--shadow-2);
}
.close {
    color: var(--color-text-muted);
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover { color: var(--color-text); }
.action-icons {
    display: flex;
    gap: var(--space-1);
    justify-content: center;
}
.action-icons button {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    transition: all 0.2s;
}
.action-icons button:hover {
    background: var(--color-elevated);
}
</style>

<h1 class="h1">Review Submissions</h1>

<?php if ($feedback !== ''): ?>
    <div class="card <?= $feedbackType === 'success' ? 'bg-success' : 'bg-error' ?>" style="margin-bottom: var(--space-3);">
        <p style="margin:0;"><?= htmlspecialchars($feedback) ?></p>
    </div>
<?php endif; ?>

<div class="action-bar">
    <div class="search-box">
        <form method="GET" style="display: flex; gap: var(--space-2);">
            <input type="search" name="search" placeholder="Search users or activities..." value="<?= htmlspecialchars($search) ?>" style="min-width: 250px;">
            <button type="submit" class="button">🔍 Search</button>
            <?php if ($filter !== 'all'): ?>
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <?php endif; ?>
        </form>
    </div>
    <div class="filter-buttons">
        <button class="button filter-btn <?= $filter === 'all' ? 'active' : '' ?>" onclick="location.href='viewSubmissions.php'">All</button>
        <button class="button filter-btn <?= $filter === 'pending' ? 'active' : '' ?>" onclick="location.href='?filter=pending'">⏱ Pending</button>
        <button class="button filter-btn <?= $filter === 'approved' ? 'active' : '' ?>" onclick="location.href='?filter=approved'">✓ Approved</button>
        <button class="button filter-btn <?= $filter === 'rejected' ? 'active' : '' ?>" onclick="location.href='?filter=rejected'">✗ Rejected</button>
    </div>
</div>

<div class="card" style="overflow-x: auto;">
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--color-border-strong);">
                <th style="padding: var(--space-2); text-align: left;">User</th>
                <th style="padding: var(--space-2); text-align: left;">Activity</th>
                <th style="padding: var(--space-2); text-align: center;">Qty</th>
                <th style="padding: var(--space-2); text-align: left;">Status</th>
                <th style="padding: var(--space-2); text-align: left;">Date</th>
                <th style="padding: var(--space-2); text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $statusColor = $row['status'] === 'approved' ? 'var(--color-success)' : 
                                  ($row['status'] === 'rejected' ? 'var(--color-error)' : 'var(--color-warning)');
                ?>
                <tr style="border-bottom: 1px solid var(--color-border);">
                    <td style="padding: var(--space-2);"><?= htmlspecialchars($row['user_name']) ?></td>
                    <td style="padding: var(--space-2);"><?= htmlspecialchars($row['activity_name']) ?></td>
                    <td style="padding: var(--space-2); text-align: center;"><?= htmlspecialchars((string)$row['quantity']) ?></td>
                    <td style="padding: var(--space-2);">
                        <span class="badge" style="background: <?= $statusColor ?>; color: var(--color-text-on-dark);">
                            <?= htmlspecialchars(ucfirst($row['status'])) ?>
                        </span>
                    </td>
                    <td style="padding: var(--space-2); font-size: var(--fs-sm);">
                        <?= date('M d, Y', strtotime($row['submitted_at'])) ?><br>
                        <span style="color: var(--color-text-muted);"><?= date('h:i A', strtotime($row['submitted_at'])) ?></span>
                    </td>
                    <td style="padding: var(--space-2);">
                        <div class="action-icons">
                            <button onclick='viewSubmission(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)' title="View Details">👁️</button>
                            <?php if ($row['status'] === 'pending'): ?>
                                <button onclick="approveSubmission(<?= $row['proof_id'] ?>)" title="Approve" style="color: var(--color-success);">✓</button>
                                <button onclick="rejectSubmission(<?= $row['proof_id'] ?>)" title="Reject" style="color: var(--color-error);">✗</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="padding: var(--space-4); text-align: center; color: var(--color-text-muted);">
                        No submissions found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- View Submission Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeViewModal()">&times;</span>
        <h2 class="h2">Submission Details</h2>
        <div id="viewContent" style="margin-top: var(--space-3);"></div>
        <div style="margin-top: var(--space-4);">
            <button class="button" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<script>
function viewSubmission(sub) {
    const points = sub.points_awarded * sub.quantity;
    const bonus = sub.bonus_points ? sub.bonus_points : 0;
    const total = points + bonus;
    
    const statusColor = sub.status === 'approved' ? 'var(--color-success)' : 
                       (sub.status === 'rejected' ? 'var(--color-error)' : 'var(--color-warning)');
    
    document.getElementById('viewContent').innerHTML = `
        <div style="display: grid; gap: var(--space-2);">
            <p><strong>Submitted by:</strong> ${sub.user_name}</p>
            <p><strong>Activity:</strong> ${sub.activity_name}</p>
            <p><strong>Quantity:</strong> ${sub.quantity}</p>
            <p><strong>Base Points:</strong> ${sub.points_awarded} × ${sub.quantity} = ${points} pts</p>
            ${sub.challenge_title ? `<p><strong>Challenge:</strong> ${sub.challenge_title} <span class="badge">+${bonus} bonus</span></p>` : ''}
            <p><strong>Total Points:</strong> <span style="font-size: var(--fs-lg); font-weight: 700; color: var(--color-success);">${total} pts</span></p>
            <p><strong>Notes:</strong> ${sub.notes || '<em style="color: var(--color-text-muted);">None</em>'}</p>
            <p><strong>Status:</strong> <span class="badge" style="background: ${statusColor}; color: var(--color-text-on-dark);">${sub.status}</span></p>
            <p><strong>Proof File:</strong> <a href="../../${sub.file_path}" target="_blank">View File</a></p>
            <p><strong>Submitted:</strong> ${new Date(sub.submitted_at).toLocaleString()}</p>
        </div>
    `;
    document.getElementById('viewModal').style.display = 'block';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function approveSubmission(id) {
    if (confirm('Approve this submission and award points?')) {
        location.href = `viewSubmissions.php?approve=${id}`;
    }
}

function rejectSubmission(id) {
    if (confirm('Reject this submission?')) {
        location.href = `viewSubmissions.php?reject=${id}`;
    }
}

window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../../assets/footer.php'; ?>