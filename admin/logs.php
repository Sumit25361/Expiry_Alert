<?php
require_once 'config/admin_auth.php';


// Initialize auth
$auth = new AdminAuth();
$auth->requireLogin();

// Get database connection
$db = $auth->getDb();

// Get logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filter options
$logType = isset($_GET['log_type']) ? $_GET['log_type'] : 'all';
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';

// Build where clause
$whereConditions = [];
$params = [];
$types = '';

if ($logType !== 'all') {
    $whereConditions[] = "action = ?";
    $params[] = $logType;
    $types .= 's';
}

switch ($dateFilter) {
    case 'today':
        $whereConditions[] = "DATE(created_at) = CURDATE()";
        break;
    case 'week':
        $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM admin_activity_log $whereClause";
$countStmt = $db->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalLogs / $limit);

// Get logs
$query = "SELECT aal.*, a.username 
          FROM admin_activity_log aal 
          LEFT JOIN admins a ON aal.admin_id = a.id 
          $whereClause 
          ORDER BY aal.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$logs = $stmt->get_result();

// Get log statistics
$statsQuery = "SELECT 
    COUNT(*) as total_logs,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_logs,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_logs,
    COUNT(DISTINCT admin_id) as active_admins
    FROM admin_activity_log";
$statsResult = $db->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin Dashboard</title>
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
        .log-entry {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .log-level {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .log-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .log-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .log-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .log-success {
            background-color: #d4edda;
            color: #155724;
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
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell"></i>
                                Notifications
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link" href="contact_admin.php">
                                <i class="fas fa-envelope"></i>
                                Contact Messages
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                        </li> -->
                    </ul>

                    <!-- <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-white">
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
                            <a class="nav-link active" href="logs.php">
                                <i class="fas fa-list"></i>
                                System Logs
                            </a>
                        </li>
                    </ul> -->
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">System Logs</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Logs</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_logs']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Today's Logs</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['today_logs']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            This Week</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['week_logs']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Active Admins</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active_admins']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Logs</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="log_type" class="mr-2">Log Type:</label>
                                <select name="log_type" id="log_type" class="form-control">
                                    <option value="all" <?php echo $logType === 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <option value="login" <?php echo $logType === 'login' ? 'selected' : ''; ?>>Login</option>
                                    <option value="logout" <?php echo $logType === 'logout' ? 'selected' : ''; ?>>Logout</option>
                                    <option value="delete_user" <?php echo $logType === 'delete_user' ? 'selected' : ''; ?>>Delete User</option>
                                    <option value="delete_item" <?php echo $logType === 'delete_item' ? 'selected' : ''; ?>>Delete Item</option>
                                    <option value="update_settings" <?php echo $logType === 'update_settings' ? 'selected' : ''; ?>>Update Settings</option>
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="date_filter" class="mr-2">Date:</label>
                                <select name="date_filter" id="date_filter" class="form-control">
                                    <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>This Week</option>
                                    <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="all" <?php echo $dateFilter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary mr-2">Filter</button>
                            <a href="logs.php" class="btn btn-secondary">Clear</a>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Activity Logs (<?php echo $totalLogs; ?> total)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Admin</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($log = $logs->fetch_assoc()): 
                                        $logClass = 'log-info';
                                        if (in_array($log['action'], ['delete_user', 'delete_item'])) {
                                            $logClass = 'log-error';
                                        } elseif (in_array($log['action'], ['login', 'logout'])) {
                                            $logClass = 'log-success';
                                        } elseif (strpos($log['action'], 'update') !== false) {
                                            $logClass = 'log-warning';
                                        }
                                    ?>
                                    <tr>
                                        <td class="log-entry"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></td>
                                        <td>
                                            <span class="log-level <?php echo $logClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                            </span>
                                        </td>
                                        <td class="log-entry"><?php echo htmlspecialchars($log['description'] ?? ''); ?></td>
                                        <td class="log-entry"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&log_type=<?php echo $logType; ?>&date_filter=<?php echo $dateFilter; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&log_type=<?php echo $logType; ?>&date_filter=<?php echo $dateFilter; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&log_type=<?php echo $logType; ?>&date_filter=<?php echo $dateFilter; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
