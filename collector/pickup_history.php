<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isCollector()) {
    header("Location: ../index.php");
    exit();
}

$collector_id = $_SESSION['user_id'];

// Fetch completed pickups with related information
$sql = "SELECT 
            p.id,
            p.completed_at,
            pr.type,
            pr.pickup_date AS scheduled_date,
            pr.pickup_time AS scheduled_time,
            pr.notes,
            u.id AS user_id,
            u.name AS citizen_name
        FROM pickups p
        JOIN pickup_requests pr ON p.request_id = pr.id
        JOIN users u ON pr.user_id = u.id
        WHERE p.collector_id = ? 
        AND p.status = 'completed'
        ORDER BY p.completed_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $collector_id);
$stmt->execute();
$result = $stmt->get_result();
$completed_pickups = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Pickup History</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <?php if (!empty($completed_pickups)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Pickup ID</th>
                                <th>Citizen</th>
                                <th>Type</th>
                                <th>Scheduled Date & Time</th>
                                <th>Completed On</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_pickups as $pickup): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pickup['id']); ?></td>
                                    <td><?php echo htmlspecialchars($pickup['citizen_name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($pickup['type'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars(date('M j, Y', strtotime($pickup['scheduled_date']))); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($pickup['scheduled_time']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($pickup['completed_at']))); ?></td>
                                    <td><?php echo !empty($pickup['notes']) ? htmlspecialchars($pickup['notes']) : 'None'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No completed pickups found in your history.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>