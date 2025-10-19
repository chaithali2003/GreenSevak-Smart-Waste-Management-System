<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not a citizen
redirectIfNotLoggedIn();
if (!isCitizen()) {
    header("Location: ../index.php");
    exit();
}
function getUserActivePickups($user_id) {
    global $conn;
    
    $query = "SELECT * FROM pickup_requests 
              WHERE user_id = ? 
              AND status IN ('Pending', 'In Progress', 'Completed', 'Assigned') 
              ORDER BY pickup_date DESC, pickup_time DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pickups = [];
    while ($row = $result->fetch_assoc()) {
        $pickups[] = $row;
    }
    
    return $pickups;
}

// Set timezone (adjust to your location)
date_default_timezone_set('Asia/Kolkata');

$user_id = $_SESSION['user_id'];
// Modify the function call to filter by status
$pickups = getUserActivePickups($user_id); // We'll need to create this function

/**
 * Check if a pickup is expired
 * @param array $pickup The pickup data
 * @return bool True if expired, false otherwise
 */
function isPickupExpired($pickup) {
    // If no date or time, can't be expired
    if (empty($pickup['pickup_date']) || empty($pickup['pickup_time'])) {
        return false;
    }

    // Get current date and time
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');
    
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
        
        // Convert to 24-hour format for comparison
        $endTime24 = date('H:i:s', strtotime($endTime));
        
        return ($endTime24 < $currentTime);
    } 
    // Handle single time (e.g., "10:00 AM")
    else {
        $pickupTime = date('H:i:s', strtotime($timeSlot));
        return ($pickupTime < $currentTime);
    }
}
?>
<?php include_once '../includes/header.php'; ?>

    <div class="container pb-3">
        <h2 class="my-4">Pickup History</h2>
        
        <div class="card">
            <div class="card-body">
                <?php if (!empty($pickups)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Type</th>
                                    <th>Pickup Date</th>
                                    <th>Pickup Time</th>
                                    <th>Status</th>
                                    <th>Collector</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pickups as $pickup): 
                                    $isExpired = isPickupExpired($pickup);
                                    $status = strtolower($pickup['status']);
                                    $isPending = ($status === 'pending');
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pickup['id']) ?></td>
                                        <td><?= htmlspecialchars($pickup['type']) ?></td>
                                        <td>
                                            <?php if (!empty($pickup['pickup_date'])): ?>
                                                <?= date("d-m-Y", strtotime($pickup['pickup_date'])) ?>
                                            <?php else: ?>
                                                Not set
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($pickup['pickup_time'])): ?>
                                                <?= htmlspecialchars($pickup['pickup_time']) ?>
                                                <?php if ($isExpired && $isPending): ?>
                                                    <span class="badge bg-danger ms-2">Expired</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Not set
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                match($status) {
                                                    'pending' => 'secondary',
                                                    'in progress', 'in_progress' => 'primary',
                                                    'completed' => 'success',
                                                    'assigned' => 'warning',
                                                    default => 'secondary'
                                                }
                                            ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($pickup['collector_id']) && in_array($status, ['in progress', 'in_progress', 'assigned'])): ?>
                                                <a href="view_collector_details.php?pickup_id=<?= $pickup['id'] ?>" 
                                                   class="btn btn-sm btn-info">
                                                    View
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>View</button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isPending): ?>
                                                <?php if ($isExpired): ?>
                                                    <!-- Expired Pickup Actions -->
                                                    <a href="edit_pickup.php?id=<?= $pickup['id'] ?>" 
                                                       class="btn btn-sm btn-primary me-1"
                                                       title="Reschedule this expired pickup">
                                                        Reschedule
                                                    </a>
                                                    <form action="delete_pickup.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="pickup_id" value="<?= $pickup['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Cancel this expired pickup?')">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <!-- Active Pickup Actions -->
                                                    <a href="edit_pickup.php?id=<?= $pickup['id'] ?>" 
                                                       class="btn btn-sm btn-warning me-1">
                                                        Edit
                                                    </a>
                                                    <form action="delete_pickup.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="pickup_id" value="<?= $pickup['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Cancel this pickup?')">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Non-pending status actions -->
                                                <button class="btn btn-sm btn-secondary me-1" disabled>Edit</button>
                                                <button class="btn btn-sm btn-secondary" disabled>Cancel</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No pickup history found.</p>
                        <a href="schedule_pickup.php" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Schedule Your First Pickup
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include_once '../includes/footer.php'; ?>

</body>
</html>