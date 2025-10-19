<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kolkata');

// Function to check if a pickup is expired (similar to citizen version)
function isPickupExpired($pickup) {
    // If no date or time, can't be expired
    if (empty($pickup['pickup_date']) || empty($pickup['pickup_time'])) {
        return false;
    }

    // Get current date and time
    $currentDate = date('Y-m-d');
    $currentTime = time(); // Current timestamp for accurate comparison
    
    // Compare dates first
    if ($pickup['pickup_date'] < $currentDate) {
        return true; // Past date = expired
    } 
    elseif ($pickup['pickup_date'] > $currentDate) {
        return false; // Future date = not expired
    }
    
    // For today's date, check the time
    $timeSlot = $pickup['pickup_time'];
    
    // Handle time ranges (e.g., "09:00 AM - 11:00 AM")
    if (strpos($timeSlot, '-') !== false) {
        $parts = explode('-', $timeSlot);
        $endTime = trim(end($parts)); // Get the end time of the slot
        
        // Create full datetime string for comparison
        $pickupEnd = strtotime($pickup['pickup_date'] . ' ' . $endTime);
        
        return ($pickupEnd < $currentTime);
    } 
    // Handle single time (e.g., "10:00 AM")
    else {
        $pickupTime = strtotime($pickup['pickup_date'] . ' ' . $pickup['pickup_time']);
        return ($pickupTime < $currentTime);
    }
}

