<?php
// loginProcess.php
session_start();
require_once __DIR__ . '/../config/dbConnect.php'; // CHANGED
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT user_id, name, password_hash, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            
            login_user($user); // uses auth.php helper

            // CHANGED: Redirects to new filenames
            if ($user['role'] == 'admin') {
                header("Location: /ecotrack/pages/admin/adminDashboard.php");
            } elseif ($user['role'] == 'organizer') {
                header("Location: /ecotrack/pages/organizer/organizerDashboard.php");
            } else {
                header("Location: /ecotrack/pages/student/studentDashboard.php");
            }
            exit();
            
        } else {
            header("Location: login.php?error=Incorrect password");
            exit();
        }
    } else {
        header("Location: login.php?error=User not found");
        exit();
    }
}
?>