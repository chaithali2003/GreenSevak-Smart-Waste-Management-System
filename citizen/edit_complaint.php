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

// Step 1: Validate and fetch the complaint
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "Invalid complaint ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: view_complaints.php");
    exit();
}

$complaint_id = intval($_GET['id']);

// Fetch complaint
$query = mysqli_query($conn, "SELECT * FROM complaints WHERE id = $complaint_id AND user_id = $user_id");
if (mysqli_num_rows($query) !== 1) {
    $_SESSION['message'] = "Complaint not found or unauthorized access.";
    $_SESSION['message_type'] = "danger";
    header("Location: view_complaints.php");
    exit();
}

$complaint = mysqli_fetch_assoc($query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = sanitizeInput($_POST['message']);
    $imagePath = $complaint['image']; // existing image

    // Handle new image upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = '../uploads/complaints/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Delete old image
            if (!empty($imagePath) && file_exists($targetDir . $imagePath)) {
                unlink($targetDir . $imagePath);
            }

            $imagePath = $imageName; // update image path
        }
    }

    // Update complaint in DB
    $stmt = mysqli_prepare($conn, "UPDATE complaints SET message = ?, image = ? WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ssii", $message, $imagePath, $complaint_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "Complaint updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating complaint.";
        $_SESSION['message_type'] = "danger";
    }

    header("Location: view_complaints.php");
    exit();
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Edit Complaint</h2>
    <div class="card">
    <div class="card-body">
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="message" class="form-label">Complaint Details</label>
            <textarea name="message" id="message" class="form-control" rows="5" required><?php echo htmlspecialchars($complaint['message']); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Image</label><br>
            <?php if (!empty($complaint['image'])): ?>
                <img src="uploads/complaints/<?php echo htmlspecialchars($complaint['image']); ?>" width="150" alt="Complaint Image"><br><br>
            <?php else: ?>
                <em>No image uploaded.</em><br><br>
            <?php endif; ?>
            <label for="image" class="form-label">Change Image (optional)</label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*">
        </div>

        <button type="submit" class="btn btn-primary">Update Complaint</button>
        <a href="view_complaints.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</div>
</div>

<?php include_once '../includes/footer.php'; ?>
