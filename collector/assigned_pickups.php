<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (!isCollector()) {
    header("Location: ../index.php");
    exit();
}

$collector_id = $_SESSION['user_id'];
$assigned_pickups = getAssignedPickups($collector_id);
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Assigned Pickups</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php echo $_SESSION['message']; ?>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <?php if (!empty($assigned_pickups)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Pickup ID</th>
                                <th>Request ID</th>
                                <th>Citizen</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assigned_pickups as $pickup): ?>
                                <tr>
                                    <td><?php echo $pickup['id']; ?></td>
                                    <td><?php echo $pickup['request_id']; ?></td>
                                    <td><?php echo htmlspecialchars(getUserById($pickup['user_id'])['name']); ?></td>
                                    <td><?php echo htmlspecialchars($pickup['type']); ?></td>
                                    <td><?php echo htmlspecialchars($pickup['pickup_date']); ?></td>
                                    <td><?php echo htmlspecialchars($pickup['pickup_time']); ?></td>
                                    <td><?php echo !empty($pickup['notes']) ? htmlspecialchars($pickup['notes']) : 'None'; ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                if ($pickup['status'] == 'assigned') echo 'bg-warning';
                                                elseif ($pickup['status'] == 'in_progress') echo 'bg-primary';
                                                elseif ($pickup['status'] == 'completed') echo 'bg-success';
                                                else echo 'bg-secondary';
                                            ?>">
                                            <?php 
                                                // Display friendly status text
                                                echo ($pickup['status'] == 'in_progress') ? 'In Progress' : ucfirst($pickup['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <!-- Accept Button -->
                                            <?php if ($pickup['status'] == 'assigned'): ?>
                                                <a href="accept_pickup.php?id=<?php echo $pickup['id']; ?>" 
                                                   class="btn btn-sm btn-primary"
                                                   onclick="return confirmAccept()">
                                                    Accept
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-primary" disabled>Accept</button>
                                            <?php endif; ?>
                                            
                                            <!-- Mark Completed Button -->
                                            <?php if ($pickup['status'] == 'in_progress'): ?>
                                                <a href="mark_completed.php?id=<?php echo $pickup['id']; ?>" 
                                                   class="btn btn-sm btn-success"
                                                   onclick="return confirmComplete()">
                                                    Complete
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" disabled>Complete</button>
                                            <?php endif; ?>
                                            
                                            <!-- Cancel Button -->
                                            <?php if ($pickup['status'] == 'assigned'): ?>
                                                <a href="cancel_pickup.php?id=<?php echo $pickup['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirmCancel()">
                                                    Cancel
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" disabled>Cancel</button>
                                            <?php endif; ?>
                                            
                                            <!-- View Button -->
                                            <a href="view_pickup_details.php?id=<?php echo $pickup['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No pickups currently assigned to you.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmAccept() {
    return confirm("Are you sure you want to accept this pickup? Once accepted, you'll be responsible for completing it.");
}

function confirmComplete() {
    return confirm("Are you sure you want to mark this pickup as completed? Please ensure you've collected the waste before confirming.");
}

function confirmCancel() {
    return confirm("Are you sure you want to cancel this pickup? This will make it available for other collectors.");
}
</script>

<?php include_once '../includes/footer.php'; ?>