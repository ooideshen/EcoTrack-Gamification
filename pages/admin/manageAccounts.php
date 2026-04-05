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
// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $feedback = 'Invalid CSRF token.';
        $feedbackType = 'error';
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $intake_code = trim($_POST['intake_code']);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, intake_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $password, $role, $intake_code);
        
        if ($stmt->execute()) {
            $feedback = "User added successfully!";
            $feedbackType = 'success';
        } else {
            $feedback = "Error adding user: " . $conn->error;
            $feedbackType = 'error';
        }
        $stmt->close();
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $feedback = 'Invalid CSRF token.';
        $feedbackType = 'error';
    } else {
        $user_id = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $intake_code = trim($_POST['intake_code']);
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password_hash=?, role=?, intake_code=? WHERE user_id=?");
            $stmt->bind_param("sssssi", $name, $email, $password, $role, $intake_code, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, intake_code=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $name, $email, $role, $intake_code, $user_id);
        }
        
        if ($stmt->execute()) {
            $feedback = "User updated successfully!";
            $feedbackType = 'success';
        } else {
            $feedback = "Error updating user: " . $conn->error;
            $feedbackType = 'error';
        }
        $stmt->close();
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $feedback = "User deleted successfully!";
        $feedbackType = 'success';
    } else {
        $feedback = "Error deleting user: " . $conn->error;
        $feedbackType = 'error';
    }
    $stmt->close();
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = '';
if ($search) {
    $searchLike = '%' . $conn->real_escape_string($search) . '%';
    $searchQuery = " WHERE name LIKE '$searchLike' OR email LIKE '$searchLike' OR role LIKE '$searchLike'";
}

// Fetch all users
$sql = "SELECT * FROM users" . $searchQuery . " ORDER BY created_at DESC";
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
.search-box form {
    display: flex;
    gap: var(--space-1);
}
.search-box input {
    min-width: 250px;
}
.action-buttons {
    display: flex;
    gap: var(--space-2);
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
    max-width: 500px;
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
.modal form {
    display: grid;
    gap: var(--space-3);
}
.modal-buttons {
    display: flex;
    gap: var(--space-2);
    margin-top: var(--space-3);
}
.action-icons {
    display: flex;
    gap: var(--space-1);
}
.action-icons button {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 4px;
    border-radius: var(--radius-sm);
    transition: all 0.2s;
}
.action-icons button:hover {
    background: var(--color-elevated);
}
@media (max-width: 640px) {
    .action-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .search-box input {
        min-width: 100%;
    }
}
</style>

<h1 class="h1">Manage User Accounts</h1>

<?php if ($feedback !== ''): ?>
    <div class="card <?= $feedbackType === 'success' ? 'bg-success' : 'bg-error' ?>" style="margin-bottom: var(--space-3);">
        <p style="margin:0;"><?= htmlspecialchars($feedback) ?></p>
    </div>
<?php endif; ?>

<div class="action-bar">
    <div class="search-box">
        <form method="GET">
            <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="button">🔍 Search</button>
        </form>
    </div>
    <div class="action-buttons">
        <button class="button" onclick="openAddModal()">➕ Add User</button>
    </div>
</div>

<div class="card" style="overflow-x: auto;">
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--color-border-strong);">
                <th style="padding: var(--space-2); text-align: left;">Full Name</th>
                <th style="padding: var(--space-2); text-align: left;">Email</th>
                <th style="padding: var(--space-2); text-align: left;">Intake Code</th>
                <th style="padding: var(--space-2); text-align: left;">Join Date</th>
                <th style="padding: var(--space-2); text-align: left;">Role</th>
                <th style="padding: var(--space-2); text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid var(--color-border);">
                    <td style="padding: var(--space-2);"><?= htmlspecialchars($row['name']) ?></td>
                    <td style="padding: var(--space-2); font-size: var(--fs-sm);"><?= htmlspecialchars($row['email']) ?></td>
                    <td style="padding: var(--space-2);"><?= htmlspecialchars($row['intake_code'] ?? 'N/A') ?></td>
                    <td style="padding: var(--space-2); font-size: var(--fs-sm);"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    <td style="padding: var(--space-2);">
                        <span class="badge"><?= htmlspecialchars(ucfirst($row['role'])) ?></span>
                    </td>
                    <td style="padding: var(--space-2);">
                        <div class="action-icons" style="justify-content: center;">
                            <button onclick='openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)' title="Edit">✏️</button>
                            <button onclick="deleteUser(<?= $row['user_id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>')" title="Delete">🗑️</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="padding: var(--space-4); text-align: center; color: var(--color-text-muted);">
                        No users found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddModal()">&times;</span>
        <h2 class="h2">Add New User</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="add">
            
            <div>
                <label class="h5" for="add_name">Full Name*</label>
                <input type="text" id="add_name" name="name" style="width:100%;" required>
            </div>
            
            <div>
                <label class="h5" for="add_email">Email*</label>
                <input type="email" id="add_email" name="email" style="width:100%;" required>
            </div>
            
            <div>
                <label class="h5" for="add_password">Password*</label>
                <input type="password" id="add_password" name="password" style="width:100%;" required>
            </div>
            
            <div>
                <label class="h5" for="add_role">Role*</label>
                <select id="add_role" name="role" style="width:100%;" required>
                    <option value="student">Student</option>
                    <option value="organizer">Organizer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div>
                <label class="h5" for="add_intake_code">Intake Code</label>
                <input type="text" id="add_intake_code" name="intake_code" style="width:100%;">
            </div>
            
            <div class="modal-buttons">
                <button type="submit" class="button">Add User</button>
                <button type="button" class="button" style="background: var(--color-elevated); color: var(--color-text);" onclick="closeAddModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2 class="h2">Edit User</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div>
                <label class="h5" for="edit_name">Full Name*</label>
                <input type="text" id="edit_name" name="name" style="width:100%;" required>
            </div>
            
            <div>
                <label class="h5" for="edit_email">Email*</label>
                <input type="email" id="edit_email" name="email" style="width:100%;" required>
            </div>
            
            <div>
                <label class="h5" for="edit_password">Password (leave blank to keep current)</label>
                <input type="password" id="edit_password" name="password" style="width:100%;">
            </div>
            
            <div>
                <label class="h5" for="edit_role">Role*</label>
                <select id="edit_role" name="role" style="width:100%;" required>
                    <option value="student">Student</option>
                    <option value="organizer">Organizer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div>
                <label class="h5" for="edit_intake_code">Intake Code</label>
                <input type="text" id="edit_intake_code" name="intake_code" style="width:100%;">
            </div>
            
            <div class="modal-buttons">
                <button type="submit" class="button">Update User</button>
                <button type="button" class="button" style="background: var(--color-elevated); color: var(--color-text);" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_intake_code').value = user.intake_code || '';
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deleteUser(id, name) {
    if (confirm(`Are you sure you want to delete ${name}?`)) {
        window.location.href = `manageAccounts.php?delete=${id}`;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../../assets/footer.php'; ?>