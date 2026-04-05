<?php
// logout.php
declare(strict_types=1);

session_start();
session_unset();
session_destroy();

// Redirect to the login page
header("Location: /ecotrack/controllers/login.php");
exit();
?>