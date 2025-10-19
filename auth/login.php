<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

$role = $_GET['role'] ?? 'citizen'; // Default role is citizen

if (isLoggedIn()) {
    redirectBasedOnRole();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);

    $sql = "SELECT * FROM users WHERE email = '$email' AND role = '$role'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            redirectBasedOnRole();
        } else {
            $_SESSION['message'] = "Invalid password";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "User not found for $role";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center pb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0 text-capitalize"><?= $role ?> Login</h4>
            </div>
            <div class="card-body">
                <!-- Role-based links -->
                <div class="text-center mb-3">
                    <a href="login.php?role=citizen" class="btn btn-outline-success btn-sm <?= $role == 'citizen' ? 'active' : '' ?>">Citizen</a>
                    <a href="login.php?role=collector" class="btn btn-outline-success btn-sm <?= $role == 'collector' ? 'active' : '' ?>">Collector</a>
                    <a href="login.php?role=admin" class="btn btn-outline-success btn-sm <?= $role == 'admin' ? 'active' : '' ?>">Admin</a>
                </div>

                <form action="login.php?role=<?= $role ?>" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mt-2">
                        <a href="forgot_password.php?role=<?= $role ?>" class="text-success">Forgot Password?</a>
                    </div>
                    <div class="d-grid justify-content-center mt-3">
                        <button type="submit" class="btn btn-success" style="width: 120px;">Login</button>
                    </div>
                </form>

                <?php if ($role === 'citizen'): ?>
                <div class="mt-3 text-center">
                    Don't have an account? <a href="register.php" class="text-success">Register</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
