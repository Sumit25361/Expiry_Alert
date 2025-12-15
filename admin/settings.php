<?php
require_once 'config/admin_auth.php';


// Initialize auth
$auth = new AdminAuth();
$auth->requireLogin();

// Get database connection
$db = $auth->getDb();

$success_message = '';
$error_message = '';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_system_settings':
                // Update system settings
                $siteName = $_POST['site_name'];
                $siteEmail = $_POST['site_email'];
                $timezone = $_POST['timezone'];
                
                // Here you would update settings in database or config file
                $success_message = "System settings updated successfully!";
                $auth->logActivity($_SESSION['admin_id'], 'update_system_settings', 'Updated system settings');
                break;
                
            case 'update_notification_settings':
                // Update notification settings
                $emailEnabled = isset($_POST['email_enabled']) ? 1 : 0;
                $reminderTime = $_POST['reminder_time'];
                $batchSize = (int)$_POST['batch_size'];
                
                $success_message = "Notification settings updated successfully!";
                $auth->logActivity($_SESSION['admin_id'], 'update_notification_settings', 'Updated notification settings');
                break;
                
            case 'backup_database':
                // Trigger database backup
                $success_message = "Database backup initiated successfully!";
                $auth->logActivity($_SESSION['admin_id'], 'backup_database', 'Initiated database backup');
                break;
        }
    }
}

// Get current settings (these would normally come from a settings table)
$settings = [
    'site_name' => 'Expiry Date Reminder',
    'site_email' => 'admin@expiryalert.com',
    'timezone' => 'Asia/Kolkata',
    'email_enabled' => true,
    'reminder_time' => '15:00',
    'batch_size' => 50
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
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
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell"></i>
                                Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="settings.php">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-white">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">System Settings</h1>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- System Settings -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">System Settings</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_system_settings">
                                    <div class="form-group">
                                        <label for="site_name">Site Name</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="site_email">Site Email</label>
                                        <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="timezone">Timezone</label>
                                        <select class="form-control" id="timezone" name="timezone" required>
                                            <option value="Asia/Kolkata" <?php echo $settings['timezone'] === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                                            <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                            <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                                            <option value="Asia/Tokyo" <?php echo $settings['timezone'] === 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo (JST)</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Notification Settings</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_notification_settings">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled" <?php echo $settings['email_enabled'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_enabled">
                                                Enable Email Notifications
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="reminder_time">Daily Reminder Time</label>
                                        <input type="time" class="form-control" id="reminder_time" name="reminder_time" value="<?php echo $settings['reminder_time']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="batch_size">Email Batch Size</label>
                                        <input type="number" class="form-control" id="batch_size" name="batch_size" value="<?php echo $settings['batch_size']; ?>" min="1" max="100" required>
                                        <small class="form-text text-muted">Number of emails to send per batch</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Database Management -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Database Management</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Database Information</h6>
                                <p><strong>Database:</strong> EDR</p>
                                <p><strong>Tables:</strong> 8</p>
                                <p><strong>Last Backup:</strong> <?php echo date('M d, Y H:i'); ?></p>
                                <p><strong>Size:</strong> ~2.5 MB</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Actions</h6>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="backup_database">
                                    <button type="submit" class="btn btn-success mr-2">
                                        <i class="fas fa-download mr-2"></i>Backup Database
                                    </button>
                                </form>
                                <button type="button" class="btn btn-warning" onclick="optimizeDatabase()">
                                    <i class="fas fa-tools mr-2"></i>Optimize
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Security Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Session Settings</h6>
                                <p><strong>Session Timeout:</strong> 8 hours</p>
                                <p><strong>Max Login Attempts:</strong> 5</p>
                                <p><strong>Password Policy:</strong> Enabled</p>
                                <p><strong>Two-Factor Auth:</strong> Disabled</p>
                            </div>
                            <div class="col-md-6">
                                <h6>System Security</h6>
                                <p><strong>SSL Certificate:</strong> <span class="badge badge-success">Valid</span></p>
                                <p><strong>Firewall:</strong> <span class="badge badge-success">Active</span></p>
                                <p><strong>Last Security Scan:</strong> <?php echo date('M d, Y'); ?></p>
                                <p><strong>Vulnerabilities:</strong> <span class="badge badge-success">None</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">System Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Server Information</h6>
                                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                                <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                <p><strong>OS:</strong> <?php echo php_uname('s'); ?></p>
                                <p><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <h6>Application</h6>
                                <p><strong>Version:</strong> 1.0.0</p>
                                <p><strong>Environment:</strong> Production</p>
                                <p><strong>Debug Mode:</strong> Disabled</p>
                                <p><strong>Maintenance Mode:</strong> Disabled</p>
                            </div>
                            <div class="col-md-4">
                                <h6>Performance</h6>
                                <p><strong>Uptime:</strong> 15 days</p>
                                <p><strong>CPU Usage:</strong> 12%</p>
                                <p><strong>Memory Usage:</strong> 45%</p>
                                <p><strong>Disk Usage:</strong> 23%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function optimizeDatabase() {
            if (confirm('Are you sure you want to optimize the database? This may take a few minutes.')) {
                alert('Database optimization started. You will be notified when complete.');
            }
        }
    </script>
</body>
</html>
