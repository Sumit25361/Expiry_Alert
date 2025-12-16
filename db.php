<?php
// db.php - shared database connection file
// Auto-detect environment
if ($_SERVER['SERVER_NAME'] == 'sql107.infinityfree.com' || strpos($_SERVER['HTTP_HOST'] ?? '', 'infinityfree') !== false) {
    // Production Credentials (InfinityFree)
    $host = "sql107.infinityfree.com";
    $user = "if0_40689940";
    $password = "Sumitkb123";
    $database = "if0_40689940_edr";
} else {
    // Local Development
    $host = "127.0.0.1";
    $user = "root";
    $password = "";
    $database = "EDR";
}
$port = 3306;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>