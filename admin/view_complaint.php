<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Get complaint ID
$complaintId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($complaintId <= 0) {
    die("Invalid Complaint ID.");
}

// Fetch complaint
$stmt = $conn->prepare("SELECT * FROM complaints WHERE id = ?");
$stmt->bind_param("i", $complaintId);
$stmt->execute();
$result = $stmt->get_result();
$complaint = $result->fetch_assoc();

if (!$complaint) {
    die("Complaint not found.");
}

// Fetch user (citizen) who made the complaint
$user = getUserById($complaint['user_id']);

// Handle resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve'])) {
    $update = $conn->prepare("UPDATE complaints SET status = 'resolved' WHERE id = ?");
    $update->bind_param("i", $complaintId);
    $update->execute();
    $complaint['status'] = 'resolved'; // Update status locally to reflect on reload
    echo "<script>alert('Complaint marked as resolved.'); window.location.href = 'view_complaint.php?id=$complaintId';</script>";
    exit();
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="container py-4">
    <h2 class="mb-4">Complaint Details</h2>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <strong>Complaint ID: #<?php echo $complaint['id']; ?></strong>
        </div>
        <div class="card-body">
            <p><strong>Citizen Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
            <hr>
            <p><strong>Message:</strong><br><?php echo nl2br(htmlspecialchars($complaint['message'])); ?></p>
            <?php if (!empty($complaint['image'])): ?>
                <p><strong>Image:</strong><br>
                    <img src="../uploads/<?php echo htmlspecialchars($complaint['image']); ?>" alt="Complaint Image" class="img-fluid rounded" style="max-width: 400px;">
                </p>
            <?php endif; ?>
            <p><strong>Status:</strong>
                <span class="badge bg-<?php echo $complaint['status'] === 'resolved' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($complaint['status']); ?>
                </span>
            </p>
            <p><strong>Date Submitted:</strong> <?php echo $complaint['date']; ?></p>
        </div>
    </div>

    <?php if ($complaint['status'] !== 'resolved'): ?>
        <form method="post" onsubmit="return confirm('Are you sure you want to mark this complaint as resolved?');">
            <button type="submit" name="resolve" class="btn btn-success">
                <i class="fas fa-check-circle me-1"></i> Mark as Resolved
            </button>
            <a href="manage_complaints.php" class="btn btn-secondary ms-2">Back</a>
        </form>
    <?php else: ?>
        <a href="manage_complaints.php" class="btn btn-secondary">Back to List</a>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
