<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isCitizen()) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $user_id = $_SESSION['user_id'];

    // Check that the complaint exists and belongs to the current user
    $check = mysqli_query($conn, "SELECT image FROM complaints WHERE id = $complaint_id AND user_id = $user_id");

    if (mysqli_num_rows($check) === 1) {
        $complaint = mysqli_fetch_assoc($check);

        // Delete the image file if it exists
        if (!empty($complaint['image'])) {
            $imagePath = '../uploads/complaints/' . $complaint['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath); // Delete the image file
            }
        }

        // Delete the complaint from the database
        $delete = mysqli_query($conn, "DELETE FROM complaints WHERE id = $complaint_id AND user_id = $user_id");

        if ($delete) {
            $_SESSION['message'] = "Complaint deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete complaint.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Complaint not found or unauthorized.";
        $_SESSION['message_type'] = "danger";
    }

    header("Location: view_complaints.php");
    exit();
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: view_complaints.php");
    exit();
}
?>
