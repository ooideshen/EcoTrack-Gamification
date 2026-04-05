<?php
session_start();
require_once __DIR__ . '/../../config/dbConnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit;
}

$user_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT user_id, name, email, role, intake_code, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$user['created_at'] = date('M d, Y h:i A', strtotime($user['created_at']));

header('Content-Type: application/json');
echo json_encode($user);

$stmt->close();
$conn->close();
?>