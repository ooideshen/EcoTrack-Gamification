<?php
// postAnnouncement.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/dbConnect.php';
require_role('organizer'); // or ['organizer','admin'] for shared pages
require_once __DIR__ . '/organizerData.php'; // initializes session data arrays

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Ensure session (organizerData.php should start session, but we guard anyway)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$feedback = '';

// Get current user ID
$userId = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? null);
if (!$userId) {
    die('User ID not found in session.');
}

// Read edit from GET
$editId = filter_input(INPUT_GET, 'editId', FILTER_VALIDATE_INT);

// DELETE via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $feedback = 'Invalid CSRF token.';
    } else {
        $deleteId = (int)($_POST['announcementId'] ?? 0);
        if ($deleteId > 0) {
            $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
            $stmt->bind_param("i", $deleteId);
            
            if ($stmt->execute()) {
                header('Location: postAnnouncement.php?msg=' . urlencode('Announcement deleted successfully'));
                exit();
            } else {
                $feedback = 'Error deleting announcement.';
            }
        } else {
            $feedback = 'Invalid announcement ID for deletion.';
        }
    }
}

// CREATE / EDIT via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveAnnouncement'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $feedback = 'Invalid CSRF token.';
    } else {
        $announcementId = (int)($_POST['announcementId'] ?? 0);
        $title          = trim($_POST['title'] ?? '');
        $content        = trim($_POST['content'] ?? '');

        if ($title !== '' && $content !== '') {
            if ($announcementId === 0) {
                // CREATE
                $stmt = $conn->prepare("
                    INSERT INTO announcements (user_id, title, content, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->bind_param("iss", $userId, $title, $content);
                
                if ($stmt->execute()) {
                    $newId = $conn->insert_id;
                    header('Location: postAnnouncement.php?msg=' . urlencode('Announcement posted successfully') . '&editId=' . $newId);
                    exit();
                } else {
                    $feedback = 'Error creating announcement.';
                }
            } else {
                // EDIT
                $stmt = $conn->prepare("
                    UPDATE announcements 
                    SET title = ?, content = ?
                    WHERE announcement_id = ?
                ");
                $stmt->bind_param("ssi", $title, $content, $announcementId);
                
                if ($stmt->execute()) {
                    header('Location: postAnnouncement.php?msg=' . urlencode('Announcement updated successfully') . '&editId=' . $announcementId);
                    exit();
                } else {
                    $feedback = 'Error updating announcement.';
                }
            }
        } else {
            $feedback = 'Title and content are required.';
        }
    }
}

// Preload record if editing
$editRecord = null;
if ($editId) {
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editRecord = $result->fetch_assoc();
}

// Fetch all announcements
$announcementsQuery = "
    SELECT a.announcement_id, a.title, a.content, a.created_at, u.name as author_name
    FROM announcements a
    JOIN users u ON u.user_id = a.user_id
    ORDER BY a.created_at DESC
";
$announcementsResult = $conn->query($announcementsQuery);
$announcements = $announcementsResult ? $announcementsResult->fetch_all(MYSQLI_ASSOC) : [];

// Message via PRG
if (isset($_GET['msg'])) {
    $feedback = (string)$_GET['msg'];
}

include __DIR__ . '/../../assets/header.php';
?>

<h1 class="h1"><?= $editRecord ? 'Edit Announcement' : 'Post News / Events' ?></h1>

<?php if ($feedback !== ''): ?>
  <div class="card <?= strpos($feedback, 'successfully') !== false ? 'bg-success' : 'bg-warning' ?>" style="margin-bottom: var(--space-3);">
    <p style="margin:0;"><?= h($feedback) ?></p>
  </div>
<?php endif; ?>

<div class="card">
  <form method="post" style="display:grid; gap: var(--space-3);">
    <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
    <input type="hidden" name="announcementId" value="<?= h((string)($editRecord['announcement_id'] ?? '')) ?>">
    
    <div>
      <label class="h5" for="title" style="display:block; margin-bottom: var(--space-1);">Title*</label>
      <input type="text" id="title" name="title" 
             value="<?= h($editRecord['title'] ?? '') ?>" 
             placeholder="Ex. Campus Clean-Up Event" 
             style="width:100%;" required>
    </div>
    
    <div>
      <label class="h5" for="content" style="display:block; margin-bottom: var(--space-1);">Content*</label>
      <textarea id="content" name="content" rows="6" 
                placeholder="Write your announcement here..." 
                style="width:100%; resize: vertical;" required><?= h($editRecord['content'] ?? '') ?></textarea>
    </div>
    
    <div style="display:flex; gap: var(--space-2);">
      <button class="button" type="submit" name="saveAnnouncement" value="1">
        <?= $editRecord ? 'Save Changes' : 'Post Announcement' ?>
      </button>
      <?php if ($editRecord): ?>
        <a href="postAnnouncement.php" class="button" style="background: var(--color-elevated); color: var(--color-text);">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<h2 class="h2" style="margin-top: var(--space-5);">All Announcements</h2>

<?php if (empty($announcements)): ?>
  <div class="card">
    <p style="margin:0; color: var(--color-text-muted);">No announcements posted yet.</p>
  </div>
<?php else: ?>
  <div class="card" style="overflow-x: auto;">
    <table style="width:100%; border-collapse: collapse;">
      <thead>
        <tr style="border-bottom: 2px solid var(--color-border-strong);">
          <th style="padding: var(--space-2); text-align: left;">ID</th>
          <th style="padding: var(--space-2); text-align: left;">Title</th>
          <th style="padding: var(--space-2); text-align: left;">Content Preview</th>
          <th style="padding: var(--space-2); text-align: left;">Author</th>
          <th style="padding: var(--space-2); text-align: left;">Posted</th>
          <th style="padding: var(--space-2); text-align: left;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($announcements as $a): ?>
        <tr style="border-bottom: 1px solid var(--color-border);">
          <td style="padding: var(--space-2);"><?= h((string)$a['announcement_id']) ?></td>
          <td style="padding: var(--space-2);"><strong><?= h($a['title']) ?></strong></td>
          <td style="padding: var(--space-2); font-size: var(--fs-sm); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            <?= h(substr($a['content'], 0, 100)) ?><?= strlen($a['content']) > 100 ? '...' : '' ?>
          </td>
          <td style="padding: var(--space-2);"><?= h($a['author_name']) ?></td>
          <td style="padding: var(--space-2); font-size: var(--fs-sm);"><?= h(date('M j, Y', strtotime($a['created_at']))) ?></td>
          <td style="padding: var(--space-2);">
            <div style="display:flex; gap:6px; flex-wrap: wrap;">
              <a href="postAnnouncement.php?editId=<?= urlencode((string)$a['announcement_id']) ?>" class="button" style="padding: 6px 12px; font-size: var(--fs-sm);">Edit</a>
              <form action="postAnnouncement.php" method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="announcementId" value="<?= h((string)$a['announcement_id']) ?>">
                <button type="submit" class="button" style="background: var(--color-error); padding: 6px 12px; font-size: var(--fs-sm);" onclick="return confirm('Delete this announcement?')">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div style="margin-top: var(--space-4);">
  <a href="organizerDashboard.php" class="button">
    <i class="ri-arrow-left-line" aria-hidden="true"></i>
    Back to Dashboard
  </a>
</div>

<?php include __DIR__ . '/../../assets/footer.php'; ?>