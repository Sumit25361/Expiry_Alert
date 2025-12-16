<?php
require_once 'config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Database Schema Update</h2>";

function addColumnIfNotExists($conn, $table, $column, $definition)
{
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql)) {
            echo "<div style='color: green'>✅ Added column `$column` to table `$table`</div>";
        } else {
            echo "<div style='color: red'>❌ Failed to add `$column`: " . $conn->error . "</div>";
        }
    } else {
        echo "<div style='color: orange'>⚠️ Column `$column` already exists in `$table`</div>";
    }
}

// Add columns needed for password reset
addColumnIfNotExists($conn, 'users', 'reset_token_hash', 'VARCHAR(64) NULL AFTER password');
addColumnIfNotExists($conn, 'users', 'reset_token_expires_at', 'DATETIME NULL AFTER reset_token_hash');

echo "<br><hr>";
echo "<h3>Update Finished.</h3>";
echo "<p>You should now be able to use the Forgot Password feature.</p>";
echo "<p><a href='forgot_password.php'>Go to Forgot Password</a></p>";
?>