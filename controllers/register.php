<?php
// register.php
declare(strict_types=1);
session_start();

if(isset($_SESSION['userId'])) {
    header("Location: studentDashboard.php"); // Redirect if already logged in
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EcoTrack</title>
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
            <h1 class="h2" style="text-align: center; margin-bottom: var(--space-4);">Create Account</h1>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="card bg-error" style="margin-bottom: var(--space-3);">
                    <p style="margin:0;"><?= htmlspecialchars($_GET['error']) ?></p>
                </div>
            <?php endif; ?>

            <form action="registerProcess.php" method="POST" style="display: grid; gap: var(--space-3);">
                <div>
                    <label class="h5" for="name" style="display: block; margin-bottom: var(--space-1);">Full Name</label>
                    <input type="text" id="name" name="name" 
                           placeholder="John Doe" 
                           style="width: 100%;" required>
                </div>

                <div>
                    <label class="h5" for="intake_code" style="display: block; margin-bottom: var(--space-1);">Intake Code</label>
                    <input type="text" id="intake_code" name="intake_code" 
                           placeholder="UCDF2407ICT(SE)" 
                           style="width: 100%;" required>
                </div>

                <div>
                    <label class="h5" for="email" style="display: block; margin-bottom: var(--space-1);">Email</label>
                    <input type="email" id="email" name="email" 
                           placeholder="student@apu.edu.my" 
                           style="width: 100%;" required>
                </div>
                
                <div>
                    <label class="h5" for="password" style="display: block; margin-bottom: var(--space-1);">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Create a password" 
                           style="width: 100%;" required>
                </div>

                <div>
                    <label class="h5" for="confirm_password" style="display: block; margin-bottom: var(--space-1);">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Retype password" 
                           style="width: 100%;" required>
                </div>
                
                <button type="submit" class="button" style="width: 100%; justify-content: center; margin-top: var(--space-2);">
                    Sign Up
                </button>
            </form>
            
            <p style="text-align: center; margin-top: var(--space-4); margin-bottom: 0; font-size: var(--fs-sm); color: var(--color-text-muted);">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </main>
</body>
</html>