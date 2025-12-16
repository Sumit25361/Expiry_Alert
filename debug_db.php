<?php
// Debug Database Tables and Connection
// Upload this file to your public_html folder and visit it in browser

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Diagnostic Tool</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_NAME'] . "</p>";

// Try to include auth to get DB connection
// Adjust paths based on where this file is located
if (file_exists('admin/config/admin_auth.php')) {
    require_once 'admin/config/admin_auth.php';
    echo "<p>Included: admin/config/admin_auth.php</p>";
} elseif (file_exists('config/admin_auth.php')) {
    require_once 'config/admin_auth.php';
    echo "<p>Included: config/admin_auth.php</p>";
} else {
    die("<p style='color:red'>CRITICAL: Could not find admin_auth.php. Make sure this file is in the root folder (EDR/).</p>");
}

try {
    echo "<h2>Connection Attempt</h2>";
    $auth = new AdminAuth();
    $db = $auth->getDb();

    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    echo "<p style='color:green; font-weight:bold'>SUCCESS: Database Connected.</p>";

    // List Tables
    echo "<h2>Table List</h2>";
    echo "<ul>";
    $tables = [];
    $result = $db->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
            $tables[] = $row[0];
        }
    } else {
        echo "<li>No tables found or query failed.</li>";
    }
    echo "</ul>";

    // Check Required Tables for Admin Panel
    echo "<h2>Admin & Logging Tables Check</h2>";

    // Check 'admin_activity_log'
    if (in_array('admin_activity_log', $tables)) {
        echo "<p style='color:green'>[OK] Table 'admin_activity_log' exists.</p>";

        // Show last 5 logs
        echo "<h3>Last 5 Activity Logs:</h3>";
        $logs = $db->query("SELECT * FROM admin_activity_log ORDER BY id DESC LIMIT 5");
        if ($logs && $logs->num_rows > 0) {
            echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Action</th><th>Description</th><th>Time</th></tr>";
            while ($l = $logs->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $l['id'] . "</td>";
                echo "<td>" . (isset($l['action']) ? $l['action'] : 'N/A') . "</td>";
                echo "<td>" . (isset($l['description']) ? $l['description'] : 'N/A') . "</td>";
                echo "<td>" . (isset($l['created_at']) ? $l['created_at'] : 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No logs found in table.</p>";
        }

    } else {
        echo "<p style='color:red; font-weight:bold'>[MISSING] Table 'admin_activity_log' does NOT exist.</p>";
        echo "<p>This is likely why logs.php is failing (500 Error).</p>";
    }

    // Check 'admins' or 'admin_login'
    if (in_array('admins', $tables)) {
        echo "<p style='color:green'>[OK] Table 'admins' exists.</p>";
    } elseif (in_array('admin_login', $tables)) {
        echo "<p style='color:green'>[OK] Table 'admin_login' exists (Alternative name).</p>";
    } else {
        echo "<p style='color:red'>[MISSING] No admin table found ('admins' or 'admin_login').</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
