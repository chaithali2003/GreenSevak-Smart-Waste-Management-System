<?php
session_start();

// Database connection
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCitizen() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'citizen';
}

function isCollector() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'collector';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function redirectBasedOnRole() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: ../admin/dashboard.php");
        } elseif (isCollector()) {
            header("Location: ../collector/dashboard.php");
        } else {
            header("Location: ../citizen/dashboard.php");
        }
        exit();
    }
}

function checkAuthorization($allowed_roles) {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }

    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    if (!in_array($_SESSION['role'], $allowed_roles)) {
        redirectBasedOnRole();
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>