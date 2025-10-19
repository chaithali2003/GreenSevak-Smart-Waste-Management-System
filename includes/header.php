<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenSevak - Servant of the Earth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" sizes="128x128" href="logo.png">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
        <div class="container">
            <?php if (!isLoggedIn()): ?>
    <a class="navbar-brand d-flex align-items-center" href="/GreenSevak/index.php">
<?php else: ?>
    <div class="navbar-brand d-flex align-items-center" style="cursor: default;" title="Logout to return to Home">
<?php endif; ?>
        <img src="/GreenSevak/logo.png" alt="GreenSevak" style="width: 45px; height: 45px; margin-right: -8px; object-fit: contain;">
        <span class="navbar-brand-text" style="margin-left: 0.5rem; font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.25rem; color: white;">
            GreenSevak
        </span>
<?php if (!isLoggedIn()): ?>
    </a>
<?php else: ?>
    </div>
<?php endif; ?>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
    <?php if (isLoggedIn()): ?>
        <?php
            // Get current filename only (e.g., dashboard.php)
            $currentPage = basename($_SERVER['PHP_SELF']);
        ?>

        <?php if (isAdmin()): ?>
            <?php
                $adminLinks = [
                    'dashboard.php' => '../admin/dashboard.php',
                    'manage_citizens.php' => '../admin/manage_citizens.php',
                    'manage_collectors.php' => '../admin/manage_collectors.php',
                    'manage_pickups.php' => '../admin/manage_pickups.php',
                    'manage_complaints.php' => '../admin/manage_complaints.php',
                    'view_feedbacks.php' => '../admin/view_feedbacks.php',
                ];
                foreach ($adminLinks as $page => $url): 
            ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === $page ? 'active disabled' : ''; ?>"
                       href="<?php echo $currentPage === $page ? '#' : $url; ?>"
                       aria-disabled="<?php echo $currentPage === $page ? 'true' : 'false'; ?>"
                       tabindex="<?php echo $currentPage === $page ? '-1' : '0'; ?>">
                       <?php echo ucwords(str_replace(['_', '.php'], [' ', ''], $page)); ?>
                    </a>
                </li>
            <?php endforeach; ?>

        <?php elseif (isCitizen()): ?>
            <?php
                $citizenLinks = [
                    'dashboard.php' => '../citizen/dashboard.php',
                    'schedule_pickup.php' => '../citizen/schedule_pickup.php',
                    'pickup_history.php' => '../citizen/pickup_history.php',
                    'submit_complaint.php' => '../citizen/submit_complaint.php',
                    'view_complaints.php' => '../citizen/view_complaints.php',
                ];
                foreach ($citizenLinks as $page => $url): 
            ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === $page ? 'active disabled' : ''; ?>"
                       href="<?php echo $currentPage === $page ? '#' : $url; ?>"
                       aria-disabled="<?php echo $currentPage === $page ? 'true' : 'false'; ?>"
                       tabindex="<?php echo $currentPage === $page ? '-1' : '0'; ?>">
                       <?php echo ucwords(str_replace(['_', '.php'], [' ', ''], $page)); ?>
                    </a>
                </li>
            <?php endforeach; ?>

        <?php elseif (isCollector()): ?>
            <?php
                $collectorLinks = [
                    'dashboard.php' => '../collector/dashboard.php',
                    'assigned_pickups.php' => '../collector/assigned_pickups.php',
                    'pickup_history.php' => '../collector/pickup_history.php',
                ];
                foreach ($collectorLinks as $page => $url): 
            ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === $page ? 'active disabled' : ''; ?>"
                       href="<?php echo $currentPage === $page ? '#' : $url; ?>"
                       aria-disabled="<?php echo $currentPage === $page ? 'true' : 'false'; ?>"
                       tabindex="<?php echo $currentPage === $page ? '-1' : '0'; ?>">
                       <?php echo ucwords(str_replace(['_', '.php'], [' ', ''], $page)); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</ul>

                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo strtoupper($_SESSION['name']); ?></span>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> Profile
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <?php if (isCitizen() || isCollector()): ?>
                                    <li><a class="dropdown-item" href="<?php echo isCitizen() ? '../citizen/update_profile.php' : '../collector/update_profile.php'; ?>">
                                        <i class="fas fa-user-edit me-2"></i>Update Profile
                                    </a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="/GreenSevak/auth/change_password.php">
    <i class="fas fa-key me-2"></i>Change Password
</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../auth/logout.php" onclick="return confirm('Are you sure you want to log out?')">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome to GreenSevak</span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="padding-top: 100px;">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>
    </div>