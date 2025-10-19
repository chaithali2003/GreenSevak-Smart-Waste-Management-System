<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isCollector()) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['message'] = "Invalid request. No pickup ID provided.";
    $_SESSION['message_type'] = "danger";
    header("Location: assigned_pickups.php");
    exit();
}

$pickup_id = intval($_GET['id']);
$collector_id = $_SESSION['user_id'];

// Begin transaction
$conn->begin_transaction();

try {
    // 1. Verify the pickup is assigned to this collector and is in 'assigned' status
    $pickup_query = "SELECT * FROM pickups WHERE id = ? AND collector_id = ? AND status = 'assigned' FOR UPDATE";
    $stmt = $conn->prepare($pickup_query);
    $stmt->bind_param("ii", $pickup_id, $collector_id);
    $stmt->execute();
    $pickup_result = $stmt->get_result();

    if ($pickup_result->num_rows === 0) {
        throw new Exception("Pickup not found or not in assignable state.");
    }

    $pickup = $pickup_result->fetch_assoc();
    $request_id = $pickup['request_id'];

    // 2. Update pickup status to 'in_progress' with timestamp
    $update_pickup = "UPDATE pickups SET status = 'in_progress', accepted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_pickup);
    $stmt->bind_param("i", $pickup_id);
    $stmt->execute();

    // 3. Update collector's status in users table to 'accepted'
    $update_collector = "UPDATE users SET status = 'accepted' WHERE id = ? AND role = 'collector'";
    $stmt = $conn->prepare($update_collector);
    $stmt->bind_param("i", $collector_id);
    $stmt->execute();

    // 4. Update pickup_request status to 'In Progress'
    $update_request = "UPDATE pickup_requests SET status = 'In Progress' WHERE id = ?";
    $stmt = $conn->prepare($update_request);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();

    // Commit all changes
    $conn->commit();

    $_SESSION['message'] = "Pickup #$pickup_id accepted successfully! Status updated to In Progress.";
    $_SESSION['message_type'] = "success";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['message'] = "Error accepting pickup: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

header("Location: assigned_pickups.php");
exit();
?>