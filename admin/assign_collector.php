<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pickup_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch pickup request details
$pickup_request = [];
$is_expired = false;

if ($pickup_id > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT pr.*, u.name AS citizen_name, u.email AS citizen_email, u.phone AS citizen_phone,
                   CONCAT(pr.pickup_date, ' ', SUBSTRING_INDEX(pr.pickup_time, '-', -1)) AS end_datetime
            FROM pickup_requests pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.id = ? AND pr.status = 'Pending'
        ");
        $stmt->bind_param("i", $pickup_id);
        $stmt->execute();
        $pickup_request = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($pickup_request) {
            $is_expired = (strtotime($pickup_request['end_datetime']) < time());
        }
    } catch (Exception $e) {
        error_log("Pickup request fetch error: " . $e->getMessage());
        $_SESSION['message'] = "System error fetching pickup request";
        $_SESSION['message_type'] = "danger";
        header("Location: manage_pickups.php");
        exit();
    }
}

// Validate pickup request
if (!$pickup_request) {
    $_SESSION['message'] = "Pickup request not found (ID: $pickup_id) or already processed";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_pickups.php");
    exit();
}

if ($is_expired) {
    $expiry_time = date('M j, Y g:i A', strtotime($pickup_request['end_datetime']));
    $_SESSION['message'] = "Cannot assign - pickup time expired on $expiry_time";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_pickups.php");
    exit();
}

