<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();

if (!isCitizen()) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pickup_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the pickup request
$pickup = [];
$sql = "SELECT * FROM pickup_requests WHERE id = $pickup_id AND user_id = $user_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 1) {
    $pickup = mysqli_fetch_assoc($result);
} else {
    $_SESSION['message'] = "Pickup request not found or you don't have permission to edit it.";
    $_SESSION['message_type'] = "danger";
    header("Location: pickup_history.php");
    exit();
}

// Check if pickup is editable (status must be pending)
if (strtolower(trim($pickup['status'])) !== 'pending') {
    $_SESSION['message'] = "You can only edit pickup requests with 'pending' status.";
    $_SESSION['message_type'] = "danger";
    header("Location: pickup_history.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitizeInput($_POST['type']);
    $pickup_date = sanitizeInput($_POST['pickup_date']);
    $pickup_time = sanitizeInput($_POST['pickup_time']);
    $address = sanitizeInput($_POST['address']);
    $notes = sanitizeInput($_POST['notes']);

    // Validate pickup date is today only
    $today = date('Y-m-d');
    if ($pickup_date != $today) {
        $_SESSION['message'] = "You can only schedule pickups for today.";
        $_SESSION['message_type'] = "danger";
        header("Location: edit_pickup.php?id=$pickup_id");
        exit();
    }

    // Define available time slots with their end times (in 24-hour format)
    $time_slots = [
        '09:00 AM - 11:00 AM' => '11:00',
        '11:00 AM - 01:00 PM' => '13:00',
        '02:00 PM - 04:00 PM' => '16:00',
        '04:00 PM - 06:00 PM' => '18:00'
    ];

    // Check if selected time slot is valid
    if (!array_key_exists($pickup_time, $time_slots)) {
        $_SESSION['message'] = "Invalid time slot selected.";
        $_SESSION['message_type'] = "danger";
        header("Location: edit_pickup.php?id=$pickup_id");
        exit();
    }

    // Check if time slot has passed
    $current_time = date('H:i');
    $slot_end_time = $time_slots[$pickup_time];
    
    if ($current_time >= $slot_end_time) {
        $_SESSION['message'] = "The selected time slot has already ended. Please choose a future time slot.";
        $_SESSION['message_type'] = "danger";
        header("Location: edit_pickup.php?id=$pickup_id");
        exit();
    }

    // Update pickup_requests table
    $sql = "UPDATE pickup_requests 
            SET type = '$type', 
                pickup_date = '$pickup_date', 
                pickup_time = '$pickup_time', 
                address = '$address', 
                notes = '$notes',
                updated_at = NOW()
            WHERE id = $pickup_id AND user_id = $user_id";

    if (mysqli_query($conn, $sql)) {
        // Update pickups table if linked record is in 'assigned' status
        $update_pickup_sql = "UPDATE pickups 
                              SET type = ?, pickup_date = ?, pickup_time = ?
                              WHERE request_id = ? AND user_id = ? AND status = 'assigned'";
        $update_pickup_stmt = $conn->prepare($update_pickup_sql);
        $update_pickup_stmt->bind_param("ssssi", $type, $pickup_date, $pickup_time, $pickup_id, $user_id);
        $update_pickup_stmt->execute();

        $_SESSION['message'] = "Pickup request updated successfully for today at $pickup_time!";
        $_SESSION['message_type'] = "success";
        header("Location: pickup_history.php");
        exit();
    } else {
        $_SESSION['message'] = "Error updating pickup request: " . mysqli_error($conn);
        $_SESSION['message_type'] = "danger";
    }
}

$current_time_24h = date('H:i');
$current_date = date('Y-m-d');
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Edit Today's Pickup Request</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?>">
            <?= $_SESSION['message'] ?>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="edit_pickup.php?id=<?= $pickup_id ?>" method="POST" id="pickupForm">
                <div class="mb-3">
                    <label for="type" class="form-label">Waste Type</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">Select waste type</option>
                        <option value="organic" <?= $pickup['type'] === 'organic' ? 'selected' : '' ?>>Organic Waste (Food, Garden Waste)</option>
                        <option value="recyclable" <?= $pickup['type'] === 'recyclable' ? 'selected' : '' ?>>Recyclable (Paper, Cardboard)</option>
                        <option value="plastics" <?= $pickup['type'] === 'plastics' ? 'selected' : '' ?>>Plastics (Bottles, Containers)</option>
                        <option value="glass" <?= $pickup['type'] === 'glass' ? 'selected' : '' ?>>Glass (Bottles, Jars)</option>
                        <option value="metal" <?= $pickup['type'] === 'metal' ? 'selected' : '' ?>>Metal (Cans, Foil, Scrap)</option>
                        <option value="e-waste" <?= $pickup['type'] === 'e-waste' ? 'selected' : '' ?>>E-Waste (Electronics, Batteries)</option>
                        <option value="hazardous" <?= $pickup['type'] === 'hazardous' ? 'selected' : '' ?>>Hazardous (Chemicals, Paint, Oil)</option>
                        <option value="medical" <?= $pickup['type'] === 'medical' ? 'selected' : '' ?>>Medical Waste (Needles, Medications)</option>
                        <option value="bulk" <?= $pickup['type'] === 'bulk' ? 'selected' : '' ?>>Bulk Items (Furniture, Mattresses)</option>
                        <option value="appliances" <?= $pickup['type'] === 'appliances' ? 'selected' : '' ?>>Appliances (Large & Small)</option>
                        <option value="construction" <?= $pickup['type'] === 'construction' ? 'selected' : '' ?>>Construction Debris (Wood, Concrete)</option>
                        <option value="textiles" <?= $pickup['type'] === 'textiles' ? 'selected' : '' ?>>Textiles (Clothing, Fabrics)</option>
                        <option value="mixed" <?= $pickup['type'] === 'mixed' ? 'selected' : '' ?>>Mixed Waste (Non-Recyclable)</option>
                        <option value="other" <?= $pickup['type'] === 'other' ? 'selected' : '' ?>>Other (Specify in Additional Notes)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Pickup Address</label>
                    <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($pickup['address']) ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pickup_date" class="form-label">Pickup Date</label>
                        <input type="text" class="form-control" value="<?= date('l, F j, Y') ?>" readonly>
                        <input type="hidden" name="pickup_date" value="<?= $current_date ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="pickup_time" class="form-label">Pickup Time</label>
                        <select class="form-select" id="pickup_time" name="pickup_time" required>
                            <option value="">Select time slot</option>
                            <?php
                            $slots = [
                                '09:00 AM - 11:00 AM' => '11:00',
                                '11:00 AM - 01:00 PM' => '13:00',
                                '02:00 PM - 04:00 PM' => '16:00',
                                '04:00 PM - 06:00 PM' => '18:00'
                            ];
                            
                            foreach ($slots as $slot => $end_time) {
                                $disabled = ($current_time_24h >= $end_time) ? 'disabled' : '';
                                $selected = ($pickup['pickup_time'] === $slot) ? 'selected' : '';
                                echo "<option value='$slot' $selected $disabled>$slot</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Additional Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($pickup['notes']) ?></textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary me-md-2">Update Pickup</button>
                    <a href="pickup_history.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pickupTime = document.getElementById('pickup_time');
    const now = new Date();
    const currentHours = now.getHours();
    const currentMinutes = now.getMinutes();
    
    // Function to convert time string to minutes (e.g. "13:00" → 780)
    function timeToMinutes(timeStr) {
        const [hours, minutes] = timeStr.split(':').map(Number);
        return hours * 60 + minutes;
    }
    
    // Function to update time slots based on current time
    function updateTimeSlots() {
        // Define all time slots with their end times
        const timeSlots = [
            { display: '09:00 AM - 11:00 AM', endTime: '11:00' },
            { display: '11:00 AM - 01:00 PM', endTime: '13:00' },
            { display: '02:00 PM - 04:00 PM', endTime: '16:00' },
            { display: '04:00 PM - 06:00 PM', endTime: '18:00' }
        ];
        
        // Clear existing options (keep first empty option)
        while (pickupTime.options.length > 1) {
            pickupTime.remove(1);
        }
        
        // Add each time slot option
        timeSlots.forEach(slot => {
            const option = new Option(slot.display, slot.display);
            const slotEndMinutes = timeToMinutes(slot.endTime);
            const currentTotalMinutes = currentHours * 60 + currentMinutes;
            option.disabled = (currentTotalMinutes >= slotEndMinutes);
            
            // Check if this was the previously selected slot
            if (slot.display === '<?= $pickup['pickup_time'] ?>') {
                option.selected = true;
                if (option.disabled) {
                    alert('Your previously selected time slot is no longer available. Please choose a different time.');
                }
            }
            
            pickupTime.add(option);
        });
    }
    
    // Initial update when page loads
    updateTimeSlots();
});
</script>

<?php include_once '../includes/footer.php'; ?>