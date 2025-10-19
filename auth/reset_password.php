<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

$role = $_GET['role'] ?? 'citizen';

if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified'] || !isset($_SESSION['reset_email'])) {
    $_SESSION['message'] = "OTP verification required";
    $_SESSION['message_type'] = "danger";
    header("Location: forgot_password.php?role=$role");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);

    $errors = [];
    if ($password !== $confirm_password) {
        $errors[] = "Passwords don't match";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain an uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain a lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain a number";
    }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET 
                    password = ?,
                    reset_otp = NULL,
                    reset_otp_expires = NULL
                    WHERE email = ? AND role = ?");
            $stmt->bind_param("sss", $hashed_password, $email, $role);
            
            if ($stmt->execute()) {
                // Clear session data
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_role']);
                unset($_SESSION['otp_verified']);
                
                $_SESSION['message'] = "Password updated successfully";
                $_SESSION['message_type'] = "success";
                header("Location: login.php?role=$role");
                exit();
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $errors[] = "System error. Please try again.";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "danger";
    }
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center pb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Reset Password - <?= ucfirst($role) ?></h4>
            </div>
            <div class="card-body">
                <form action="reset_password.php?role=<?= $role ?>" method="POST" id="resetForm">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
<div class="form-text small">
                                Password Requirements:
                                <ul class="mb-0">
                                    <li>Minimum 6 characters</li>
                                    <li>At least 1 uppercase letter</li>
                                    <li>At least 1 lowercase letter</li>
                                    <li>At least 1 number</li>
                                </ul>
                            </div>                    
                        </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid justify-content-center gap-2">
                        <button type="submit" class="btn btn-success" style="width: 150px;">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('resetForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        alert('Passwords do not match!');
        e.preventDefault();
        return false;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters!');
        e.preventDefault();
        return false;
    }
    
    if (!/[A-Z]/.test(password)) {
        alert('Password must contain an uppercase letter!');
        e.preventDefault();
        return false;
    }
    
    if (!/[a-z]/.test(password)) {
        alert('Password must contain a lowercase letter!');
        e.preventDefault();
        return false;
    }
    
    if (!/[0-9]/.test(password)) {
        alert('Password must contain a number!');
        e.preventDefault();
        return false;
    }
    
    return true;
});
</script>

<?php include_once '../includes/footer.php'; ?>