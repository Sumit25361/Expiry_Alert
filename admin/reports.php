<?php

// ENABLE DEBUGGING - REMOVE IN PRODUCTION
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/admin_auth.php';

// Initialize auth
$auth = new AdminAuth();
$auth->requireLogin();

// Get database connection
$db = $auth->getDb();

// Get date range from query parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today

// Get analytics data
function getAnalyticsData($db, $startDate, $endDate)
{
    $data = [];

    // User registration trends
    $userQuery = "SELECT DATE(created_at) as date, COUNT(*) as count 
                  FROM users 
                  WHERE created_at BETWEEN ? AND ? 
                  GROUP BY DATE(created_at) 
                  ORDER BY date";
    $stmt = $db->prepare($userQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $data['user_registrations'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['user_registrations'][] = $row;
    }

    // Items added trends
    $tables = ['documents', 'medicines', 'foods', 'books', 'cosmetics', 'other_items'];
    $data['items_added'] = [];

    foreach ($tables as $table) {
        $itemQuery = "SELECT DATE(created_at) as date, COUNT(*) as count, '$table' as category
                      FROM $table 
                      WHERE created_at BETWEEN ? AND ? 
                      GROUP BY DATE(created_at)";
        $stmt = $db->prepare($itemQuery);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data['items_added'][] = $row;
        }
    }

    // Category distribution
    $data['category_distribution'] = [];
    foreach ($tables as $table) {
        $countQuery = "SELECT COUNT(*) as count FROM $table";
        $result = $db->query($countQuery);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            $categoryName = ucfirst(str_replace('_', ' ', $table));
            $data['category_distribution'][] = [
                'category' => $categoryName,
                'count' => $count
            ];
        }
    }

    // Expiry status distribution
    $expiryData = [];
    $totalItems = 0;
    $expiredItems = 0;
    $expiringSoon = 0;

    foreach ($tables as $table) {
        // Total items
        $result = $db->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $totalItems += $result->fetch_assoc()['count'];
        }

        // Expired items
        $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE expiry_date < CURDATE()");
        if ($result) {
            $expiredItems += $result->fetch_assoc()['count'];
        }

        // Expiring soon (within 7 days)
        $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
        if ($result) {
            $expiringSoon += $result->fetch_assoc()['count'];
        }
    }

    $goodItems = $totalItems - $expiredItems - $expiringSoon;

    $data['expiry_status'] = [
        ['status' => 'Good', 'count' => $goodItems],
        ['status' => 'Expiring Soon', 'count' => $expiringSoon],
        ['status' => 'Expired', 'count' => $expiredItems]
    ];

    return $data;
}

$analyticsData = getAnalyticsData($db, $startDate, $endDate);

// Get summary statistics
$summaryStats = [
    'total_users' => 0,
    'total_items' => 0,
    'active_users' => 0,
    'new_users_period' => 0
];

$result = $db->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $summaryStats['total_users'] = $result->fetch_assoc()['count'];
}

$result = $db->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'active'");
if ($result) {
    $summaryStats['active_users'] = $result->fetch_assoc()['count'];
}

$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$summaryStats['new_users_period'] = $stmt->get_result()->fetch_assoc()['count'];

// Calculate total items
$tables = ['documents', 'medicines', 'foods', 'books', 'cosmetics', 'other_items'];
foreach ($tables as $table) {
    $result = $db->query("SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $summaryStats['total_items'] += $result->fetch_assoc()['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - Admin Dashboard</title>
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

        .chart-container {
            position: relative;
            height: 400px;
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
                            <a class="nav-link active" href="reports.php">
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
                    <h1 class="h2">Analytics & Reports</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <form method="GET" class="form-inline">
                            <div class="form-group mr-2">
                                <label for="start_date" class="sr-only">Start Date</label>
                                <input type="date" class="form-control form-control-sm" id="start_date"
                                    name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="form-group mr-2">
                                <label for="end_date" class="sr-only">End Date</label>
                                <input type="date" class="form-control form-control-sm" id="end_date" name="end_date"
                                    value="<?php echo $endDate; ?>">
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                        </form>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $summaryStats['total_users']; ?>
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
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Items</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $summaryStats['total_items']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box fa-2x text-gray-300"></i>
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
                                            Active Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $summaryStats['active_users']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                            New Users (Period)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $summaryStats['new_users_period']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Category Distribution Chart -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Items by Category</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expiry Status Chart -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Expiry Status Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="expiryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Registration Trends -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">User Registration Trends</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="userTrendsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Export Reports</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <button class="btn btn-success btn-block" onclick="exportReport('csv')">
                                    <i class="fas fa-file-csv mr-2"></i>Export CSV
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-danger btn-block" onclick="exportReport('pdf')">
                                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-info btn-block" onclick="exportReport('excel')">
                                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-secondary btn-block" onclick="printReport()">
                                    <i class="fas fa-print mr-2"></i>Print Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Category Distribution Chart
        var categoryCtx = document.getElementById('categoryChart').getContext('2d');
        var categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo "'" . implode("', '", array_column($analyticsData['category_distribution'], 'category')) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(', ', array_column($analyticsData['category_distribution'], 'count')); ?>],
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                    ]
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                }
            }
        });

        // Expiry Status Chart
        var expiryCtx = document.getElementById('expiryChart').getContext('2d');
        var expiryChart = new Chart(expiryCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo "'" . implode("', '", array_column($analyticsData['expiry_status'], 'status')) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(', ', array_column($analyticsData['expiry_status'], 'count')); ?>],
                    backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b']
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                }
            }
        });

        // User Registration Trends Chart
        var userTrendsCtx = document.getElementById('userTrendsChart').getContext('2d');
        var userTrendsChart = new Chart(userTrendsCtx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("', '", array_column($analyticsData['user_registrations'], 'date')) . "'"; ?>],
                datasets: [{
                    label: 'New Users',
                    data: [<?php echo implode(', ', array_column($analyticsData['user_registrations'], 'count')); ?>],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function exportReport(format) {
            if (format === 'pdf') {
                window.print(); // Use browser print for PDF
                return;
            }
            var startDate = document.getElementById('start_date').value;
            var endDate = document.getElementById('end_date').value;
            window.location.href = 'export_report.php?format=' + format + '&start_date=' + startDate + '&end_date=' + endDate;
        }

        function printReport() {
            window.print();
        }
    </script>
</body>

</html>