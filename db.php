<?php
// db.php - shared database connection file
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "EDR";
$port = 3306;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
