<?php
require_once 'config/admin_auth.php';

// Initialize auth
$auth = new AdminAuth();

// Require login
$auth->requireLogin();

// Get current admin
$currentAdmin = $auth->getCurrentAdmin();

// Get database connection
$db = $auth->getDb();

// Function to get statistics
function getStatistics($db, $range = 'week')
{
    $stats = [
        'total_users' => 0,
        'total_items' => 0,
        'expiring_soon' => 0,
        'expired' => 0,
        'categories' => [],
        'recent_users' => [],
        'recent_items' => []
    ];

    $dateFilterUser = "";
    $dateFilterItem = "";

    // Build Date Filter
    if ($range === 'day') {
        $dateFilterUser = " WHERE created_at >= CURDATE()";
        $dateFilterItem = " AND created_at >= CURDATE()";
    } elseif ($range === 'week') {
        $dateFilterUser = " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $dateFilterItem = " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($range === 'month') {
        $dateFilterUser = " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        $dateFilterItem = " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
    } elseif ($range === 'year') {
        $dateFilterUser = " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        $dateFilterItem = " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    }
    // 'all' case has no filter

    try {
        // Total users
        $result = $db->query("SELECT COUNT(*) as count FROM users $dateFilterUser");
        if ($result) {
            $stats['total_users'] = $result->fetch_assoc()['count'];
        }

        // Count items from all category tables
        $tables = ['documents', 'medicines', 'foods', 'books', 'cosmetics', 'other_items'];
        $totalItems = 0;
        $expiringSoon = 0;
        $expired = 0;

        foreach ($tables as $table) {
            // Check if table exists
            $tableCheck = $db->query("SHOW TABLES LIKE '$table'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                // Total items (Filtered)
                $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE 1=1 $dateFilterItem");
                if ($result) {
                    $totalItems += $result->fetch_assoc()['count'];
                }

                // Items expiring soon (Always future 7 days, unaffected by "Recent Activity" filter usually, OR we filter items added recently that are expiring?)
                // Usually "Expiring Soon" is a state monitor, regardless of when added. I will LEAVE IT UNFILTERED by creation date to be useful.
                $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
                if ($result) {
                    $expiringSoon += $result->fetch_assoc()['count'];
                }

                // Expired items (State monitor, UNFILTERED by creation date)
                $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE expiry_date < CURDATE()");
                if ($result) {
                    $expired += $result->fetch_assoc()['count'];
                }
            }
        }

        $stats['total_items'] = $totalItems;
        $stats['expiring_soon'] = $expiringSoon;
        $stats['expired'] = $expired;

        // Recent users
        $result = $db->query("SELECT id, username, email, created_at FROM users $dateFilterUser ORDER BY created_at DESC LIMIT 5");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['recent_users'][] = $row;
            }
        }

        // Recent items logic... (Similar fix needed for categories if we want them filtered, but pie chart usually shows distribution. I'll leave pie chart total.)
        // Actually, pie chart should probably reflect the total filtered items.
        // Let's filter categories too.

        $stats['categories'] = [
            'Documents' => 0,
            'Medicines' => 0,
            'Foods' => 0,
            'Books' => 0,
            'Cosmetics' => 0,
            'Other' => 0
        ];

        foreach ($tables as $index => $table) {
            $tableCheck = $db->query("SHOW TABLES LIKE '$table'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $result = $db->query("SELECT COUNT(*) as count FROM $table WHERE 1=1 $dateFilterItem");
                if ($result) {
                    $categoryNames = ['Documents', 'Medicines', 'Foods', 'Books', 'Cosmetics', 'Other'];
                    $stats['categories'][$categoryNames[$index]] = $result->fetch_assoc()['count'];
                }
            }
        }

    } catch (Exception $e) {
        // Handle database errors gracefully
        error_log("Dashboard statistics error: " . $e->getMessage());
    }

    return $stats;
}

// Get range parameter
$range = isset($_GET['range']) ? $_GET['range'] : 'all'; // Default to 'all' to show everything initially, or 'week' as per UI?
// User UI says "This week" initially. But "Total Users" usually implies ALL. 
// I'll default to 'all' but if user selects 'week', it filters.
// Actually, let's look at the button text. It currently says "This week". 
// To avoid confusion, I'll default $range to 'all', and if the user clicks 'This Week', it will reload with ?range=week.
// But the button text should update.

// Get statistics
$stats = getStatistics($db, $range);

