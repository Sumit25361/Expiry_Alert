<?php
require_once 'config/admin_auth.php';
// Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'email_sender.php'; // Include our simple email sender

// Initialize auth
$auth = new AdminAuth();
$auth->requireLogin();

// Get database connection
$db = $auth->getDb();

$success_message = '';
$error_message = '';

// Function to send email using our simple sender
function sendEmail($to, $subject, $message)
{
    global $emailSender;
    return $emailSender->sendEmail($to, $subject, $message);
}

// Function to get user's expiring items from EDR database
function getUserExpiringItems($db, $email, $daysAhead)
{
    $tables = [
        'documents' => 'document_name',
        'medicines' => 'medicine_name',
        'foods' => 'food_name',
        'books' => 'book_name',
        'cosmetics' => 'cosmetic_name',
        'other_items' => 'item_name'
    ];

    $expiringItems = [];

    foreach ($tables as $table => $nameColumn) {
        // Check if table exists first
        $tableCheck = $db->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $query = "SELECT $nameColumn as item_name, expiry_date, '$table' as category 
                     FROM $table 
                     WHERE email = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                     ORDER BY expiry_date ASC";

            $stmt = $db->prepare($query);
            if ($stmt) {
                $stmt->bind_param('si', $email, $daysAhead);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $expiringItems[] = $row;
                }
                $stmt->close();
            }
        }
    }

    return $expiringItems;
}

// Function to get all users with expiring items
function getAllUsersWithExpiringItems($db, $daysAhead)
{
    $tables = [
        'documents' => 'document_name',
        'medicines' => 'medicine_name',
        'foods' => 'food_name',
        'books' => 'book_name',
        'cosmetics' => 'cosmetic_name',
        'other_items' => 'item_name'
    ];

    $userEmails = [];

    foreach ($tables as $table => $nameColumn) {
        // Check if table exists first
        $tableCheck = $db->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $query = "SELECT DISTINCT email FROM $table 
                     WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                     AND email IS NOT NULL AND email != ''";

            $stmt = $db->prepare($query);
            if ($stmt) {
                $stmt->bind_param('i', $daysAhead);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    if (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                        $userEmails[] = $row['email'];
                    }
                }
                $stmt->close();
            }
        }
    }

    return array_unique($userEmails);
}

