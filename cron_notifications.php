<?php
// cron_notifications.php
// Run this script via a Cron Job or Windows Task Scheduler once daily (e.g., at 9 AM)

// Set time limit to avoid timeout for large batches
set_time_limit(300);

require_once __DIR__ . '/services/notification_service.php';

echo "Starting Expiry Notification Check...<br>\n";
echo "Time: " . date('Y-m-d H:i:s') . "<br>\n";

try {
    $notificationService = new NotificationService();
    $results = $notificationService->checkAndSendExpiryReminders();

    echo "<pre>";
    print_r($results);
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    error_log("Notification Cron Error: " . $e->getMessage());
}

echo "<br>\nDone.";
?>