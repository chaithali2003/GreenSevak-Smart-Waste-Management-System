<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isCollector()) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = "Invalid request. No pickup ID provided.";
    $_SESSION['message_type'] = "danger";
    header("Location: assigned_pickups.php");
    exit();
}

$pickup_id = intval($_GET['id']);
$collector_id = $_SESSION['user_id'];

// Get pickup details - explicitly select p.id as pickup_id to avoid ambiguity
$query = "SELECT 
            p.id AS pickup_id,
            p.*, 
            pr.*, 
            u.name as citizen_name, 
            pr.address, 
            u.phone,
            u.email
          FROM pickups p
          JOIN pickup_requests pr ON p.request_id = pr.id
          JOIN users u ON pr.user_id = u.id
          WHERE p.id = ? AND p.collector_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $pickup_id, $collector_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Pickup not found or you don't have permission to view it.";
    $_SESSION['message_type'] = "danger";
    header("Location: assigned_pickups.php");
    exit();
}

$pickup = $result->fetch_assoc();
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Pickup Details #<?php echo htmlspecialchars($pickup['pickup_id']); ?></h2>
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Citizen Information</h5>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($pickup['citizen_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($pickup['phone']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($pickup['email']); ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Pickup Information</h5>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($pickup['type']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($pickup['pickup_date']); ?></p>
                    <p><strong>Time:</strong> <?php echo htmlspecialchars($pickup['pickup_time']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($pickup['address']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($pickup['status']); ?></p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <h5>Notes</h5>
                    <p><?php echo !empty($pickup['notes']) ? htmlspecialchars($pickup['notes']) : 'No additional notes'; ?></p>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="assigned_pickups.php" class="btn btn-secondary">Back to List</a>
                
                <?php if ($pickup['status'] == 'assigned'): ?>
                    <a href="accept_pickup.php?id=<?php echo $pickup['pickup_id']; ?>" 
                       class="btn btn-primary ms-2"
                       onclick="return confirm('Are you sure you want to accept this pickup?')">
                        Accept Pickup
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>