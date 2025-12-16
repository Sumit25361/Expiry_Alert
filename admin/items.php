<?php
require_once 'config/admin_auth.php';


// Initialize auth
$auth = new AdminAuth();
$auth->requireLogin();

// Get database connection
$db = $auth->getDb();

$success_message = '';
$error_message = '';

// Handle item actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_item') {
        $table = $_POST['table'];
        $itemId = (int) $_POST['item_id'];

        // Validate table name for security
        $allowedTables = ['documents', 'medicines', 'foods', 'books', 'cosmetics', 'other_items'];
        if (in_array($table, $allowedTables)) {
            $stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->bind_param("i", $itemId);

            if ($stmt->execute()) {
                $success_message = "Item deleted successfully!";
                $auth->logActivity($_SESSION['admin_id'], 'delete_item', "Deleted item ID: $itemId from table: $table");
            } else {
                $error_message = "Error deleting item.";
            }
        }
    }
}

// Get all items without pagination
$page = 1;
$limit = 999999; // Large number to get all items
$offset = 0;

// Filter by category
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on filters
$items = [];
$totalItems = 0;

$tables = [
    'documents' => 'Documents',
    'medicines' => 'Medicines',
    'foods' => 'Foods',
    'books' => 'Books',
    'cosmetics' => 'Cosmetics',
    'other_items' => 'Other Items'
];

if ($category === 'all') {
    // Get items from all tables
    foreach ($tables as $table => $tableName) {
        $nameColumn = $table === 'documents' ? 'document_name' :
            ($table === 'medicines' ? 'medicine_name' :
                ($table === 'foods' ? 'food_name' :
                    ($table === 'books' ? 'book_name' :
                        ($table === 'cosmetics' ? 'cosmetic_name' : 'item_name'))));

        $whereClause = '';
        if (!empty($search)) {
            $whereClause = "WHERE $nameColumn LIKE '%$search%' OR email LIKE '%$search%'";
        }

        $query = "SELECT id, email, $nameColumn as name, mfg_date, expiry_date, created_at, '$table' as table_name, '$tableName' as category 
                  FROM $table $whereClause 
                  ORDER BY created_at DESC";

        $result = $db->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
    }

    // Sort by created_at
    usort($items, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    $totalItems = count($items);
    // Remove the array_slice to show all items

} else {
    // Get items from specific table
    if (array_key_exists($category, $tables)) {
        $nameColumn = $category === 'documents' ? 'document_name' :
            ($category === 'medicines' ? 'medicine_name' :
                ($category === 'foods' ? 'food_name' :
                    ($category === 'books' ? 'book_name' :
                        ($category === 'cosmetics' ? 'cosmetic_name' : 'item_name'))));

        $whereClause = '';
        if (!empty($search)) {
            $whereClause = "WHERE $nameColumn LIKE '%$search%' OR email LIKE '%$search%'";
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM $category $whereClause";
        $countResult = $db->query($countQuery);
        $totalItems = $countResult->fetch_assoc()['total'];

        // Get items
        $query = "SELECT id, email, $nameColumn as name, mfg_date, expiry_date, created_at, '$category' as table_name, '{$tables[$category]}' as category 
                  FROM $category $whereClause 
                  ORDER BY created_at DESC 
                  LIMIT $limit OFFSET $offset";

        $result = $db->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
    }
}

$totalPages = ceil($totalItems / $limit);

// Get statistics
$stats = [];
foreach ($tables as $table => $tableName) {
    $result = $db->query("SELECT COUNT(*) as count FROM $table");
    $stats[$tableName] = $result ? $result->fetch_assoc()['count'] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items Management - Admin Dashboard</title>
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

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-expired {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-expiring {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-good {
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
                            <a class="nav-link active" href="items.php">
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
                    <h1 class="h2">Items Management</h1>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <?php foreach ($stats as $category => $count): ?>
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                <?php echo $category; ?>
                                            </div>
                                            <div class="h6 mb-0 font-weight-bold text-gray-800"><?php echo $count; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Items</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group mr-3">
                                <select name="category" class="form-control">
                                    <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All
                                        Categories</option>
                                    <?php foreach ($tables as $table => $tableName): ?>
                                        <option value="<?php echo $table; ?>" <?php echo $category === $table ? 'selected' : ''; ?>>
                                            <?php echo $tableName; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <input type="text" class="form-control" name="search" placeholder="Search items..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary mr-2">Filter</button>
                            <a href="items.php" class="btn btn-secondary">Clear</a>
                        </form>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Items List (<?php echo $totalItems; ?> total)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>User Email</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item):
                                        $expiryDate = new DateTime($item['expiry_date']);
                                        $today = new DateTime();
                                        $diff = $today->diff($expiryDate);
                                        $daysRemaining = $expiryDate > $today ? $diff->days : -$diff->days;

                                        $statusClass = 'status-good';
                                        $statusText = '';

                                        if ($daysRemaining < 0) {
                                            $statusClass = 'status-expired';
                                            $statusText = 'Expired';
                                        } elseif ($daysRemaining <= 7) {
                                            $statusClass = 'status-expiring';
                                            $statusText = 'Expire Soon';
                                        } else {
                                            $statusClass = 'status-good';
                                            $statusText = $daysRemaining . ' days remaining';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $item['id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo $item['category']; ?></td>
                                            <td><?php echo htmlspecialchars($item['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="deleteItem('<?php echo $item['table_name']; ?>', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Hidden form for delete action -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_item">
        <input type="hidden" name="table" id="deleteTable">
        <input type="hidden" name="item_id" id="deleteItemId">
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteItem(table, itemId, itemName) {
            if (confirm('Are you sure you want to delete "' + itemName + '"? This action cannot be undone.')) {
                document.getElementById('deleteTable').value = table;
                document.getElementById('deleteItemId').value = itemId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>

</html>