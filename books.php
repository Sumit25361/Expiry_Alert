<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Handle delete
        $id = (int)$_POST['id'];
        $email = $db->escape($_SESSION['email']);
        
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ? AND email = ?");
        $stmt->bind_param("is", $id, $email);
        
        if ($stmt->execute()) {
            $success_message = "Book/media item deleted successfully!";
        } else {
            $error_message = "Error deleting item.";
        }
        $stmt->close();
    } else {
        // Handle add
        $book_name = $db->escape($_POST['book_name']);
        $mfg_date = !empty($_POST['mfg_date']) ? $_POST['mfg_date'] : null;
        $expiry_date = $_POST['expiry_date'];
        $email = $db->escape($_SESSION['email']);

        // Validate dates
        $today = date('Y-m-d');
        if ($expiry_date <= $today) {
            $error_message = "Return date must be in the future.";
        } elseif ($mfg_date && $mfg_date >= $expiry_date) {
            $error_message = "Issue date must be before return date.";
        } else {
            $stmt = $conn->prepare("INSERT INTO books (email, book_name, mfg_date, expiry_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $book_name, $mfg_date, $expiry_date);
            
            if ($stmt->execute()) {
                $success_message = "Book/media item added successfully!";

                // Send email notification
                require_once 'services/notification_service.php';
                $notificationService = new NotificationService();
                $notificationService->sendItemAddedNotification($email, $book_name, 'Book', $expiry_date);

            } else {
                $error_message = "Error adding item.";
            }
            $stmt->close();
        }
    }
}

// Fetch books
$email = $db->escape($_SESSION['email']);
$result = $conn->query("SELECT * FROM books WHERE email='$email' ORDER BY expiry_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books & Media - Expiry Alert</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .header {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header h2 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header::before {
            content: '📚';
            font-size: 3rem;
            position: absolute;
            top: 20px;
            left: 30px;
            opacity: 0.3;
        }

        .form-section {
            padding: 40px;
            background: white;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .book-info {
            background: #f3e8ff;
            border: 1px solid #7c3aed;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-container {
            background: #f8fafc;
            padding: 30px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        input[type="text"], input[type="date"] {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        input[type="text"]:focus, input[type="date"]:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            transform: translateY(-2px);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 8px 12px;
            font-size: 0.8rem;
        }

        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .table-section {
            padding: 0 40px 40px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h3 {
            font-size: 1.5rem;
            color: #1f2937;
            font-weight: 700;
        }

        .search-box {
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            width: 250px;
            font-size: 0.9rem;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }

        th {
            padding: 20px;
            text-align: left;
            font-weight: 700;
            color: #374151;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #4b5563;
            font-size: 1rem;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-expired {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-warning {
            background: #fef3c7;
            color: #d97706;
        }

        .status-good {
            background: #dcfce7;
            color: #16a34a;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .header h2 {
                font-size: 2rem;
            }
            
            .form-section, .table-section {
                padding: 20px;
            }
            
            .search-box {
                width: 100%;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <div class="header">
            <h2>Books & Media Manager</h2>
            <p>Track library books, rentals, and subscription return dates</p>
        </div>

        <div class="form-section">
            <div class="book-info">
                <span>📖</span>
                <div>
                    <strong>Library Tip:</strong> Keep track of due dates to avoid late fees and maintain good borrowing privileges.
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="message success-message">
                    <span>✅</span>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="message error-message">
                    <span>❌</span>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" id="bookForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="book_name">Book/Media Title</label>
                            <input type="text" name="book_name" id="book_name" placeholder="e.g., The Great Gatsby, DVD Movie" required>
                        </div>
                        <div class="form-group">
                            <label for="mfg_date">Issue Date (Optional)</label>
                            <input type="date" name="mfg_date" id="mfg_date">
                        </div>
                        <div class="form-group">
                            <label for="expiry_date">Return/Due Date</label>
                            <input type="date" name="expiry_date" id="expiry_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            ➕ Add Item
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            ⬅ Back to Home
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-section">
            <div class="table-header">
                <h3>Your Books & Media</h3>
                <input type="text" class="search-box" placeholder="Search books..." id="searchInput">
            </div>

            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table id="booksTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Days Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): 
                                $expiry_date = new DateTime($row['expiry_date']);
                                $today = new DateTime();
                                $diff = $today->diff($expiry_date);
                                $days_remaining = $expiry_date > $today ? $diff->days : -$diff->days;
                                
                                $status_class = 'status-good';
                                $status_text = 'On Time';
                                if ($days_remaining < 0) {
                                    $status_class = 'status-expired';
                                    $status_text = 'Overdue';
                                } elseif ($days_remaining <= 3) {
                                    $status_class = 'status-warning';
                                    $status_text = 'Due Soon';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['book_name']); ?></td>
                                    <td><?php echo $row['mfg_date'] ? date('M d, Y', strtotime($row['mfg_date'])) : 'N/A'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    <td><?php echo $days_remaining >= 0 ? $days_remaining . ' days' : 'Overdue by ' . abs($days_remaining) . ' days'; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-danger">🗑️ Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📚</div>
                        <h3>No books or media added yet</h3>
                        <p>Add your first item to start tracking due dates</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today for expiry date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('expiry_date').min = today;

        // Set maximum date to today for issue date
        document.getElementById('mfg_date').max = today;

        // Validate dates
        document.getElementById('mfg_date').addEventListener('change', function() {
            const issueDate = this.value;
            const expiryInput = document.getElementById('expiry_date');
            
            if (issueDate) {
                expiryInput.min = issueDate;
            } else {
                expiryInput.min = today;
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('booksTable');
            if (!table) return;
            
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const bookName = rows[i].getElementsByTagName('td')[0];
                if (bookName) {
                    const textValue = bookName.textContent || bookName.innerText;
                    if (textValue.toLowerCase().indexOf(searchTerm) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });

        // Form submission animation
        document.getElementById('bookForm').addEventListener('submit', function(e) {
            const button = this.querySelector('.btn-primary');
            button.innerHTML = '⏳ Adding...';
            button.style.opacity = '0.7';
        });
    </script>
</body>
</html>
