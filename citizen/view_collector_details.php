<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isCitizen()) {
    header("Location: ../index.php");
    exit();
}

// Validate pickup_id parameter
if (!isset($_GET['pickup_id']) || !ctype_digit($_GET['pickup_id'])) {
    $_SESSION['message'] = "Invalid pickup request";
    $_SESSION['message_type'] = "danger";
    header("Location: pickup_history.php");
    exit();
}

$pickup_id = intval($_GET['pickup_id']);
$user_id = $_SESSION['user_id'];

// Get pickup details with collector information
$query = "SELECT 
            pr.id AS pickup_request_id,
            pr.*,
            p.*,
            u.name AS citizen_name,
            c.id AS collector_id,
            c.name AS collector_name,
            c.email AS collector_email,
            c.phone AS collector_phone,
            c.vehicle_number
          FROM pickup_requests pr
          LEFT JOIN pickups p ON p.request_id = pr.id
          LEFT JOIN users u ON pr.user_id = u.id
          LEFT JOIN users c ON p.collector_id = c.id
          WHERE pr.id = ? AND pr.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $pickup_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Pickup not found or you don't have permission to view it";
    $_SESSION['message_type'] = "danger";
    header("Location: pickup_history.php");
    exit();
}

$pickup = $result->fetch_assoc();
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Collector Details for Pickup #<?php echo htmlspecialchars($pickup['pickup_request_id']); ?></h2>
    
    <div class="card">
        <div class="card-body">
            <?php if (!empty($pickup['collector_id'])): ?>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Collector Information</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($pickup['collector_name']); ?></p>
                        <p><strong>Email:</strong> 
                                <?php echo htmlspecialchars($pickup['collector_email']); ?>
                        </p>
                        <p><strong>Phone:</strong> 
                                <?php echo htmlspecialchars($pickup['collector_phone']); ?>
                        </p>
                        <p><strong>Vehicle Number:</strong> <?php echo htmlspecialchars($pickup['vehicle_number']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Pickup Information</h5>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                switch(strtolower($pickup['status'])) {
                                    case 'completed': echo 'success'; break;
                                    case 'in progress': 
                                    case 'in_progress': echo 'primary'; break;
                                    case 'assigned': echo 'warning'; break;
                                    case 'cancelled': echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $pickup['status'])); ?>
                            </span>
                        </p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($pickup['type']); ?></p>
                        <p><strong>Scheduled Date:</strong> <?php echo htmlspecialchars($pickup['pickup_date']); ?></p>
                        <p><strong>Scheduled Time:</strong> <?php echo htmlspecialchars($pickup['pickup_time']); ?></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Pickup Notes</h5>
                        <p><?php echo !empty($pickup['notes']) ? htmlspecialchars($pickup['notes']) : 'No additional notes'; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h5>No Collector Assigned Yet</h5>
                    <p>Your pickup request has not been assigned to a collector yet. Please check back later.</p>
                    <p>Current Status: <span class="badge bg-secondary"><?php echo ucfirst($pickup['status']); ?></span></p>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="pickup_history.php" class="btn btn-secondary">Back to History</a>
                
                <?php if ($pickup['status'] === 'in_progress'): ?>
                    <button class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#contactModal">
                        Contact Collector
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalLabel">Contact Collector</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($pickup['collector_name']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($pickup['collector_phone']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($pickup['collector_email']); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>