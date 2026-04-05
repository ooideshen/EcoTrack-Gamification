<?php
declare(strict_types=1);
// --- guards & setup ---
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/dbConnect.php';
require_role('organizer'); // or ['organizer','admin'] for shared pages
require_once __DIR__ . '/organizerData.php'; // initializes session data arrays

// --- helpers ---
function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// --- CSRF token ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$feedback = '';
// Handle POST - Create Challenge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $feedback = 'Invalid form token. Please reload and try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startDate   = trim($_POST['startDate'] ?? '');
        $endDate     = trim($_POST['endDate'] ?? '');
        $bonusPoints = (int)($_POST['bonusPoints'] ?? 0);

        // Validate
        $valid = $title !== '' && $description !== '' && $startDate !== '' && $endDate !== '' && $bonusPoints >= 0;
        
        $startTs = strtotime($startDate);
        $endTs   = strtotime($endDate);

        $imagePath = null;

        if (isset($_FILES['challenge_image']) && $_FILES['challenge_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../assets/uploads/challenges/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileExt = strtolower(pathinfo($_FILES['challenge_image']['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid('challenge_', true) . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['challenge_image']['tmp_name'], $destPath)) {
                $imagePath = 'assets/uploads/challenges/' . $newFileName;
            }
        }

        if ($valid && $startTs !== false && $endTs !== false && $endTs >= $startTs) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO weekly_challenges (title, description, bonus_points, start_date, end_date, image_path)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssisss", $title, $description, $bonusPoints, $startDate, $endDate);
                
                if ($stmt->execute()) {
                    $feedback = 'Challenge created successfully!';
                    // Clear form by redirecting
                    header('Location: createChallenge.php?msg=' . urlencode($feedback));
                    exit();
                } else {
                    $feedback = 'Database error: ' . h($stmt->error);
                }
            } catch (Throwable $e) {
                $feedback = 'Database error: ' . h($e->getMessage());
            }
        } else {
            $feedback = 'Please fill all fields, set bonus points ≥ 0, and ensure end date ≥ start date.';
        }
    }
}

// Fetch existing challenges to display
$challengesQuery = "
    SELECT challenge_id, title, description, bonus_points, start_date, end_date
    FROM weekly_challenges
    ORDER BY start_date DESC
    LIMIT 10
";
$challengesResult = $conn->query($challengesQuery);
$challenges = $challengesResult ? $challengesResult->fetch_all(MYSQLI_ASSOC) : [];

// Get feedback from redirect
if (isset($_GET['msg'])) {
    $feedback = (string)$_GET['msg'];
}

include __DIR__ . '/../../assets/header.php'; // includes opening <html><body><main>
?>

<h1 class="h1">Create Challenge</h1>

<?php if ($feedback !== ''): ?>
  <div class="card <?= strpos($feedback, 'successfully') !== false ? 'bg-success' : 'bg-warning' ?>" style="margin-bottom: var(--space-3);">
    <p style="margin:0;"><?= h($feedback) ?></p>
  </div>
<?php endif; ?>

<div class="card">
  <form method="post" enctype="multipart/form-data" style="display:grid; gap: var(--space-3);">
    <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">

    <!-- Title -->
    <div>
      <label class="h5" for="title" style="display:block; margin-bottom: var(--space-1);">Title*</label>
      <input type="text" id="title" name="title"
             placeholder="Ex. Plastic Free Week" 
             style="width:100%;" required>
    </div>

    <!-- Description -->
    <div>
      <label class="h5" for="description" style="display:block; margin-bottom: var(--space-1);">Description*</label>
      <textarea id="description" name="description" rows="4"
                placeholder="Describe the challenge and what participants need to do..." 
                style="width:100%; resize: vertical;" required></textarea>
    </div>

    <div>
      <label class="h5" for="challenge_image" style="display:block; margin-bottom: var(--space-1);">Challenge Banner Image</label>
      <input type="file" id="challenge_image" name="challenge_image" accept="image/*" 
             style="width:100%; padding: 8px; border: 1px dashed var(--color-border-strong); border-radius: var(--radius-sm);">
      <p style="margin: var(--space-1) 0 0; font-size: var(--fs-xs); color: var(--color-text-muted);">
        Recommended size: 1200x400px. Supports JPG, PNG, or WebP.
      </p>
    </div>

    <!-- From / To (two columns) -->
    <div class="grid cols-1 md:cols-2 gap-3">
      <div>
        <label class="h5" for="startDate" style="display:block; margin-bottom: var(--space-1);">Start Date*</label>
        <input type="date" id="startDate" name="startDate" 
               style="width:100%;" required>
      </div>
      <div>
        <label class="h5" for="endDate" style="display:block; margin-bottom: var(--space-1);">End Date*</label>
        <input type="date" id="endDate" name="endDate" 
               style="width:100%;" required>
      </div>
    </div>

    <!-- Bonus Points -->
    <div>
      <label class="h5" for="bonusPoints" style="display:block; margin-bottom: var(--space-1);">Bonus Points*</label>
      <input type="number" id="bonusPoints" name="bonusPoints" 
             min="0" placeholder="e.g., 50" 
             style="width:100%;" required>
      <p style="margin: var(--space-1) 0 0; font-size: var(--fs-sm); color: var(--color-text-muted);">
        Extra points awarded for completing this challenge
      </p>
    </div>

    <!-- Submit -->
    <button class="button" type="submit" name="save" value="1" style="justify-self: start;">
      Create Challenge
    </button>
  </form>
</div>

<!-- Recent Challenges -->
<h2 class="h2" style="margin-top: var(--space-5);">Recent Challenges</h2>
<?php if (empty($challenges)): ?>
  <div class="card">
    <p style="margin:0; color: var(--color-text-muted);">No challenges created yet.</p>
  </div>
<?php else: ?>
  <div class="grid cols-1 gap-3">
    <?php foreach ($challenges as $c): ?>
      <article class="card">
        <div style="display:flex; gap: 20px; align-items: flex-start;">
          
          <div style="flex: 0 0 100px; height: 100px; background: var(--color-elevated); border-radius: var(--radius-sm); overflow: hidden; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($c['image_path'])): ?>
              <img src="../../<?= h($c['image_path']) ?>" alt="Challenge" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
              <i class="ri-image-line" style="font-size: 2rem; color: var(--color-text-muted);"></i>
            <?php endif; ?>
          </div>

          <div style="flex: 1;">
            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom: var(--space-1);">
              <div>
                <h3 class="h4" style="margin:0;"><?= h($c['title']) ?></h3>
                <p style="margin:0; font-size: var(--fs-xs); color: var(--color-text-muted);">
                  <?= h(date('M j', strtotime($c['start_date']))) ?> - <?= h(date('M j, Y', strtotime($c['end_date']))) ?>
                </p>
              </div>
              <span class="badge"><?= h((string)$c['bonus_points']) ?> pts</span>
            </div>
            <p style="margin:0; font-size: var(--fs-sm);">
              <?= h(substr($c['description'], 0, 120)) ?>...
            </p>
          </div>
        </div>
      </article>
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