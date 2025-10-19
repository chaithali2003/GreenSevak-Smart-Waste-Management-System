<?php
ob_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    ob_end_clean();
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        ob_end_clean();
        die("Security error: Invalid request");
    }

    $errors = [];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    if (empty($current_password)) $errors[] = "Current password is required";
    if (empty($new_password)) $errors[] = "New password is required";
    if ($new_password === $current_password) $errors[] = "New password cannot be same as current password";
    if ($new_password !== $_POST['confirm_password']) $errors[] = "Passwords don't match";
    if (strlen($new_password) < 6) $errors[] = "Password must be at least 6 characters";
    if (!preg_match('/[A-Z]/', $new_password)) $errors[] = "Must contain uppercase letter";
    if (!preg_match('/[a-z]/', $new_password)) $errors[] = "Must contain lowercase letter";
    if (!preg_match('/[0-9]/', $new_password)) $errors[] = "Must contain number";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                
                if ($update_stmt->execute()) {
                    // Show JavaScript alert before redirect
                    echo '<script>
                            alert("Password changed successfully! Please login again.");
                            window.location.href = "login.php";
                          </script>';
                    // Clear session and exit
                    session_unset();
                    session_destroy();
                    exit();
                }
            } else {
                $errors[] = "Current password is incorrect";
            }
        } else {
            $errors[] = "User not found";
        }
    }

    if (!empty($errors)) {
        $_SESSION['password_errors'] = $errors;
        ob_end_clean();
        header("Location: change_password.php");
        exit();
    }
}

$title = "Change Password";
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Rest of your HTML form remains exactly the same -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <?php if (isset($_SESSION['password_errors'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php foreach ($_SESSION['password_errors'] as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['password_errors']); ?>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h4>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text small">
                                Password Requirements:
                                <ul class="mb-0">
                                    <li>Minimum 6 characters</li>
                                    <li>At least 1 uppercase letter</li>
                                    <li>At least 1 lowercase letter</li>
                                    <li>At least 1 number</li>
                                    <li>Cannot be same as current password</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fas fa-save me-2"></i>Change Password
                            </button>
                            <a href="<?= isAdmin() ? '../admin/dashboard.php' : 
                                      (isCollector() ? '../collector/dashboard.php' : '../citizen/dashboard.php') ?>" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/../includes/footer.php';
ob_end_flush();
?>