// Fetch available collectors (active status only)
try {
    $collectors = [];
    $result = $conn->query("
        SELECT id, name, email, phone, vehicle_number, address 
        FROM users 
        WHERE role = 'collector' AND status = 'active'
    ");
    
    if ($result) {
        $collectors = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Query failed: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Collector fetch error: " . $e->getMessage());
    $_SESSION['message'] = "Error loading collector list";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_pickups.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    $collector_id = intval($_POST['collector_id']);
    $notes = sanitizeInput($_POST['notes'] ?? '');

    // Validate collector selection
    if ($collector_id <= 0) {
        $_SESSION['message'] = "Please select a valid collector";
        $_SESSION['message_type'] = "danger";
    } else {
        $conn->begin_transaction();
        
        try {
            // Lock the rows for update
            $conn->query("SELECT * FROM pickup_requests WHERE id = $pickup_id FOR UPDATE");
            $conn->query("SELECT * FROM users WHERE id = $collector_id FOR UPDATE");

            // Check pickup request status
            $check_stmt = $conn->prepare("SELECT status FROM pickup_requests WHERE id = ?");
            $check_stmt->bind_param("i", $pickup_id);
            $check_stmt->execute();
            $current_status = $check_stmt->get_result()->fetch_assoc()['status'];
            $check_stmt->close();

            if ($current_status !== 'Pending') {
                throw new Exception("Pickup request is no longer available for assignment (current status: $current_status)");
            }

            // Get collector details
            $collector = getUserById($collector_id);
            if (!$collector) {
                throw new Exception("Collector not found");
            }

            // 1. Update collector status to 'assigned'
            $update_collector = $conn->prepare("UPDATE users SET status = 'assigned' WHERE id = ?");
            $update_collector->bind_param("i", $collector_id);
            $update_collector->execute();
            $update_collector->close();

            // 2. Create record in pickups table with status 'assigned'
            $insert_stmt = $conn->prepare("
                INSERT INTO pickups 
                (request_id, user_id, collector_id, collector_name, collector_phone, collector_vehicle, 
                 type, pickup_date, pickup_time, address, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'assigned', ?, NOW())
            ");
            $insert_stmt->bind_param(
                "iiissssssss",
                $pickup_id,
                $pickup_request['user_id'],
                $collector_id,
                $collector['name'],
                $collector['phone'],
                $collector['vehicle_number'],
                $pickup_request['type'],
                $pickup_request['pickup_date'],
                $pickup_request['pickup_time'],
                $pickup_request['address'],
                $notes
            );
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to create pickup assignment: " . $conn->error);
            }
            
            $insert_stmt->close();

            // 3. Update pickup_request timestamp
            $update_stmt = $conn->prepare("UPDATE pickup_requests SET updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $pickup_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update pickup request timestamp");
            }
            
            if ($conn->affected_rows === 0) {
                throw new Exception("Pickup request was modified by another process");
            }
            
            $update_stmt->close();

            $conn->commit();

            $_SESSION['message'] = "Successfully assigned pickup request #$pickup_id to collector. Collector status updated to 'assigned'.";
            $_SESSION['message_type'] = "success";
            header("Location: manage_pickups.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Assignment error: " . $e->getMessage());
            $_SESSION['message'] = "Error assigning collector: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
    }
}

$title = "Assign Collector to Pickup Request #$pickup_id";
include_once '../includes/header.php';
?>

<div class="container pb-3">
    <h2 class="my-4">Assign Collector to Pickup Request #<?= $pickup_id ?></h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="mb-4">
                <h5>Pickup Request Details</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Request ID:</strong> <?= $pickup_id ?></p>
                        <p><strong>Citizen:</strong> <?= htmlspecialchars($pickup_request['citizen_name']) ?></p>
                        <p><strong>Contact:</strong> 
                            <?= htmlspecialchars($pickup_request['citizen_email']) ?><br>
                            <?= htmlspecialchars($pickup_request['citizen_phone']) ?>
                        </p>
                        <p><strong>Type:</strong> <?= ucfirst($pickup_request['type']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date:</strong> <?= date('M j, Y', strtotime($pickup_request['pickup_date'])) ?></p>
                        <p><strong>Time Slot:</strong> 
                            <?= date('g:i A', strtotime(explode('-', $pickup_request['pickup_time'])[0])) ?> - 
                            <?= date('g:i A', strtotime(explode('-', $pickup_request['pickup_time'])[1])) ?>
                        </p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($pickup_request['address']) ?></p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-3">
                    <label for="collector_id" class="form-label">Select Collector</label>
                    <select class="form-select" id="collector_id" name="collector_id" required>
                        <option value="">-- Select Collector --</option>
                        <?php foreach ($collectors as $collector): ?>
                            <option value="<?= $collector['id'] ?>" 
                                data-email="<?= htmlspecialchars($collector['email']) ?>"
                                data-phone="<?= htmlspecialchars($collector['phone']) ?>"
                                data-vehicle="<?= htmlspecialchars($collector['vehicle_number']) ?>"
                                data-address="<?= htmlspecialchars($collector['address']) ?>">
                                <?= $collector['id'] ?> - <?= htmlspecialchars($collector['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="collectorDetails" class="mb-4 p-3 bg-light rounded" style="display: none;">
                    <h6>Collector Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <span id="collectorEmail"></span></p>
                            <p><strong>Phone:</strong> <span id="collectorPhone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Vehicle Number:</strong> <span id="collectorVehicle"></span></p>
                            <p><strong>Address:</strong> <span id="collectorAddress"></span></p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Assignment Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? $pickup_request['notes'] ?? '') ?></textarea>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success px-4">Assign Collector</button>
                    <a href="manage_pickups.php" class="btn btn-secondary px-4 ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const collectorSelect = document.getElementById('collector_id');
    const collectorDetails = document.getElementById('collectorDetails');
    
    collectorSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            
            document.getElementById('collectorEmail').textContent = selectedOption.dataset.email;
            document.getElementById('collectorPhone').textContent = selectedOption.dataset.phone;
            document.getElementById('collectorVehicle').textContent = selectedOption.dataset.vehicle;
            document.getElementById('collectorAddress').textContent = selectedOption.dataset.address;
            
            collectorDetails.style.display = 'block';
        } else {
            collectorDetails.style.display = 'none';
        }
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>