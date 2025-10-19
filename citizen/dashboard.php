<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (!isCitizen()) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pending_pickups = array_slice(getUserPickups($user_id), 0, 3);
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Dashboard</h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Welcome, <?php echo htmlspecialchars(strtoupper($_SESSION['name'])); ?></h5>
                </div>
                <div class="card-body">
                    <p>Thank you for being a responsible citizen and using GreenSevak for your waste management needs.</p>
                    <p>Together, we can make our city cleaner and greener!</p>
                    <a href="schedule_pickup.php" class="btn btn-success">Schedule a Pickup</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Recent Pickup Requests</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($pending_pickups)): ?>
                        <ul class="list-group">
                            <?php foreach ($pending_pickups as $pickup): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($pickup['type']); ?></strong>
                                        <div class="text-muted">
                                            <?php
                                            echo "Pickup scheduled on ";

                                            // Safely display date
                                            if (!empty($pickup['pickup_date']) && strtotime($pickup['pickup_date'])) {
                                                echo date("d-m-Y", strtotime($pickup['pickup_date']));
                                            } else {
                                                echo "Date not set";
                                            }

                                            echo " at ";

                                            // Safely display time slot
                                            if (!empty($pickup['pickup_time'])) {
                                                $slot = $pickup['pickup_time'];

                                                if (strpos($slot, '-') !== false) {
                                                    $parts = explode('-', $slot);
                                                    if (count($parts) === 2 && strtotime($parts[0]) && strtotime($parts[1])) {
                                                        $start = trim($parts[0]);
                                                        $end = trim($parts[1]);
                                                        echo date("h:i A", strtotime($start)) . " - " . date("h:i A", strtotime($end));
                                                    } else {
                                                        echo "Invalid time slot";
                                                    }
                                                } elseif (strtotime($slot)) {
                                                    echo date("h:i A", strtotime($slot));
                                                } else {
                                                    echo "Invalid time";
                                                }
                                            } else {
                                                echo "Time not set";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                    $status = strtolower($pickup['status']);
                                    $badgeColors = [
                                        'pending' => 'secondary',
                                        'in progress' => 'primary',
                                        'completed' => 'success',
                                        'assigned' => 'warning',
                                        'cancelled' => 'danger',
                                    ];

                                    $badgeClass = $badgeColors[$status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="pickup_history.php" class="btn btn-success mt-3">View All Pickups</a>
                    <?php else: ?>
                        <p>No recent pickup requests. Schedule your first pickup now!</p>
                        <a href="schedule_pickup.php" class="btn btn-success">Schedule Pickup</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="schedule_pickup.php" class="btn btn-outline-success">Schedule Pickup</a>
                        <a href="submit_complaint.php" class="btn btn-outline-success">Submit Complaint</a>
                        <a href="pickup_history.php" class="btn btn-outline-success">View History</a>
                        <a href="view_complaints.php" class="btn btn-outline-success">View Complaints</a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Waste Collection Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Separate dry and wet waste</li>
                        <li class="list-group-item">Clean containers before disposal</li>
                        <li class="list-group-item">Schedule pickups in advance</li>
                        <li class="list-group-item">Report any issues promptly</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>