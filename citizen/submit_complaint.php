<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isCitizen()) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = sanitizeInput($_POST['message']);
    $date = date('Y-m-d H:i:s');
    $imagePath = null;

    // Handle image upload if present
    if (!empty($_FILES['image']['name'])) {
        $targetDir = '../uploads/complaints/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // create folder if it doesn't exist
        }

        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;

        // Validate and move the uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $imageName; // Store only the name in DB
        } else {
            $_SESSION['message'] = "Image upload failed.";
            $_SESSION['message_type'] = "danger";
            header("Location: view_complaints.php");
            exit();
        }
    }

    // Insert complaint with or without image
    $imageValue = $imagePath ? "'$imagePath'" : "NULL";
    $sql = "INSERT INTO complaints (user_id, message, image, date, status) 
            VALUES ('$user_id', '$message', $imageValue, '$date', 'pending')";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = "Complaint submitted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error submitting complaint: " . mysqli_error($conn);
        $_SESSION['message_type'] = "danger";
    }

    header("Location: view_complaints.php");
    exit();
}
?>


<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Submit Complaint</h2>
    
    <div class="card">
        <div class="card-body">
            <form action="submit_complaint.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="message" class="form-label">Complaint Details</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                <label for="image" class="form-label">Upload Image (optional)</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
            </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success" style="width: 170px">Submit Complaint</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>