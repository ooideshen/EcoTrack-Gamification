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
$csrf_token = $_SESSION['csrf_token'];

$feedback = '';
$feedbackType = '';

// Handle Add Challenge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $feedback = 'Invalid CSRF token.';
        $feedbackType = 'error';
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $bonus_points = (int)$_POST['bonus_points'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        $stmt = $conn->prepare("INSERT INTO weekly_challenges (title, description, bonus_points, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $title, $description, $bonus_points, $start_date, $end_date);
        
        if ($stmt->execute()) {
            $feedback = "Challenge added successfully!";
            $feedbackType = 'success';
        } else {
            $feedback = "Error: " . $conn->error;
            $feedbackType = 'error';
        }
        $stmt->close();
    }
}

// Handle Update Challenge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $feedback = 'Invalid CSRF token.';
        $feedbackType = 'error';
    } else {
        $challenge_id = (int)$_POST['challenge_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $bonus_points = (int)$_POST['bonus_points'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        $stmt = $conn->prepare("UPDATE weekly_challenges SET title=?, description=?, bonus_points=?, start_date=?, end_date=? WHERE challenge_id=?");
        $stmt->bind_param("ssissi", $title, $description, $bonus_points, $start_date, $end_date, $challenge_id);
        
        if ($stmt->execute()) {
            $feedback = "Challenge updated successfully!";
            $feedbackType = 'success';
        } else {
            $feedback = "Error: " . $conn->error;
            $feedbackType = 'error';
        }
        $stmt->close();
    }
}

// Handle Delete Challenge
if (isset($_GET['delete'])) {
    $challenge_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM weekly_challenges WHERE challenge_id=?");
    $stmt->bind_param("i", $challenge_id);
    
    if ($stmt->execute()) {
        $feedback = "Challenge deleted successfully!";
        $feedbackType = 'success';
    } else {
        $feedback = "Error: " . $conn->error;
        $feedbackType = 'error';
    }
    $stmt->close();
}

// Fetch all challenges (active and upcoming)
$sql = "SELECT *, 
        DATEDIFF(end_date, CURDATE()) as days_left,
        CASE 
            WHEN CURDATE() < start_date THEN 'upcoming'
            WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
            ELSE 'past'
        END as status
        FROM weekly_challenges 
        ORDER BY start_date DESC";
$result = $conn->query($sql);

// Get selected challenge for editing
$selected_challenge = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM weekly_challenges WHERE challenge_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $selected_challenge = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

include __DIR__ . '/../../assets/adminHeader.php';
?>

<style>
.two-column-layout {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}
.challenge-list {
    display: grid;
    gap: var(--space-2);
}
.challenge-item {
    padding: var(--space-3);
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.3s ease;
}
.challenge-item:hover {
    border-color: var(--color-primary);
    transform: translateX(4px);
}
.challenge-item.selected {
    border-color: var(--color-primary);
    background: var(--color-primary-soft);
}
.challenge-title {
    font-weight: 600;
    margin-bottom: var(--space-1);
}
.challenge-time {
    font-size: var(--fs-sm);
    color: var(--color-text-muted);
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-3);
}
@media (max-width: 960px) {
    .two-column-layout {
        grid-template-columns: 1fr;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<h1 class="h1">Manage Challenges</h1>

<?php if ($feedback !== ''): ?>
    <div class="card <?= $feedbackType === 'success' ? 'bg-success' : 'bg-error' ?>" style="margin-bottom: var(--space-3);">
        <p style="margin:0;"><?= htmlspecialchars($feedback) ?></p>
    </div>
<?php endif; ?>

<div class="two-column-layout">
    <!-- Left: Challenge List -->
    <div>
        <div class="card">
            <h2 class="h3" style="margin-bottom: var(--space-3);">All Challenges</h2>
            <div class="challenge-list">
                <?php 
                $result->data_seek(0);
                while ($challenge = $result->fetch_assoc()): 
                    $statusBadge = $challenge['status'] === 'active' ? '<span class="badge" style="background: var(--color-success);">Active</span>' :
                                  ($challenge['status'] === 'upcoming' ? '<span class="badge" style="background: var(--color-info);">Upcoming</span>' :
                                  '<span class="badge" style="background: var(--color-text-muted);">Past</span>');
                ?>
                <div class="challenge-item <?= $selected_challenge && $selected_challenge['challenge_id'] == $challenge['challenge_id'] ? 'selected' : '' ?>" 
                     onclick="location.href='manageChallenges.php?edit=<?= $challenge['challenge_id'] ?>'">
                    <div class="challenge-title"><?= htmlspecialchars($challenge['title']) ?></div>
                    <div class="challenge-time">
                        <?= $statusBadge ?>
                        <?php if ($challenge['status'] === 'active'): ?>
                            • <?= $challenge['days_left'] ?> days left
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Right: Manage Form -->
    <div>
        <div class="card">
            <h2 class="h3" style="margin-bottom: var(--space-3);">
                <?= $selected_challenge ? 'Edit Challenge' : 'Create New Challenge' ?>
            </h2>
            <form method="POST" style="display: grid; gap: var(--space-3);">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="<?= $selected_challenge ? 'update' : 'add' ?>">
                <?php if ($selected_challenge): ?>
                    <input type="hidden" name="challenge_id" value="<?= $selected_challenge['challenge_id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="h5" for="title">Challenge Name*</label>
                    <input type="text" id="title" name="title" 
                           value="<?= $selected_challenge ? htmlspecialchars($selected_challenge['title']) : '' ?>" 
                           placeholder="Ex. Plastic Free Week"
                           style="width:100%;" required>
                </div>

                <div>
                    <label class="h5" for="description">Description / Goal*</label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Describe the challenge goals..."
                              style="width:100%; resize: vertical;" required><?= $selected_challenge ? htmlspecialchars($selected_challenge['description']) : '' ?></textarea>
                </div>

                <div class="form-row">
                    <div>
                        <label class="h5" for="start_date">Start Date*</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?= $selected_challenge ? $selected_challenge['start_date'] : '' ?>" 
                               style="width:100%;" required>
                    </div>
                    <div>
                        <label class="h5" for="end_date">End Date*</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?= $selected_challenge ? $selected_challenge['end_date'] : '' ?>" 
                               style="width:100%;" required>
                    </div>
                </div>

                <div>
                    <label class="h5" for="bonus_points">Bonus Points Reward*</label>
                    <input type="number" id="bonus_points" name="bonus_points" 
                           value="<?= $selected_challenge ? $selected_challenge['bonus_points'] : '' ?>" 
                           placeholder="e.g., 50"
                           min="0"
                           style="width:100%;" required>
                </div>

                <div style="display: flex; gap: var(--space-2); margin-top: var(--space-2);">
                    <button type="submit" class="button">
                        <?= $selected_challenge ? 'Update Challenge' : 'Create Challenge' ?>
                    </button>
                    <?php if ($selected_challenge): ?>
                        <button type="button" class="button" 
                                style="background: var(--color-elevated); color: var(--color-text);" 
                                onclick="location.href='manageChallenges.php'">Cancel</button>
                        <button type="button" class="button" 
                                style="background: var(--color-error);" 
                                onclick="deleteChallenge(<?= $selected_challenge['challenge_id'] ?>)">Delete</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteChallenge(id) {
    if (confirm('Delete this challenge? This action cannot be undone.')) {
        location.href = `manageChallenges.php?delete=${id}`;
    }
}
</script>

<?php include __DIR__ . '/../../assets/footer.php'; ?>