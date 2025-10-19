<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone']);

    // Validate inputs
    if ($password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match";
        $_SESSION['message_type'] = "danger";
    } elseif (strlen($password) < 6) {
        $_SESSION['message'] = "Password must be at least 6 characters";
        $_SESSION['message_type'] = "danger";
    } else {
        // Check if email or phone already exists for role = 'citizen'
$stmt = $conn->prepare("SELECT id FROM users WHERE (email = ? OR phone = ?) AND role = 'citizen'");
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['message'] = "Email or phone already registered as a citizen";
    $_SESSION['message_type'] = "danger";
} else {
    // Proceed to insert
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, address, phone) 
                            VALUES (?, ?, ?, 'citizen', ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashed_password, $address, $phone);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Registration successful! Please login.";
        $_SESSION['message_type'] = "success";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
        $_SESSION['message_type'] = "danger";
    }
}
    }
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center pb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Register as Citizen</h4>
            </div>
            <div class="card-body">
                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" required></textarea>
                    </div>
                    <div class="mb-3">
    <label for="phone" class="form-label">Phone number</label>
    <input type="tel" class="form-control" id="phone" name="phone" pattern="\d{10}" maxlength="10" required
           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10);">
</div>
                    <div class="d-grid justify-content-center">
                        <button type="submit" class="btn btn-success" style="width: 150px;">Register</button>
                    </div>
                </form>
                <div class="mt-3 text-center">
                    Already have an account? <a href="login.php" class="text-success">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>