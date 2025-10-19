<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$total_users = count(getAllCitizens());
$total_collectors = count(getAllCollectors());
$pending_pickups = countPendingPickupRequests(); // from pickup_requests table
$completed_pickups = countCompletedPickups();    // from pickups table
$recent_pickups = array_slice(getPickupRequestsByStatus('Pending'), 0, 5);
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Admin Dashboard</h2>
    <div class="row">
        <div class="col-md-3 mb-4">
    <div class="card bg-primary text-white position-relative">
        <div class="card-body">
            <h5 class="card-title">Citizens</h5>
            <h2 class="card-text"><?php echo $total_users; ?></h2>
            <a href="manage_citizens.php" class="text-white">View All</a>
            <i class="fas fa-users fa-4x position-absolute" style="top: 40px; right: 50px;"></i>
        </div>
    </div>
</div>

        <div class="col-md-3 mb-4">
    <div class="card bg-info text-white position-relative">
        <div class="card-body">
            <h5 class="card-title">Waste Collectors</h5>
            <h2 class="card-text"><?php echo $total_collectors; ?></h2>
            <a href="manage_collectors.php" class="text-white">View All</a>
            <i class="fas fa-user-cog fa-4x position-absolute" style="top: 40px; right: 50px;"></i>
        </div>
    </div>
</div>

        <div class="col-md-3 mb-4">
    <div class="card bg-warning text-dark position-relative">
        <div class="card-body">
            <h5 class="card-title">Pending Pickups</h5>
            <h2 class="card-text"><?php echo $pending_pickups; ?></h2>
            <a href="manage_pickups.php" class="text-dark">View All</a>
            <i class="fas fa-exclamation-triangle fa-4x position-absolute" style="top: 40px; right: 50px;"></i>
        </div>
    </div>
</div>

        <div class="col-md-3 mb-4">
    <div class="card bg-success text-white position-relative">
        <div class="card-body">
            <h5 class="card-title">Completed Pickups</h5>
            <h2 class="card-text"><?php echo $completed_pickups; ?></h2>
            <a href="manage_pickups.php" class="text-white">View All</a>
            <i class="fas fa-clipboard-check fa-4x position-absolute" style="top: 40px; right: 50px;"></i>
        </div>
    </div>
</div>

    </div>

    <div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                Recent Pickup Requests
            </div>
            <div class="card-body">
                <?php
                // ✅ Correct function to fetch from pickup_requests table
                $recent_pickups = array_slice(getPickupRequestsByStatus('Pending'), 0, 5);
                if (!empty($recent_pickups)):
                ?>
                    <ul class="list-group">
                        <?php foreach ($recent_pickups as $pickup): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($pickup['citizen_name']) ?></strong>
                                - <?= htmlspecialchars($pickup['type']) ?> on 
                                <?= date("d-m-Y", strtotime($pickup['pickup_date'])) ?> at 
                                <?= date("h:i A", strtotime(explode('-', $pickup['pickup_time'])[0])) ?> -
                                <?= date("h:i A", strtotime(explode('-', $pickup['pickup_time'])[1])) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No recent pickup requests</p>
                <?php endif; ?>
                <a href="manage_pickups.php" class="btn btn-success mt-3">View All Pickups</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                Recent Complaints
            </div>
            <div class="card-body">
                <?php
                $recent_complaints = array_slice(getAllComplaints(), 0, 5);
                if (!empty($recent_complaints)):
                ?>
                    <ul class="list-group">
                        <?php foreach ($recent_complaints as $complaint): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars(getUserById($complaint['user_id'])['name']) ?></strong>
                                - <?= htmlspecialchars(substr($complaint['message'], 0, 50)) ?>...
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No recent complaints</p>
                <?php endif; ?>
                <a href="manage_complaints.php" class="btn btn-success mt-3">View All Complaints</a>
            </div>
        </div>
    </div>
</div>

    </div>
</div>

<?php include_once '../includes/footer.php'; ?>