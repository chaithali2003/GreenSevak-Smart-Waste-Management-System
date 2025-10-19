<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid citizen ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_citizens.php");
    exit();
}

$citizen_id = intval($_GET['id']);
$citizens = getAllCitizens();

$found = false;
foreach ($citizens as $citizen) {
    if ($citizen['id'] == $citizen_id) {
        $found = true;
        break;
    }
}

if (!$found) {
    $_SESSION['message'] = "Citizen not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_citizens.php");
    exit();
}

// Delete complaint images
$imgQuery = $conn->prepare("SELECT image FROM complaints WHERE user_id = ?");
$imgQuery->bind_param("i", $citizen_id);
$imgQuery->execute();
$result = $imgQuery->get_result();
while ($row = $result->fetch_assoc()) {
    if (!empty($row['image'])) {
        $imgPath = '../uploads/complaints/' . $row['image'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }
}

// Delete citizen's complaints
mysqli_query($conn, "DELETE FROM complaints WHERE user_id = $citizen_id");

// Delete citizen's pickups (not pickup_requests)
mysqli_query($conn, "DELETE FROM pickups WHERE user_id = $citizen_id");

// Now delete the citizen
if (mysqli_query($conn, "DELETE FROM users WHERE id = $citizen_id")) {
    $_SESSION['message'] = "Citizen deleted successfully.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to delete citizen: " . mysqli_error($conn);
    $_SESSION['message_type'] = "danger";
}

header("Location: manage_citizens.php");
exit();