// Function to create expiry notification email content
function createExpiryEmailContent($items, $daysAhead, $userEmail)
{
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; }
            .header { background-color: #4e73df; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 20px; background-color: #f8f9fc; border-radius: 0 0 8px 8px; }
            .item { background-color: white; margin: 10px 0; padding: 15px; border-left: 4px solid #e74a3b; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .urgent { border-left-color: #dc3545; background-color: #fff5f5; }
            .warning { border-left-color: #ffc107; background-color: #fffbf0; }
            .info { border-left-color: #17a2b8; background-color: #f0f9ff; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; border-top: 1px solid #dee2e6; margin-top: 20px; }
            .item-name { font-size: 18px; font-weight: bold; margin-bottom: 8px; color: #2c3e50; }
            .item-details { font-size: 14px; margin-bottom: 5px; }
            .days-remaining { font-weight: bold; font-size: 16px; }
            .urgent-text { color: #dc3545; }
            .warning-text { color: #856404; }
            .info-text { color: #0c5460; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>⚠️ EDR Expiry Alert</h1>
                <p style="margin: 0; font-size: 16px;">Items Expiring Soon</p>
            </div>
            <div class="content">
                <h2>Hello!</h2>
                <p>You have <strong>' . count($items) . ' item(s)</strong> that will expire in the next ' . $daysAhead . ' days. Please review the details below:</p>';

    foreach ($items as $item) {
        $daysUntilExpiry = (strtotime($item['expiry_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
        $daysUntilExpiry = ceil($daysUntilExpiry);

        if ($daysUntilExpiry <= 0) {
            $urgencyClass = 'urgent';
            $textClass = 'urgent-text';
            $statusText = 'EXPIRED';
        } elseif ($daysUntilExpiry <= 1) {
            $urgencyClass = 'urgent';
            $textClass = 'urgent-text';
            $statusText = 'EXPIRES TODAY';
        } elseif ($daysUntilExpiry <= 3) {
            $urgencyClass = 'warning';
            $textClass = 'warning-text';
            $statusText = 'EXPIRES SOON';
        } else {
            $urgencyClass = 'info';
            $textClass = 'info-text';
            $statusText = 'EXPIRES IN ' . $daysUntilExpiry . ' DAYS';
        }

        $categoryName = ucfirst(str_replace('_', ' ', $item['category']));

        $html .= '
                <div class="item ' . $urgencyClass . '">
                    <div class="item-name">' . htmlspecialchars($item['item_name']) . '</div>
                    <div class="item-details"><strong>Category:</strong> ' . $categoryName . '</div>
                    <div class="item-details"><strong>Expiry Date:</strong> ' . date('F j, Y', strtotime($item['expiry_date'])) . '</div>
                    <div class="days-remaining ' . $textClass . '">' . $statusText . '</div>
                </div>';
    }

    $html .= '
                <div style="margin-top: 30px; padding: 20px; background-color: #e8f4fd; border-radius: 8px; border-left: 4px solid #17a2b8;">
                    <h3 style="margin-top: 0; color: #0c5460;">📋 What should you do?</h3>
                    <ul style="margin-bottom: 0;">
                        <li><strong>Use or consume</strong> items before they expire</li>
                        <li><strong>Update expiry dates</strong> if they have been extended</li>
                        <li><strong>Remove expired items</strong> from your inventory</li>
                        <li><strong>Consider donating</strong> items that are still good but you won\'t use</li>
                        <li><strong>Check your EDR account</strong> to manage your items</li>
                    </ul>
                </div>
                
                <p style="margin-top: 20px; text-align: center; font-size: 16px; color: #2c3e50;">
                    <strong>Stay organized and never let anything expire unexpectedly!</strong>
                </p>
            </div>
            <div class="footer">
                <p><strong>EDR - Expiry Date Reminder System</strong></p>
                <p>This notification was sent to: ' . htmlspecialchars($userEmail) . '</p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p style="font-size: 10px; color: #999;">Sent on ' . date('F j, Y \a\t g:i A') . '</p>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_test_notification':
                $testEmail = $_POST['test_email'];
                $subject = "🔔 Test Notification from EDR Admin";

                // Get sample data
                $sampleItems = [
                    [
                        'item_name' => 'Sample Medicine (Test)',
                        'category' => 'medicines',
                        'expiry_date' => date('Y-m-d', strtotime('+3 days'))
                    ],
                    [
                        'item_name' => 'Sample Food Item (Test)',
                        'category' => 'foods',
                        'expiry_date' => date('Y-m-d', strtotime('+1 day'))
                    ]
                ];

                $message = createExpiryEmailContent($sampleItems, 7, $testEmail);

                if (sendEmail($testEmail, $subject, $message)) {
                    $success_message = "✅ Test notification sent successfully to: " . htmlspecialchars($testEmail);
                    $auth->logActivity($_SESSION['admin_id'], 'send_test_notification', "Test notification sent to: $testEmail");
                } else {
                    $error_message = "❌ Failed to send test notification to: " . htmlspecialchars($testEmail) . ". Check email_log.txt for details.";
                }
                break;

            case 'send_bulk_reminder':
                $reminderType = $_POST['reminder_type'];
                $daysAhead = (int) $_POST['days_ahead'];

                // Get all users with expiring items from EDR database
                $userEmails = getAllUsersWithExpiringItems($db, $daysAhead);

                $sentCount = 0;
                $failedCount = 0;

                foreach ($userEmails as $email) {
                    // Get user's specific expiring items
                    $expiringItems = getUserExpiringItems($db, $email, $daysAhead);

                    if (!empty($expiringItems)) {
                        $itemCount = count($expiringItems);
                        $subject = "⚠️ EDR Alert: $itemCount Item(s) Expiring in $daysAhead Days";
                        $message = createExpiryEmailContent($expiringItems, $daysAhead, $email);

                        if (sendEmail($email, $subject, $message)) {
                            $sentCount++;
                        } else {
                            $failedCount++;
                        }

                        // Small delay to avoid overwhelming the mail server
                        usleep(500000); // 0.5 second delay
                    }
                }

                if (empty($userEmails)) {
                    $error_message = "ℹ️ No users found with items expiring in the next $daysAhead days.";
                } elseif ($sentCount > 0) {
                    $success_message = "✅ Bulk reminder sent successfully to $sentCount users for items expiring in $daysAhead days.";
                    if ($failedCount > 0) {
                        $success_message .= " ⚠️ ($failedCount emails failed to send)";
                    }
                } else {
                    $error_message = "❌ Found " . count($userEmails) . " users, but failed to send any emails. Check email_log.txt.";
                }

                $auth->logActivity($_SESSION['admin_id'], 'send_bulk_reminder', "Bulk reminder for $daysAhead days - Sent: $sentCount, Failed: $failedCount");
                break;
        }
    }
}

// Get notification statistics from EDR database
$stats = [
    'total_users' => 0,
    'items_expiring_today' => 0,
    'items_expiring_week' => 0,
    'items_expired' => 0
];

// Count total active users
$userTables = ['users'];
foreach ($userTables as $userTable) {
    $tableCheck = $db->query("SHOW TABLES LIKE '$userTable'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $result = $db->query("SELECT COUNT(*) as count FROM $userTable WHERE account_status = 'active'");
        if (!$result) {
            $result = $db->query("SELECT COUNT(*) as count FROM $userTable");
        }
        if ($result) {
            $stats['total_users'] += $result->fetch_assoc()['count'];
        }
    }
}

// Count items by expiry status
$tables = ['documents', 'medicines', 'foods', 'books', 'cosmetics', 'other_items'];
foreach ($tables as $table) {
    $tableCheck = $db->query("SHOW TABLES LIKE '$table'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Items expiring today
        $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE expiry_date = CURDATE()");
        if ($result) {
            $stats['items_expiring_today'] += $result->fetch_assoc()['count'];
        }

        // Items expiring this week
        $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
        if ($result) {
            $stats['items_expiring_week'] += $result->fetch_assoc()['count'];
        }

        // Expired items
        $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE expiry_date < CURDATE()");
        if ($result) {
            $stats['items_expired'] += $result->fetch_assoc()['count'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - EDR Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #4e73df;
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            font-weight: 500;
            color: #fff;
            padding: .75rem 1rem;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }

        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }

        main {
            margin-top: 56px;
        }

        .email-preview {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            max-height: 400px;
            overflow-y: auto;
        }

        .config-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .stats-card {
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="#">EDR Admin</a>
        <ul class="navbar-nav px-3 ml-auto">
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="logout.php">Sign out</a>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i>
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="items.php">
                                <i class="fas fa-box"></i>
                                Items
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="notifications.php">
                                <i class="fas fa-bell"></i>
                                Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact_admin.php">
                                <i class="fas fa-envelope"></i>
                                Contact Messages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                        </li>
                    </ul>

                    <h6
                        class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-white">
                        <span>Reports</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i>
                                Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logs.php">
                                <i class="fas fa-list"></i>
                                System Logs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">📧 EDR Notifications Management</h1>
                </div>

                <!-- Configuration Warning -->
                <!-- <div class="config-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Email Configuration Required</h6>
                    <p class="mb-2">To send emails to users' Gmail accounts, update the email configuration in
                        <code>email_sender.php</code>:</p>
                    <ul class="mb-2">
                        <li><code>smtp_username</code> - Your Gmail address</li>
                        <li><code>smtp_password</code> - Your Gmail app password</li>
                        <li><code>from_email</code> - Your Gmail address</li>
                    </ul>
                    <small><strong>Gmail Setup:</strong> Enable 2FA and create an "App Password" in your Google Account
                        settings.</small>
                </div> -->

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2 stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['total_users']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning shadow h-100 py-2 stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Expiring Today</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['items_expiring_today']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info shadow h-100 py-2 stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Expiring This Week</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['items_expiring_week']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-danger shadow h-100 py-2 stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Expired Items</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['items_expired']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Send Test Notification -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">🧪 Send Test Notification</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="send_test_notification">
                                    <div class="form-group">
                                        <label for="test_email">Test Email Address</label>
                                        <input type="email" class="form-control" id="test_email" name="test_email"
                                            required placeholder="user@gmail.com">
                                        <small class="form-text text-muted">This will send a sample expiry notification
                                            with test data</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane mr-2"></i>Send Test Email
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Send Bulk Reminder -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">📢 Send Bulk Expiry Reminders</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="send_bulk_reminder">
                                    <div class="form-group">
                                        <label for="reminder_type">Reminder Type</label>
                                        <select class="form-control" id="reminder_type" name="reminder_type" required>
                                            <option value="expiry_reminder">Expiry Reminder</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="days_ahead">Send to users with items expiring in:</label>
                                        <select class="form-control" id="days_ahead" name="days_ahead" required>
                                            <option value="1">Next 1 Day</option>
                                            <option value="3">Next 3 Days</option>
                                            <option value="7">Next 7 Days</option>
                                            <option value="14">Next 14 Days</option>
                                            <option value="30">Next 30 Days</option>
                                        </select>
                                        <small class="form-text text-muted">This will send personalized emails to all
                                            users with items expiring in the selected timeframe</small>
                                    </div>
                                    <button type="submit" class="btn btn-warning"
                                        onclick="return confirm('Are you sure you want to send bulk emails? This will send emails to all users with expiring items.')">
                                        <i class="fas fa-broadcast-tower mr-2"></i>Send Bulk Reminders
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Preview -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">📧 Email Template Preview</h6>
                    </div>
                    <div class="card-body">
                        <p>This is how the expiry notification emails will look to your users:</p>
                        <div class="email-preview">
                            <?php
                            // Show a sample email with realistic EDR data
                            $sampleItems = [
                                [
                                    'item_name' => 'Paracetamol 500mg Tablets',
                                    'category' => 'medicines',
                                    'expiry_date' => date('Y-m-d', strtotime('+2 days'))
                                ],
                                [
                                    'item_name' => 'Fresh Milk 1L',
                                    'category' => 'foods',
                                    'expiry_date' => date('Y-m-d', strtotime('+1 day'))
                                ]
                            ];
                            echo createExpiryEmailContent($sampleItems, 7, 'user@example.com');
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Email Log -->
                <?php if (file_exists('email_log.txt')): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">📋 Email Activity Log</h6>
                        </div>
                        <div class="card-body">
                            <p>Recent email sending activity:</p>
                            <a href="email_log.txt" target="_blank" class="btn btn-outline-info">View Full Email Log</a>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <?php
                                    $logContent = file_get_contents('email_log.txt');
                                    $lines = explode("\n", $logContent);
                                    $recentLines = array_slice(array_reverse($lines), 0, 5);
                                    foreach ($recentLines as $line) {
                                        if (!empty(trim($line))) {
                                            echo htmlspecialchars($line) . "<br>";
                                        }
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>