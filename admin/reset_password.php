<?php
// Script to reset admin password if needed
require_once '../config/database.php';

// Only run this script if you need to reset the admin password
$reset_password = false; // Change to true to enable password reset

if ($reset_password) {
    $database = new Database();
    $db = $database->getConnection();
    
    // New password (change this to your desired password)
    $new_password = 'newpassword123';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update admin password
    $stmt = $db->prepare("UPDATE admins SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE username = 'admin'");
    $stmt->bind_param("s", $hashed_password);
    
    if ($stmt->execute()) {
        echo "✅ Admin password updated successfully!<br>";
        echo "New password: " . $new_password . "<br>";
        echo "Username: admin<br>";
        echo "Email: admin@expiryalert.com<br>";
    } else {
        echo "❌ Error updating password: " . $stmt->error;
    }
} else {
    echo "Password reset is disabled. Set \$reset_password = true to enable.";
}
?>
