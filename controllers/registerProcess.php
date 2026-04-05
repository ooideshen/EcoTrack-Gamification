<?php
// registerProcess.php
require_once __DIR__ . '/../config/dbConnect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $name = $_POST['name'];
    $intake = $_POST['intake_code'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $confirmPass = $_POST['confirm_password']; // CHANGED: camelCase

    if ($pass !== $confirmPass) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    $checkSql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: register.php?error=Email is already registered");
        exit();
    }
    $stmt->close();

    $hashedPassword = password_hash($pass, PASSWORD_DEFAULT); // CHANGED: camelCase
    
    $role = 'student'; 

    $sql = "INSERT INTO users (name, email, password_hash, role, intake_code) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $intake);

    if ($stmt->execute()) {
        header("Location: login.php?error=Account created! Please login.");
    } else {
        header("Location: register.php?error=Database error: " . $conn->error);
    }

    $stmt->close();
    $conn->close();
}
?>