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

function countCollectorCompletedPickups($collector_id) {
    global $conn; // MySQLi connection
    
    $query = "SELECT COUNT(*) FROM pickups 
              WHERE collector_id = ? 
              AND status = 'completed'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $collector_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    return $count ? $count : 0;
}

// Get the count
$completed_count = countCollectorCompletedPickups($collector_id);
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Collector Dashboard</h2>
    
    <div class="row">
        <div class="col-md-4 mb-4">
    <div class="card bg-primary text-white">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title">Assigned Pickups</h5>
                <h2 class="card-text"><?php echo count($assigned_pickups); ?></h2>
                <a href="assigned_pickups.php" class="text-white">View</a>
            </div>
            <i class="fas fa-tasks fa-4x me-3"></i>
        </div>
    </div>
</div>

<div class="col-md-4 mb-4">
    <div class="card bg-success text-white">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title">Completed Pickups</h5>
                <h2 class="card-text"><?= htmlspecialchars($completed_count) ?></h2>
                <a href="pickup_history.php" class="text-white">View History</a>
            </div>
            <i class="fas fa-check-circle fa-4x me-3"></i>
        </div>
    </div>
</div>

<div class="col-md-4 mb-4">
    <div class="card bg-info text-white">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title">Current Time</h5>
                <h2 class="card-text" id="live-clock"><?php echo date('h:i:s A'); ?></h2>
                <div><?php echo date('M d, Y'); ?></div>
            </div>
            <i class="fas fa-clock fa-4x me-3"></i>
        </div>
    </div>
</div>

    </div>
    
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Today's Assigned Pickups</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($assigned_pickups)): ?>
                <ul class="list-group">
                    <?php foreach ($assigned_pickups as $pickup): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo getUserById($pickup['user_id'])['name']; ?></strong>
                                <div class="text-muted">
                                    <?php echo $pickup['type']; ?> - <?php echo $pickup['pickup_date']; ?> at <?php echo $pickup['pickup_time']; ?>
                                </div>
                                <div class="mt-1">
                                    <?php echo $pickup['notes']; ?>
                                </div>
                            </div>
                            <a href="assigned_pickups.php" class="btn btn-sm btn-primary">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No pickups assigned for today.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Live updating clock
function updateClock() {
    const now = new Date();
    const hours = now.getHours() % 12 || 12;
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
    document.getElementById('live-clock').textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
}

// Update clock every second
updateClock();
setInterval(updateClock, 1000);
</script>

<?php include_once '../includes/footer.php'; ?>