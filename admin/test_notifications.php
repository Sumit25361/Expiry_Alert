<?php
session_start();
require_once '../services/notification_service.php';

// Simple admin interface to test notifications
if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'admin@expiryalert.com') {
    header("Location: ../login.php");
    exit;
}

$notificationService = new NotificationService();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_reminders'])) {
        $results = $notificationService->checkAndSendExpiryReminders();
        $message = "Test completed! Results: " . json_encode($results, JSON_PRETTY_PRINT);
    }
    
    if (isset($_POST['test_item_added'])) {
        $result = $notificationService->sendItemAddedNotification(
            $_POST['test_email'],
            $_POST['test_item'],
            $_POST['test_category'],
            $_POST['test_expiry']
        );
        $message = $result ? "Item added notification sent successfully!" : "Failed to send notification.";
    }
}

$stats = $notificationService->getNotificationStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Testing - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #4f46e5; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 5px 10px 0; }
        button:hover { background: #3730a3; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d1fae5; border: 1px solid #10b981; color: #065f46; }
        .error { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; }
        .stats-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .stats-table th, .stats-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .stats-table th { background: #f8fafc; }
        pre { background: #f8fafc; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 Notification Testing Panel</h1>
        
        <?php if ($message): ?>
            <div class="message success">
                <strong>Result:</strong><br>
                <pre><?php echo htmlspecialchars($message); ?></pre>
            </div>
        <?php endif; ?>
        
        <h2>Test Expiry Reminders</h2>
        <form method="POST">
            <p>This will check for items expiring and send reminders if it's 3:00 PM IST.</p>
            <button type="submit" name="test_reminders">Test Expiry Reminders</button>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <h2>Test Item Added Notification</h2>
        <form method="POST">
            <div class="form-group">
                <label for="test_email">User Email:</label>
                <input type="email" name="test_email" id="test_email" value="test@example.com" required>
            </div>
            
            <div class="form-group">
                <label for="test_item">Item Name:</label>
                <input type="text" name="test_item" id="test_item" value="Test Medicine" required>
            </div>
            
            <div class="form-group">
                <label for="test_category">Category:</label>
                <select name="test_category" id="test_category" required>
                    <option value="Medicine">Medicine</option>
                    <option value="Food">Food</option>
                    <option value="Documents">Documents</option>
                    <option value="Cosmetic">Cosmetic</option>
                    <option value="Book">Book</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="test_expiry">Expiry Date:</label>
                <input type="datetime-local" name="test_expiry" id="test_expiry" required>
            </div>
            
            <button type="submit" name="test_item_added">Send Test Notification</button>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <h2>📊 Notification Statistics</h2>
        <?php if (!empty($stats)): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reminder Type</th>
                        <th>Users Notified</th>
                        <th>Total Items</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat): ?>
                        <tr>
                            <td><?php echo $stat['date_sent']; ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $stat['reminder_type'])); ?></td>
                            <td><?php echo $stat['total_sent']; ?></td>
                            <td><?php echo $stat['total_items']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No notification statistics available yet.</p>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <h2>📋 Setup Instructions</h2>
        <div style="background: #f8fafc; padding: 20px; border-radius: 5px;">
            <h3>1. Email Configuration</h3>
            <p>Edit <code>config/email.php</code> and update:</p>
            <ul>
                <li>SMTP settings (Gmail, SendGrid, etc.)</li>
                <li>Your email credentials</li>
                <li>Set <code>isTestMode()</code> to <code>false</code> for production</li>
            </ul>
            
            <h3>2. Cron Job Setup</h3>
            <p>Add this cron job to run every hour:</p>
            <pre>0 * * * * /usr/bin/php /path/to/your/project/cron/send_reminders.php</pre>
            
            <h3>3. Testing</h3>
            <p>Currently in test mode - emails are logged to <code>logs/email_log.txt</code></p>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="../index.php" style="color: #4f46e5; text-decoration: none;">← Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        // Set default expiry date to tomorrow
        document.addEventListener('DOMContentLoaded', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(15, 0, 0, 0); // 3 PM
            
            const isoString = tomorrow.toISOString().slice(0, 16);
            document.getElementById('test_expiry').value = isoString;
        });
    </script>
</body>
</html>
