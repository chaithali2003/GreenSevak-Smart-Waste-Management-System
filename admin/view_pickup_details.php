<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$pickup_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get pickup details
$pickup = [];
$citizen = [];
$collector = [];

if ($pickup_id > 0) {
    // Get pickup info
    $stmt = $conn->prepare("SELECT * FROM pickups WHERE id = ?");
    $stmt->bind_param("i", $pickup_id);
    $stmt->execute();
    $pickup = $stmt->get_result()->fetch_assoc();
    
    if ($pickup) {
        // Get citizen details
        $citizen = getUserById($pickup['user_id']);
        
        // Get collector details
        $collector = getUserById($pickup['collector_id']);
    }
}

$title = "Pickup Details #".$pickup_id;
include_once '../includes/header.php';
?>

<div class="container pb-3">
    <div class="card mt-4">
        <div class="card-header">
            <h4>Pickup Details - #<?= $pickup_id ?></h4>
        </div>
        <div class="card-body">
            <?php if ($pickup): ?>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Pickup Information</h5>
                        <p><strong>Status:</strong> <?= ucfirst(str_replace('_', ' ', $pickup['status'])) ?></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($pickup['type']) ?></p>
                        <p><strong>Date:</strong> <?= date("d-m-Y", strtotime($pickup['pickup_date'])) ?></p>
                        <p><strong>Time Slot:</strong> <?= htmlspecialchars($pickup['pickup_time']) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($pickup['address']) ?></p>
                        <p><strong>Notes:</strong> <?= htmlspecialchars($pickup['notes']) ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Citizen Details</h5>
                        <?php if ($citizen): ?>
                            <p><strong>ID:</strong> <?= $citizen['id'] ?></p>
                            <p><strong>Name:</strong> <?= htmlspecialchars($citizen['name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($citizen['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($citizen['phone']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($citizen['address']) ?></p>
                        <?php else: ?>
                            <p>Citizen details not found</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6 mt-4">
                        <h5>Collector Details</h5>
                        <?php if ($collector): ?>
                            <p><strong>ID:</strong> <?= $collector['id'] ?></p>
                            <p><strong>Name:</strong> <?= htmlspecialchars($collector['name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($collector['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($collector['phone']) ?></p>
                            <p><strong>Vehicle Number:</strong> <?= htmlspecialchars($collector['vehicle_number']) ?></p>
                        <?php else: ?>
                            <p>Collector details not found</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">Pickup details not found</div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="manage_pickups.php" class="btn btn-secondary">Close</a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>