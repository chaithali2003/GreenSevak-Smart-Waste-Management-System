<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Set consistent timezone
date_default_timezone_set('Asia/Kolkata');

if (isLoggedIn()) {
    redirectBasedOnRole();
}

$role = $_GET['role'] ?? 'citizen';

if (!isset($_SESSION['reset_email']) || $_SESSION['reset_role'] !== $role) {
    $_SESSION['message'] = "Invalid request";
    $_SESSION['message_type'] = "danger";
    header("Location: forgot_password.php?role=$role");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = sanitizeInput($_POST['otp']);
    $email = $_SESSION['reset_email'];

    try {
        $stmt = $conn->prepare("SELECT reset_otp, reset_otp_expires FROM users 
                WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $current_time = time();
            $expiry_time = strtotime($user['reset_otp_expires']);

            // Debug output
            error_log("OTP verification attempt at: " . date('Y-m-d H:i:s', $current_time));
            error_log("OTP expires at: " . date('Y-m-d H:i:s', $expiry_time));

            if ($current_time > $expiry_time) {
                $_SESSION['message'] = "OTP has expired. Please request a new one.";
                $_SESSION['message_type'] = "danger";
            } elseif (password_verify($otp, $user['reset_otp'])) {
                $_SESSION['otp_verified'] = true;
                header("Location: reset_password.php?role=$role");
                exit();
            } else {
                $_SESSION['message'] = "Invalid OTP";
                $_SESSION['message_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "User not found";
            $_SESSION['message_type'] = "danger";
        }
    } catch (Exception $e) {
        error_log("OTP verification error: " . $e->getMessage());
        $_SESSION['message'] = "System error. Please try again.";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center pb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Verify OTP - <?= ucfirst($role) ?></h4>
            </div>
            <div class="card-body">
                <form action="verify_otp.php?role=<?= $role ?>" method="POST">
                    <div class="mb-3">
                        <label for="otp" class="form-label">Enter 6-digit OTP</label>
                        <input type="text" class="form-control" id="otp" name="otp" 
                               required maxlength="6" pattern="\d{6}" 
                               inputmode="numeric">
                        <small class="text-muted">Check your email for the OTP</small>
                    </div>
                    <div class="d-grid justify-content-center gap-2">
                        <button type="submit" class="btn btn-success" style="width: 150px;">Verify OTP</button>
                    </div>
                </form>
                <div class="mt-3 text-center">
                    <a href="forgot_password.php?role=<?= $role ?>" class="text-success">Resend OTP</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('otp').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include_once '../includes/footer.php'; ?>