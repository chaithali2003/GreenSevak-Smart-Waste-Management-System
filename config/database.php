<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'greensevak');

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// After creating connection
$conn->query("SET time_zone = '+05:30'"); // For India timezone
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>