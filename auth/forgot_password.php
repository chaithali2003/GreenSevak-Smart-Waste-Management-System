<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../config/mailer.php';

// Set consistent timezone
date_default_timezone_set('Asia/Kolkata');

if (isLoggedIn()) {
    redirectBasedOnRole();
}

$role = $_GET['role'] ?? 'citizen';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format";
        $_SESSION['message_type'] = "danger";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND role = ?");
            $stmt->bind_param("ss", $email, $role);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
                $expires = date("Y-m-d H:i:s", time() + 600); // 10 minutes from now

                // Debug output
                error_log("Generating OTP for $email at " . date('Y-m-d H:i:s'));
                error_log("OTP will expire at: $expires");

                $update_stmt = $conn->prepare("UPDATE users SET 
                        reset_otp = ?,
                        reset_otp_expires = ?
                        WHERE email = ? AND role = ?");
                $update_stmt->bind_param("ssss", $otp_hash, $expires, $email, $role);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update OTP in database");
                }

                $subject = "Password Reset OTP";
                $message = "Hello {$user['name']},<br><br>";
                $message .= "You have requested to reset your password for your $role account. Use the following OTP to reset your password:<br><br>";
                $message .= "<strong>OTP: $otp</strong><br><br>";
                $message .= "This OTP will expire in 10 minutes.<br><br>";
                $message .= "If you didn't request this, please ignore this email or contact support if you have concerns.<br><br>";
                $message .= "Regards,<br>";
                $message .= "<strong>GreenSevak Team</strong>";

                if (sendEmail($email, $subject, $message)) {
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_role'] = $role;
                    $_SESSION['message'] = "OTP sent to your email";
                    $_SESSION['message_type'] = "success";
                    header("Location: verify_otp.php?role=$role");
                    exit();
                } else {
                    $_SESSION['message'] = "Failed to send OTP email";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "If your email exists, you'll receive an OTP";
                $_SESSION['message_type'] = "success";
            }
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            $_SESSION['message'] = "System error. Please try again.";
            $_SESSION['message_type'] = "danger";
        }
    }
}
?>

<!-- HTML remains the same -->
<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center pb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Forgot Password - <?= ucfirst($role) ?></h4>
            </div>
            <div class="card-body">
                <form action="forgot_password.php?role=<?= $role ?>" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="d-grid justify-content-center mt-3">
                        <button type="submit" class="btn btn-success">Send OTP</button>
                    </div>
                </form>
                <div class="mt-3 text-center">
                    <a href="login.php?role=<?= $role ?>" class="text-success">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>