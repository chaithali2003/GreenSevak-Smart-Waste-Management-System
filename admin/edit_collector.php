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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch collector data from users table
$collector = [];
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'collector'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $collector = $result->fetch_assoc();
    $stmt->close();
}

if (!$collector) {
    $_SESSION['message'] = "Collector not found";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_collectors.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $vehicle_number = sanitizeInput($_POST['vehicle_number']);
    $address = sanitizeInput($_POST['address']);

    // Basic validation
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone) || !preg_match('/^\d{10}$/', $phone)) $errors[] = "Valid 10-digit phone number is required";
    if (empty($address)) $errors[] = "Address is required";

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'collector' AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email already exists for another collector";
    }
    $stmt->close();

    // Check for duplicate phone
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND role = 'collector' AND id != ?");
    $stmt->bind_param("si", $phone, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Phone number already exists for another collector";
    }
    $stmt->close();

    // Check for duplicate vehicle number (if provided)
    if (!empty($vehicle_number)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE vehicle_number = ? AND role = 'collector' AND id != ?");
        $stmt->bind_param("si", $vehicle_number, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Vehicle number already exists for another collector";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, vehicle_number = ?, address = ? WHERE id = ? AND role = 'collector'");
        $stmt->bind_param("sssssi", $name, $email, $phone, $vehicle_number, $address, $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Collector Details updated successfully";
            $_SESSION['message_type'] = "success";
            header("Location: manage_collectors.php");
            exit();
        } else {
            $_SESSION['message'] = "Error updating collector";
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "danger";
    }
}

$title = "Edit Collector";
include_once '../includes/header.php';
?>

<div class="container pb-3">
    <h2 class="my-4">Edit Details</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo htmlspecialchars($collector['name']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($collector['email']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           pattern="[0-9]{10}" title="Please enter a 10-digit phone number"
                           maxlength="10" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10);" 
                           value="<?php echo htmlspecialchars($collector['phone']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="vehicle_number" class="form-label">Vehicle Number</label>
                    <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" 
                           value="<?php echo htmlspecialchars($collector['vehicle_number'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($collector['address'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Details</button>
                <a href="manage_collectors.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>