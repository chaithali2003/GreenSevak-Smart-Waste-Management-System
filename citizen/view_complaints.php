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
$complaints = getUserComplaints($user_id);
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">My Complaints</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (!empty($complaints)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Message</th>
                                <th>Image</th>
                                <!-- <th>Date</th> -->
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td><?php echo $complaint['id']; ?></td>
                                    <td><?php echo substr($complaint['message'], 0, 50); ?>...</td>
                                    <td>
                                        <?php if (!empty($complaint['image'])): ?>
                                            <img src="uploads/complaints/<?php echo htmlspecialchars($complaint['image']); ?>" alt="Complaint Image" width="80">
                                        <?php else: ?>
                                            <em>No image</em>
                                        <?php endif; ?>
                                    </td>
<td><?php echo date("d-m-Y", strtotime($complaint['date'])); ?></td>
<!-- <td><?php echo date("h:i A", strtotime($complaint['date'])); ?></td> -->
                                    <td>
                                        <span class="badge bg-<?php echo $complaint['status'] === 'resolved' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($complaint['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if ($complaint['status'] !== 'resolved'): ?>
                                                <a href="edit_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <?php endif; ?>

                                            <form action="delete_complaint.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this complaint?');">
                                                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No complaints found. Submit your first complaint if you have any issues.</p>
                <a href="submit_complaint.php" class="btn btn-success">Submit Complaint</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
