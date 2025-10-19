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
        header("Location: schedule_pickup.php");
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
        header("Location: schedule_pickup.php");
        exit();
    }

    // Check if time slot has passed
    $current_time = date('H:i');
    $slot_end_time = $time_slots[$pickup_time];
    
    if ($current_time >= $slot_end_time) {
        $_SESSION['message'] = "The selected time slot has already ended. Please choose a future time slot.";
        $_SESSION['message_type'] = "danger";
        header("Location: schedule_pickup.php");
        exit();
    }

    // Proceed with scheduling if validation passes
    $sql = "INSERT INTO pickup_requests (user_id, address, type, pickup_date, pickup_time, notes, created_at) 
            VALUES ('$user_id', '$address', '$type', '$pickup_date', '$pickup_time', '$notes', NOW())";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = "Pickup scheduled successfully for today at $pickup_time!";
        $_SESSION['message_type'] = "success";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['message'] = "Error scheduling pickup: " . mysqli_error($conn);
        $_SESSION['message_type'] = "danger";
    }
}

// Get current time for client-side disabling of past slots
$current_time_24h = date('H:i');
$current_date = date('Y-m-d');
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Schedule Waste Pickup</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?>">
            <?= $_SESSION['message'] ?>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form action="schedule_pickup.php" method="POST" id="pickupForm">
                <div class="mb-3">
                    <label for="type" class="form-label">Waste Type</label>
                    <select class="form-select" id="type" name="type" required>
    <option value="">Select waste type</option>
    
    <!-- Common Household Waste -->
    <option value="organic">Organic Waste (Food, Garden Waste)</option>
    <option value="recyclable">Recyclable (Paper, Cardboard)</option>
    <option value="plastics">Plastics (Bottles, Containers)</option>
    <option value="glass">Glass (Bottles, Jars)</option>
    <option value="metal">Metal (Cans, Foil, Scrap)</option>
    
    <!-- Special Waste -->
    <option value="e-waste">E-Waste (Electronics, Batteries)</option>
    <option value="hazardous">Hazardous (Chemicals, Paint, Oil)</option>
    <option value="medical">Medical Waste (Needles, Medications)</option>
    
    <!-- Bulk & Construction Waste -->
    <option value="bulk">Bulk Items (Furniture, Mattresses)</option>
    <option value="appliances">Appliances (Large & Small)</option>
    <option value="construction">Construction Debris (Wood, Concrete)</option>
    
    <!-- Other Categories -->
    <option value="textiles">Textiles (Clothing, Fabrics)</option>
    <option value="mixed">Mixed Waste (Non-Recyclable)</option>
    <option value="other">Other (Specify in Additional Notes)</option>
</select>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Pickup Address</label>
                    <input type="text" class="form-control" id="address" name="address" required>
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
                                echo "<option value='$slot' $disabled>$slot</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Additional Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success" style="width: 150px">Schedule Pickup</button>
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
            pickupTime.add(option);
        });
    }
    
    // Initial update when page loads
    updateTimeSlots();
});
</script>

<?php include_once '../includes/footer.php'; ?>