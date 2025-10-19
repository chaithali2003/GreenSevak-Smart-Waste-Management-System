<?php
require_once '../config/database.php';

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function getUserById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getPickupById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM pickup_requests WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getAllCollectors() {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE role = 'collector'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getAllCitizens() {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE role = 'citizen'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getAssignedPickups($collector_id) {
    global $conn;
    $query = "SELECT p.*, pr.type, pr.pickup_date, pr.pickup_time, pr.notes, pr.user_id 
              FROM pickups p
              JOIN pickup_requests pr ON p.request_id = pr.id
              WHERE p.collector_id = ? AND p.status != 'completed' AND p.status != 'cancelled'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $collector_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUserPickups($user_id) {
    global $conn;
    
    $query = "
        SELECT 
            pr.*, 
            p.collector_id
            /* Removed COALESCE for status - we'll only use pr.status */
        FROM 
            pickup_requests pr
        LEFT JOIN 
            pickups p ON pr.id = p.request_id  /* Use your correct join column */
        WHERE 
            pr.user_id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("SQL Error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getUserComplaints($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM complaints WHERE user_id = ? ORDER BY date DESC");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getAllComplaints() {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM complaints ORDER BY date DESC");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getPickupsByStatus($status) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, pr.id as request_id, u.name as citizen_name
        FROM pickups p
        LEFT JOIN pickup_requests pr ON p.user_id = pr.user_id 
            AND p.type = pr.type
            AND p.pickup_date = pr.pickup_date
            AND p.pickup_time = pr.pickup_time
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.status = ?
    ");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getPickupRequestsByStatus($status) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT pr.*, u.name AS citizen_name 
        FROM pickup_requests pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.status = ?
    ");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function countPendingPickupRequests() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM pickup_requests WHERE status = 'Pending'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

function countCompletedPickups() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM pickups WHERE status = 'completed'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

function getPickupRequestStatus($request_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT status FROM pickup_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['status'];
    }
    return null;
}

function getPickupByRequestId($request_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM pickups WHERE request_id = ? LIMIT 1");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc(); // returns null if not found
}

function getCollectorDetails($collector_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            u.name, 
            u.email, 
            u.phone, 
            p.vehicle_number
        FROM 
            users u
        JOIN 
            pickups p ON u.id = p.collector_id
        WHERE 
            u.id = ? AND
            u.role = 'collector'
        LIMIT 1
    ");
    
    if (!$stmt) {
        error_log("Database error: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $collector_id);
    
    if (!$stmt->execute()) {
        error_log("Execution error: " . $stmt->error);
        return null;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

function getPickupDetails($pickup_id, $user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM pickup_requests 
                           WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pickup_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}