<?php
require_once 'config/admin_auth.php';

// Initialize auth
$auth = new AdminAuth();
$auth->requireLogin();

// Get database connection
$db = $auth->getDb();

$success_message = '';
$error_message = '';

// Handle contact message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $messageId = (int) $_POST['message_id'];

        if ($_POST['action'] === 'mark_replied') {
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied', replied_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $messageId);

            if ($stmt->execute()) {
                $success_message = "Message marked as replied!";
                $auth->logActivity($_SESSION['admin_id'], 'mark_replied', "Marked contact message ID: $messageId as replied");
            } else {
                $error_message = "Error updating message status.";
            }
        } elseif ($_POST['action'] === 'delete_message') {
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->bind_param("i", $messageId);

            if ($stmt->execute()) {
                $success_message = "Message deleted successfully!";
                $auth->logActivity($_SESSION['admin_id'], 'delete_contact', "Deleted contact message ID: $messageId");
            } else {
                $error_message = "Error deleting message.";
            }
        }
    }
}

// Get contact messages with pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build WHERE clause
$where_conditions = [];
if ($status_filter !== 'all') {
    $where_conditions[] = "status = '$status_filter'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM contact_messages $where_clause";
$countResult = $db->query($countQuery);
$totalMessages = $countResult->fetch_assoc()['total'];

// Get messages
$query = "SELECT * FROM contact_messages $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $db->query($query);
$messages = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

$totalPages = ceil($totalMessages / $limit);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_messages,
    COUNT(CASE WHEN status = 'replied' THEN 1 END) as replied_messages,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_messages,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as recent_messages
    FROM contact_messages";
$stats_result = $db->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin Dashboard</title>
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

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-replied {
            background-color: #d4edda;
            color: #155724;
        }

        .message-preview {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
                            <a class="nav-link active" href="contact_admin.php">
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
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Contact Messages</h1>
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
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Messages</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_messages']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-envelope fa-2x text-gray-300"></i>
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
                                            Pending</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['pending_messages']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                            Replied</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['replied_messages']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Messages</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group mr-3">
                                <select name="status" class="form-control">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All
                                        Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                                        Pending</option>
                                    <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>
                                        Replied</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary mr-2">Filter</button>
                            <a href="contact_admin.php" class="btn btn-secondary">Clear</a>
                        </form>
                    </div>
                </div>

                <!-- Messages Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Contact Messages (<?php echo $totalMessages; ?>
                            total)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                        <tr>
                                            <td><?php echo $message['id']; ?></td>
                                            <td><?php echo htmlspecialchars($message['name']); ?></td>
                                            <td><?php echo htmlspecialchars($message['email']); ?></td>
                                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                            <td>
                                                <div class="message-preview"
                                                    title="<?php echo htmlspecialchars($message['message']); ?>">
                                                    <?php echo htmlspecialchars($message['message']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $message['status']; ?>">
                                                    <?php echo ucfirst($message['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                            <td>
                                                <?php if ($message['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        onclick="markReplied(<?php echo $message['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-info"
                                                    onclick="viewMessage(<?php echo $message['id']; ?>, '<?php echo htmlspecialchars($message['name']); ?>', '<?php echo htmlspecialchars($message['email']); ?>', '<?php echo htmlspecialchars($message['subject']); ?>', '<?php echo htmlspecialchars($message['message']); ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="deleteMessage(<?php echo $message['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>">Next</a>
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

    <!-- Message View Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Message Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Name:</strong> <span id="modalName"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong> <span id="modalEmail"></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <strong>Subject:</strong> <span id="modalSubject"></span>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <strong>Message:</strong>
                            <div class="mt-2 p-3 bg-light rounded">
                                <span id="modalMessage"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden forms for actions -->
    <form id="replyForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="mark_replied">
        <input type="hidden" name="message_id" id="replyMessageId">
    </form>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_message">
        <input type="hidden" name="message_id" id="deleteMessageId">
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markReplied(messageId) {
            if (confirm('Mark this message as replied?')) {
                document.getElementById('replyMessageId').value = messageId;
                document.getElementById('replyForm').submit();
            }
        }

        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                document.getElementById('deleteMessageId').value = messageId;
                document.getElementById('deleteForm').submit();
            }
        }

        function viewMessage(id, name, email, subject, message) {
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalEmail').textContent = email;
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalMessage').textContent = message;
            $('#messageModal').modal('show');
        }
    </script>
</body>

</html>