<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Handle "Mark Resolved" POST action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
    $complaintId = (int)$_POST['resolve_id'];

    $stmt = $conn->prepare("UPDATE complaints SET status = 'resolved' WHERE id = ?");
    $stmt->bind_param("i", $complaintId);
    $stmt->execute();

    // Optional: redirect to avoid resubmission
    header("Location: manage_complaints.php");
    exit();
}

$complaints = getAllComplaints();
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Manage Complaints</h2>

    <div class="card">
        <div class="card-body">
            <?php if (!empty($complaints)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Citizen</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td><?php echo $complaint['id']; ?></td>
                                    <td><?php echo getUserById($complaint['user_id'])['name']; ?></td>
                                    <td><?php echo substr($complaint['message'], 0, 50); ?>...</td>
                                    <td><?php echo $complaint['date']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $complaint['status'] === 'resolved' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($complaint['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-info">View</a>
                                        
                                        <?php if ($complaint['status'] !== 'resolved'): ?>
                                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to mark this complaint as resolved?');">
                                                <input type="hidden" name="resolve_id" value="<?php echo $complaint['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Mark Resolved</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No complaints found</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
