<?php
// submitProcess.php
session_start();
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/dbConnect.php';
require_role('student');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userId = $_SESSION['user']['id'];
    $activityId = $_POST['activity_id'];
    $quantity = (int)$_POST['quantity']; // NEW: Capture Quantity
    $challengeId = !empty($_POST['challenge_id']) ? $_POST['challenge_id'] : NULL;
    $notes = $_POST['notes'];

    // Basic Validation
    if ($quantity < 1) $quantity = 1;

    $uploadDir = __DIR__ . "/../../assets/uploads/";
    $fileExtension = strtolower(pathinfo($_FILES["proof_photo"]["name"], PATHINFO_EXTENSION));
    $newFilename = "user_" . $userId . "_" . time() . "." . $fileExtension;
    $targetFile = $uploadDir . $newFilename;
    $dbFilePath = "assets/uploads/" . $newFilename;
    
    // Check image
    $check = getimagesize($_FILES["proof_photo"]["tmp_name"]);
    if($check === false) {
        header("Location: submitAction.php?error=File is not an image.");
        exit();
    }

    if($fileExtension != "jpg" && $fileExtension != "png" && $fileExtension != "jpeg") {
        header("Location: submitAction.php?error=Only JPG & PNG allowed.");
        exit();
    }

    if (move_uploaded_file($_FILES["proof_photo"]["tmp_name"], $targetFile)) {
        
        // UPDATED SQL: Added 'quantity' column
        $sql = "INSERT INTO submission_proof (user_id, activity_id, challenge_id, quantity, file_path, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($sql);
        // UPDATED BIND: Added 'i' for quantity (types: iiiiss)
        $stmt->bind_param("iiiiss", $userId, $activityId, $challengeId, $quantity, $dbFilePath, $notes);

        if ($stmt->execute()) {
            header("Location: submitAction.php?msg=Success! Your submission is pending verification.");
        } else {
            header("Location: submitAction.php?error=Database error.");
        }
    } else {
        header("Location: submitAction.php?error=Error uploading file.");
    }
}
?>