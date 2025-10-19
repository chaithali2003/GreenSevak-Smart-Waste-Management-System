<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get collector ID from either GET or POST
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if ($id <= 0) {
    $_SESSION['message'] = "Invalid collector ID";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_collectors.php");
    exit();
}

// Handle the deletion
try {
    // Check if collector exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'collector'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        $_SESSION['message'] = "Collector not found";
        $_SESSION['message_type'] = "danger";
        header("Location: manage_collectors.php");
        exit();
    }
    
    // Check if collector has any assigned pickups
    $pickupCheck = $conn->prepare("SELECT id FROM pickups WHERE collector_id = ?");
    $pickupCheck->bind_param("i", $id);
    $pickupCheck->execute();
    $pickupCheck->store_result();
    
    if ($pickupCheck->num_rows > 0) {
        $_SESSION['message'] = "Cannot delete collector with assigned pickups. Reassign pickups first.";
        $_SESSION['message_type'] = "danger";
        header("Location: manage_collectors.php");
        exit();
    }
    
    // Delete the collector
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'collector'");
    $deleteStmt->bind_param("i", $id);
    
    if ($deleteStmt->execute()) {
        $_SESSION['message'] = "Collector deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting collector";
        $_SESSION['message_type'] = "danger";
    }
    
    $deleteStmt->close();
    $pickupCheck->close();
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['message'] = "Database error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

header("Location: manage_collectors.php");
exit();
?>