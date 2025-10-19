<?php
ob_start();

require_once __DIR__ . '/../includes/auth.php';
checkAuthorization(['collector']);

$title = "Update Profile";
include __DIR__ . '/../includes/header.php';

// Fetch current user data from the users table
$userId = $_SESSION['user_id'];
$query = "SELECT name, email, phone, address, vehicle_number FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    // Sanitize and validate input
    $name = trim($conn->real_escape_string($_POST['name']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $phone = trim($conn->real_escape_string($_POST['phone']));
    $address = trim($conn->real_escape_string($_POST['address']));
    $vehicle_number = trim($conn->real_escape_string($_POST['vehicle_number']));

    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = "Phone must be exactly 10 digits";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($vehicle_number)) $errors[] = "Vehicle number is required";

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // Update users table including vehicle number
            $updateUserQuery = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, vehicle_number = ? WHERE id = ?";
            $updateUserStmt = $conn->prepare($updateUserQuery);
            if ($updateUserStmt === false) throw new Exception("Prepare failed: " . $conn->error);
            $updateUserStmt->bind_param("sssssi", $name, $email, $phone, $address, $vehicle_number, $userId);
            if (!$updateUserStmt->execute()) throw new Exception("Error updating user profile: " . $conn->error);

            $conn->commit();
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
            $_SESSION['name'] = $name;
            ob_end_clean();
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "danger";
    }
}

ob_end_flush();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Update Profile</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>

                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   maxlength="10" pattern="[0-9]{10}" title="Enter 10-digit phone number" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" id="vehicle_number" name="vehicle_number"
                                   value="<?php echo htmlspecialchars($user['vehicle_number'] ?? ''); ?>" required>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