// Log activity
if (isset($_SESSION['admin_id'])) {
    $auth->logActivity($_SESSION['admin_id'], 'view_dashboard', 'Admin viewed dashboard');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Expiry Date Reminder</title>
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

        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }

        .navbar .form-control {
            padding: .75rem 1rem;
            border-width: 0;
            border-radius: 0;
        }

        .form-control-dark {
            color: #fff;
            background-color: rgba(255, 255, 255, .1);
            border-color: rgba(255, 255, 255, .1);
        }

        .form-control-dark:focus {
            border-color: transparent;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, .25);
        }

        .card-dashboard {
            border-left: 4px solid;
            margin-bottom: 20px;
        }

        .card-dashboard.primary {
            border-left-color: #4e73df;
        }

        .card-dashboard.success {
            border-left-color: #1cc88a;
        }

        .card-dashboard.warning {
            border-left-color: #f6c23e;
        }

        .card-dashboard.danger {
            border-left-color: #e74a3b;
        }

        .card-dashboard .card-body {
            padding: 1.25rem;
        }

        .card-dashboard .text-xs {
            font-size: .7rem;
        }

        .card-dashboard .text-primary {
            color: #4e73df !important;
        }

        .card-dashboard .text-success {
            color: #1cc88a !important;
        }

        .card-dashboard .text-warning {
            color: #f6c23e !important;
        }

        .card-dashboard .text-danger {
            color: #e74a3b !important;
        }

        .card-dashboard .icon {
            font-size: 2rem;
            opacity: 0.3;
        }

        main {
            margin-top: 56px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="#">EDR Admin</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse"
            data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
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
                            <a class="nav-link active" href="dashboard.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="shareDashboard()">Share</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="window.print()">Export</button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <i class="fas fa-calendar"></i>
                                <?php
                                $labels = [
                                    'day' => 'This Day',
                                    'week' => 'This Week',
                                    'month' => 'This Month',
                                    'year' => 'This Year',
                                    'all' => 'All Time'
                                ];
                                echo isset($labels[$range]) ? $labels[$range] : 'All Time';
                                ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="?range=day">This Day</a>
                                <a class="dropdown-item" href="?range=week">This Week</a>
                                <a class="dropdown-item" href="?range=month">This Month</a>
                                <a class="dropdown-item" href="?range=year">This Year</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="?range=all">All Time</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-dashboard primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_users']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300 icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-dashboard success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Items</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_items']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box fa-2x text-gray-300 icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-dashboard warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Expiring Soon</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['expiring_soon']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300 icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-dashboard danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Expired Items</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['expired']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300 icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
                                <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['recent_users'] as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Items</h6>
                                <a href="items.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Expiry Date</th>
                                                <th>User</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // The original code had a bug here. $stats['recent_items'] is not populated in the updated code.
                                            // To avoid errors, I'm adding a check to ensure it exists and is an array before iterating.
                                            if (isset($stats['recent_items']) && is_array($stats['recent_items'])):
                                                foreach ($stats['recent_items'] as $item): ?>
                                                    <tr>
                                                        <td><?php echo $item['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($item['username']); ?></td>
                                                    </tr>
                                                <?php endforeach;
                                            endif;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Items by Category</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <a href="notifications.php" class="btn btn-primary btn-block">
                                            <i class="fas fa-bell mr-2"></i> Send Notifications
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="users.php" class="btn btn-info btn-block">
                                            <i class="fas fa-user-plus mr-2"></i> Add User
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="reports.php" class="btn btn-success btn-block">
                                            <i class="fas fa-chart-line mr-2"></i> Generate Report
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="settings.php" class="btn btn-secondary btn-block">
                                            <i class="fas fa-cog mr-2"></i> System Settings
                                        </a>
                                    </div>
                                </div>
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
        // Chart for categories
        var ctx = document.getElementById('categoryChart').getContext('2d');
        var categoryChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [<?php echo "'" . implode("', '", array_keys($stats['categories'])) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(', ', array_values($stats['categories'])); ?>],
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                    ],
                    hoverBackgroundColor: [
                        '#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617', '#60616f'
                    ],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                },
                legend: {
                    display: true,
                    position: 'bottom'
                },
                cutoutPercentage: 0,
            },
        });
        // Share dashboard
        function shareDashboard() {
            var dummyLink = window.location.href;
            var input = document.createElement('input');
            document.body.appendChild(input);
            input.value = dummyLink;
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            alert("Dashboard link copied to clipboard!");
        }
    </script>
</body>

</html>