// Function to get pickup requests by status
function getPickupRequestsByStatus($status) {
    global $conn;
    $query = "SELECT pr.*, u.name as citizen_name 
              FROM pickup_requests pr
              JOIN users u ON pr.user_id = u.id
              WHERE pr.status = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get pickups by status
function getPickupsByStatus($status) {
    global $conn;
    $query = "SELECT p.*, pr.type, pr.pickup_date, pr.pickup_time, u.name as citizen_name, c.name as collector_name
              FROM pickups p
              JOIN pickup_requests pr ON p.request_id = pr.id
              JOIN users u ON pr.user_id = u.id
              LEFT JOIN users c ON p.collector_id = c.id
              WHERE p.status = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get pickup by request ID
function getPickupByRequestId($request_id) {
    global $conn;
    $query = "SELECT * FROM pickups WHERE request_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get user by ID
function getUserById($user_id) {
    global $conn;
    $query = "SELECT name FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get pickup request status
function getPickupRequestStatus($request_id) {
    global $conn;
    $query = "SELECT status FROM pickup_requests WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['status'] ?? null;
}

// Get all data using the local functions
$pending_requests = getPickupRequestsByStatus('Pending');
$assigned_pickups = getPickupsByStatus('assigned');
$in_progress_pickups = getPickupsByStatus('in_progress');
$completed_pickups = getPickupsByStatus('completed');
$cancelled_pickups = getPickupsByStatus('cancelled');

$active_pickups = array_merge($assigned_pickups, $in_progress_pickups);
?>

<?php include_once '../includes/header.php'; ?>

<div class="container">
    <h2 class="my-4">Manage Pickup Requests</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="pickupTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active text-success" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">Pending Requests</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-success" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">Assigned/In Progress</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-success" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">Completed</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-success" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button" role="tab">Cancelled</button>
        </li>
    </ul>

    <div class="tab-content pb-3" id="pickupTabsContent">

        <!-- Pending Requests Tab -->
        <div class="tab-pane fade show active" id="pending" role="tabpanel">
        <div class="card mt-3">
            <div class="card-body">
                <?php if (!empty($pending_requests)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Citizen</th>
                                    <th>Type</th>
                                    <th>Pickup Date</th>
                                    <th>Pickup Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_requests as $request): 
                                    // Improved expiration check
                                    $is_expired = false;
                                    if (!empty($request['pickup_date']) && !empty($request['pickup_time'])) {
                                        $pickup_date = $request['pickup_date'];
                                        $time_slot = $request['pickup_time'];
                                        
                                        // Get the end time of the slot
                                        $time_parts = explode('-', $time_slot);
                                        if (count($time_parts) === 2) {
                                            $end_time = trim($time_parts[1]);
                                            $pickup_end = strtotime($pickup_date . ' ' . $end_time);
                                            $current_time = time();
                                            
                                            if ($pickup_end !== false) {
                                                $is_expired = ($pickup_end < $current_time);
                                            }
                                        }
                                    }

                                    $pickup = getPickupByRequestId($request['id']);
                                    $pickup_status = $pickup['status'] ?? null;
                                    $can_assign = !$is_expired && (is_null($pickup_status) || strtolower($pickup_status) === 'cancelled');
                                ?>
                                    <tr>
                                        <td><?= $request['id'] ?></td>
                                        <td><?= htmlspecialchars($request['citizen_name']) ?></td>
                                        <td><?= htmlspecialchars($request['type']) ?></td>
                                        <td><?= date("d-m-Y", strtotime($request['pickup_date'])) ?></td>
                                        <td>
                                            <?php if (!empty($request['pickup_time'])): 
                                                $time_parts = explode('-', $request['pickup_time']);
                                                if (count($time_parts) === 2): ?>
                                                    <?= date("h:i A", strtotime(trim($time_parts[0]))) ?> - 
                                                    <?= date("h:i A", strtotime(trim($time_parts[1]))) ?>
                                                <?php else: ?>
                                                    <?= date("h:i A", strtotime($request['pickup_time'])) ?>
                                                <?php endif; ?>
                                                
                                                <?php if ($is_expired): ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Time not set
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-secondary">Pending</span></td>
                                        <td>
                                            <?php if ($can_assign): ?>
                                                <a href="assign_collector.php?id=<?= $request['id'] ?>" class="btn btn-success">Assign Collector</a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled title="Collector already assigned or request expired">
                                                    Assign Collector
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No pending pickup requests</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

        <!-- Active Tab (Assigned/In Progress) -->
        <div class="tab-pane fade" id="active" role="tabpanel">
            <div class="card mt-3">
                <div class="card-body">
                    <?php if (!empty($active_pickups)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Pickup ID</th>
                                        <th>Request ID</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_pickups as $pickup): ?>
                                        <tr>
                                            <td><?= $pickup['id'] ?></td>
                                            <td><?= $pickup['request_id'] ?? 'N/A' ?></td>
                                            <td><?= htmlspecialchars($pickup['type']) ?></td>
                                            <td><?= date("d-m-Y", strtotime($pickup['pickup_date'])) ?></td>
                                            <td>
                                                <?= date("h:i A", strtotime(explode('-', $pickup['pickup_time'])[0])) . ' - ' .
                                                   date("h:i A", strtotime(explode('-', $pickup['pickup_time'])[1])) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $pickup['status'] === 'assigned' ? 'warning' : 'primary' ?>">
                                                    <?= ucfirst($pickup['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_pickup_details.php?id=<?= $pickup['id'] ?>" class="btn btn-sm btn-info">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No assigned or in-progress pickups</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Completed Tab -->
        <div class="tab-pane fade" id="completed" role="tabpanel">
            <div class="card mt-3">
                <div class="card-body">
                    <?php if (!empty($completed_pickups)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Pickup ID</th>
                                        <th>Request ID</th>
                                        <th>Citizen</th>
                                        <th>Collector</th>
                                        <th>Type</th>
                                        <th>Scheduled Date</th>
                                        <th>Completed On</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_pickups as $pickup): ?>
                                        <tr>
                                            <td><?= $pickup['id'] ?></td>
                                            <td><?= $pickup['request_id'] ?></td>
                                            <td><?= htmlspecialchars($pickup['citizen_name']) ?></td>
                                            <td><?= htmlspecialchars($pickup['collector_name']) ?></td>
                                            <td><?= htmlspecialchars($pickup['type']) ?></td>
                                            <td><?= date("d-m-Y", strtotime($pickup['pickup_date'])) ?></td>
                                            <td><?= date("d-m-Y H:i", strtotime($pickup['completed_at'])) ?></td>
                                            <td><span class="badge bg-success">Completed</span></td>
                                            <td>
                                                <a href="view_pickup_details.php?id=<?= $pickup['id'] ?>" class="btn btn-sm btn-info">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No completed pickups</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cancelled Tab -->
<div class="tab-pane fade" id="cancelled" role="tabpanel">
    <div class="card mt-3">
        <div class="card-body">
            <?php 
            // Filter to only show truly cancelled pickups (where request_id doesn't exist with other statuses)
            $truly_cancelled = array_filter($cancelled_pickups, function($pickup) use ($conn) {
                $request_id = $pickup['request_id'];
                $stmt = $conn->prepare("SELECT COUNT(*) FROM pickups WHERE request_id = ? AND status != 'cancelled'");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_row()[0];
                return $count === 0;
            });
            
            if (!empty($truly_cancelled)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Pickup ID</th>
                                <th>Request ID</th>
                                <th>Citizen</th>
                                <th>Collector</th>
                                <th>Type</th>
                                <th>Scheduled Date</th>
                                <th>Cancelled On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($truly_cancelled as $pickup): ?>
                                <tr>
                                    <td><?= $pickup['id'] ?></td>
                                    <td><?= $pickup['request_id'] ?></td>
                                    <td><?= htmlspecialchars($pickup['citizen_name']) ?></td>
                                    <td><?= htmlspecialchars($pickup['collector_name']) ?></td>
                                    <td><?= htmlspecialchars($pickup['type']) ?></td>
                                    <td><?= date("d-m-Y", strtotime($pickup['pickup_date'])) ?>
                                    <br><small><?= htmlspecialchars($pickup['pickup_time']) ?></small>
                                    </td></td>
                                    <td><?= date("d-m-Y h:i A", strtotime($pickup['cancelled_at'])) ?></td>
                                    <td><span class="badge bg-danger">Cancelled</span></td>
                                    <td>
                                        <a href="view_pickup_details.php?id=<?= $pickup['id'] ?>" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No cancelled pickups found
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>