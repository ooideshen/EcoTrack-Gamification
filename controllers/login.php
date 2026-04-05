<?php
// login.php
// No session logic needed here usually, but good practice to allow redirect if already logged in
declare(strict_types=1);
require_once __DIR__ . '/../config/dbConnect.php';
require_once __DIR__ . '/../config/auth.php';

if(isset($_SESSION['userId'])) {
    if ($_SESSION['role'] == 'student') header("Location: /ecotrack/pages/student/studentDashboard.php");
    else if ($_SESSION['role'] == 'organizer') header("Location: /ecotrack/pages/organizer/organizerDashboard.php");
    else header("Location: /ecotrack/pages/admin/adminDashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EcoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Simple header with just logo/title -->
    <header class="header" role="banner">
        <div class="container header-bar">
            <strong class="brand">EcoTrack</strong>
        </div>
    </header>

    <main class="container" style="max-width: 480px; padding-top: var(--space-6);">
        <div class="card" style="padding: var(--space-5);">
            <h1 class="h2" style="text-align: center; margin-bottom: var(--space-4);">Welcome Back</h1>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="card bg-error" style="margin-bottom: var(--space-3);">
                    <p style="margin:0;"><?= htmlspecialchars($_GET['error']) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="card bg-success" style="margin-bottom: var(--space-3);">
                    <p style="margin:0;"><?= htmlspecialchars($_GET['msg']) ?></p>
                </div>
            <?php endif; ?>

            <form action="loginProcess.php" method="POST" style="display: grid; gap: var(--space-3);">
                <div>
                    <label class="h5" for="email" style="display: block; margin-bottom: var(--space-1);">Email</label>
                    <input type="email" id="email" name="email" 
                           placeholder="student@apu.edu.my" 
                           style="width: 100%;" required>
                </div>
                
                <div>
                    <label class="h5" for="password" style="display: block; margin-bottom: var(--space-1);">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter your password" 
                           style="width: 100%;" required>
                </div>
                
                <button type="submit" class="button" style="width: 100%; justify-content: center; margin-top: var(--space-2);">
                    Login
                </button>
            </form>
            
            <p style="text-align: center; margin-top: var(--space-4); margin-bottom: 0; font-size: var(--fs-sm); color: var(--color-text-muted);">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </main>
</body>
</html>