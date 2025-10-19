<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();

if (!isCitizen()) {
    header("Location: ../index.php");
    exit();
}

// Validate request method and parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pickup_id'])) {
    $_SESSION['message'] = "Invalid request method or missing pickup ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: pickup_history.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pickup_id = intval($_POST['pickup_id']);

// Validate pickup ID
if ($pickup_id <= 0) {
    $_SESSION['message'] = "Invalid pickup request ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: pickup_history.php");
    exit();
}

// Verify the pickup request exists and belongs to this user
$query = "SELECT * FROM pickup_requests WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $pickup_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Pickup request not found or you don't have permission to delete it.";
    $_SESSION['message_type'] = "danger";
    header("Location: pickup_history.php");
    exit();
}

$pickup = $result->fetch_assoc();

// Ensure it's still in 'Pending' status (case-insensitive comparison)
if (strcasecmp(trim($pickup['status']), 'pending') !== 0) {
    $_SESSION['message'] = "Only 'Pending' pickup requests can be cancelled.";
    $_SESSION['message_type'] = "danger";
    header("Location: pickup_history.php");
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // First delete from pickups table if status is 'assigned'
    $delete_pickup_sql = "DELETE FROM pickups WHERE request_id = ? AND status = 'assigned'";
    $delete_pickup_stmt = $conn->prepare($delete_pickup_sql);
    $delete_pickup_stmt->bind_param("i", $pickup_id);
    $delete_pickup_stmt->execute();
    
    // Then delete from pickup_requests
    $delete_query = "DELETE FROM pickup_requests WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $pickup_id, $user_id);
    $delete_stmt->execute();

    // Commit transaction if both operations succeed
    $conn->commit();

    $_SESSION['message'] = "Pickup request #$pickup_id has been cancelled successfully.";
    $_SESSION['message_type'] = "success";
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error cancelling pickup request #$pickup_id: " . $e->getMessage());
    $_SESSION['message'] = "Error cancelling the pickup request. Please try again.";
    $_SESSION['message_type'] = "danger";
}

header("Location: pickup_history.php");
exit();
?>