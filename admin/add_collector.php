<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $vehicle_number = sanitizeInput($_POST['vehicle_number']);
    $address = sanitizeInput($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($vehicle_number) || empty($address) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check for duplicate email, phone, or vehicle number ONLY among collectors
        $checkSql = "SELECT id FROM users WHERE role = 'collector' AND (email = ? OR phone = ? OR vehicle_number = ?)";
        $stmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($stmt, "sss", $email, $phone, $vehicle_number);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            // Determine which field is duplicate
            $duplicates = [];
            $emailCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'collector' AND email = ?");
            mysqli_stmt_bind_param($emailCheck, "s", $email);
            mysqli_stmt_execute($emailCheck);
            mysqli_stmt_store_result($emailCheck);
            if (mysqli_stmt_num_rows($emailCheck) > 0) $duplicates[] = "email";
            mysqli_stmt_close($emailCheck);

            $phoneCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'collector' AND phone = ?");
            mysqli_stmt_bind_param($phoneCheck, "s", $phone);
            mysqli_stmt_execute($phoneCheck);
            mysqli_stmt_store_result($phoneCheck);
            if (mysqli_stmt_num_rows($phoneCheck) > 0) $duplicates[] = "phone";
            mysqli_stmt_close($phoneCheck);

            $vehicleCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE role = 'collector' AND vehicle_number = ?");
            mysqli_stmt_bind_param($vehicleCheck, "s", $vehicle_number);
            mysqli_stmt_execute($vehicleCheck);
            mysqli_stmt_store_result($vehicleCheck);
            if (mysqli_stmt_num_rows($vehicleCheck) > 0) $duplicates[] = "vehicle number";
            mysqli_stmt_close($vehicleCheck);

            $error = "The following already exist for another collector: " . implode(", ", $duplicates);
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertSql = "INSERT INTO users (name, email, phone, address, password, role, vehicle_number)
                          VALUES (?, ?, ?, ?, ?, 'collector', ?)";
            $stmt = mysqli_prepare($conn, $insertSql);
            mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $phone, $address, $hashedPassword, $vehicle_number);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Collector added successfully!";
            } else {
                $error = "Error adding collector: " . mysqli_error($conn);
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4 text-center">Add New Collector</h2>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card col-md-6 col-lg-5 mx-auto">
        <div class="card-body">
            <form method="POST" action="add_collector.php">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" maxlength="10" pattern="\d{10}" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10);" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="vehicle_number" class="form-label">Vehicle Number</label>
                    <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" 
                           value="<?php echo htmlspecialchars($_POST['vehicle_number'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Create Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success px-4">Add Collector</button>
                    <a href="manage_collectors.php" class="btn btn-secondary px-4 ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>