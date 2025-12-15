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
        
        $stmt = $conn->prepare("DELETE FROM documents WHERE id = ? AND email = ?");
        $stmt->bind_param("is", $id, $email);
        
        if ($stmt->execute()) {
            $success_message = "Document deleted successfully!";
        } else {
            $error_message = "Error deleting document.";
        }
        $stmt->close();
    } else {
        // Handle add document
        $document_name = $db->escape($_POST['document_name']);
        $mfg_date = !empty($_POST['mfg_date']) ? $_POST['mfg_date'] : null;
        $expiry_date = $_POST['expiry_date'];
        $email = $db->escape($_SESSION['email']);

        // Validate dates
        $today = date('Y-m-d');
        if ($expiry_date <= $today) {
            $error_message = "Expiry date must be in the future.";
        } elseif ($mfg_date && $mfg_date >= $expiry_date) {
            $error_message = "Manufacturing date must be before expiry date.";
        } else {
            $stmt = $conn->prepare("INSERT INTO documents (email, document_name, mfg_date, expiry_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $document_name, $mfg_date, $expiry_date);
            
            if ($stmt->execute()) {
                $success_message = "Document added successfully! 📧 Email notification will be sent...";
                
                // Log the document addition for EmailJS
                $log_entry = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_email' => $email,
                    'user_name' => isset($_SESSION['name']) ? $_SESSION['name'] : "User",
                    'document_name' => $document_name,
                    'issue_date' => $mfg_date ?: 'Not specified',
                    'expiry_date' => $expiry_date,
                    'days_until_expiry' => ceil((strtotime($expiry_date) - time()) / (60 * 60 * 24))
                ];
                
                // Save for JavaScript to pick up
                file_put_contents('email_queue.json', json_encode($log_entry) . "\n", FILE_APPEND);
                
            } else {
                $error_message = "Error adding document.";
            }
            $stmt->close();
        }
    }
}

// Fetch documents for display
$email = $db->escape($_SESSION['email']);
$result = $conn->query("SELECT * FROM documents WHERE email='$email' ORDER BY expiry_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Expiry Manager</title>
    
    <!-- EmailJS SDK -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    
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
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
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
            content: '📄';
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
            animation: slideIn 0.3s ease-out;
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

        .warning-message {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #f59e0b;
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
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
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
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
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

        .emailjs-info {
            background: #e0f2fe;
            border: 1px solid #0284c7;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .emailjs-info h4 {
            color: #0284c7;
            margin-bottom: 10px;
        }

        .setup-needed {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .setup-needed h4 {
            color: #d97706;
            margin-bottom: 10px;
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

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .email-status {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .email-status.show {
            transform: translateX(0);
        }

        .email-status.success {
            background: #16a34a;
        }

        .email-status.error {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <div class="header">
            <h2>Document Expiry Manager</h2>
            <p>Keep track of your important document expiration dates</p>
        </div>

        <div class="form-section">
            <!-- EmailJS Setup Check -->
            <!-- <div class="setup-needed" id="emailjs-setup">
                <h4>⚙️ EmailJS Setup Required</h4>
                <p>To enable email notifications, you need to configure EmailJS. This will allow real emails to be sent to your Gmail account.</p>
                <p><strong>Current Status:</strong> <span id="emailjs-status">Not configured</span></p>
                <div style="margin-top: 15px;">
                    <a href="emailjs-setup.html" target="_blank" class="btn" style="background: #f59e0b; color: white; text-decoration: none;">📧 Setup EmailJS</a>
                    <button onclick="testEmailJS()" class="btn" style="background: #16a34a; color: white;">🧪 Test Configuration</button>
                </div>
            </div> -->

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
                <form method="POST" id="documentForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="document_name">Document Name</label>
                            <input type="text" name="document_name" id="document_name" placeholder="e.g., Passport, License" required>
                        </div>
                        <div class="form-group">
                            <label for="mfg_date">Issue Date (Optional)</label>
                            <input type="date" name="mfg_date" id="mfg_date">
                        </div>
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="date" name="expiry_date" id="expiry_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            ➕ Add Document
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
                <h3>Your Documents</h3>
                <input type="text" class="search-box" placeholder="Search documents..." id="searchInput">
            </div>

            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table id="documentsTable">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
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
                                $status_text = 'Valid';
                                if ($days_remaining < 0) {
                                    $status_class = 'status-expired';
                                    $status_text = 'Expired';
                                } elseif ($days_remaining <= 30) {
                                    $status_class = 'status-warning';
                                    $status_text = 'Expiring Soon';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['document_name']); ?></td>
                                    <td><?php echo $row['mfg_date'] ? date('M d, Y', strtotime($row['mfg_date'])) : 'N/A'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    <td><?php echo $days_remaining >= 0 ? $days_remaining . ' days' : 'Expired ' . abs($days_remaining) . ' days ago'; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this document?')">
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
                        <div class="empty-state-icon">📄</div>
                        <h3>No documents added yet</h3>
                        <p>Add your first document to start tracking expiry dates</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Email Status Notification -->
    <div id="emailStatus" class="email-status"></div>

    <script>
        // EmailJS Configuration - UPDATE THESE VALUES AFTER SETUP
        const EMAILJS_CONFIG = {
            publicKey: 'G_IjTZHAAG__n2Uow',      // Replace with your EmailJS public key
            serviceId: 'service_dn1xwrk',      // Replace with your EmailJS service ID
            templateId: 'template_o4hhgyu'     // Replace with your EmailJS template ID
        };

        // Initialize EmailJS
        function initEmailJS() {
            if (EMAILJS_CONFIG.publicKey !== 'YOUR_PUBLIC_KEY_HERE') {
                emailjs.init(EMAILJS_CONFIG.publicKey);
                document.getElementById('emailjs-status').textContent = 'Configured ✅';
                document.getElementById('emailjs-setup').style.background = '#dcfce7';
                document.getElementById('emailjs-setup').style.borderColor = '#16a34a';
                document.querySelector('#emailjs-setup h4').style.color = '#16a34a';
                document.querySelector('#emailjs-setup h4').innerHTML = '✅ EmailJS Ready';
                return true;
            } else {
                document.getElementById('emailjs-status').textContent = 'Not configured ❌';
                return false;
            }
        }

        // Test EmailJS Configuration
        function testEmailJS() {
            if (!initEmailJS()) {
                showEmailStatus('Please configure EmailJS first!', 'error');
                return;
            }

            showEmailStatus('Testing EmailJS configuration...', 'success');

            const testParams = {
                user_name: '<?php echo isset($_SESSION['name']) ? $_SESSION['name'] : "Test User"; ?>',
                user_email: '<?php echo $_SESSION['email']; ?>',
                subject: '🧪 EmailJS Test - Document Manager',
                message: 'This is a test email to verify your EmailJS configuration is working correctly.',
                document_name: 'Test Document',
                issue_date: 'Not specified',
                expiry_date: new Date().toLocaleDateString(),
                days_until_expiry: '30'
            };

            emailjs.send(EMAILJS_CONFIG.serviceId, EMAILJS_CONFIG.templateId, testParams)
                .then(function(response) {
                    console.log('Test email sent successfully:', response);
                    showEmailStatus('✅ Test email sent successfully! Check your Gmail.', 'success');
                })
                .catch(function(error) {
                    console.error('Test email failed:', error);
                    showEmailStatus('❌ Test email failed: ' + error.text, 'error');
                });
        }

        // Send document added notification
        function sendDocumentNotification(documentData) {
            if (!initEmailJS()) {
                console.log('EmailJS not configured, skipping email');
                return;
            }

            const emailParams = {
                user_name: documentData.user_name,
                user_email: documentData.user_email,
                subject: `📄 Document Added Successfully - ${documentData.document_name}`,
                message: `Your document "${documentData.document_name}" has been successfully added to your Document Manager. We'll keep track of its expiry date for you!`,
                document_name: documentData.document_name,
                issue_date: documentData.issue_date,
                expiry_date: documentData.expiry_date,
                days_until_expiry: documentData.days_until_expiry
            };

            emailjs.send(EMAILJS_CONFIG.serviceId, EMAILJS_CONFIG.templateId, emailParams)
                .then(function(response) {
                    console.log('Document notification sent successfully:', response);
                    showEmailStatus('📧 Email notification sent successfully!', 'success');
                })
                .catch(function(error) {
                    console.error('Email notification failed:', error);
                    showEmailStatus('❌ Email notification failed: ' + error.text, 'error');
                });
        }

        // Show email status notification
        function showEmailStatus(message, type) {
            const statusDiv = document.getElementById('emailStatus');
            statusDiv.textContent = message;
            statusDiv.className = `email-status ${type} show`;
            
            setTimeout(() => {
                statusDiv.classList.remove('show');
            }, 5000);
        }

        // Check for new documents and send notifications
        function checkForNewDocuments() {
            // This would typically check the email_queue.json file
            // For now, we'll trigger email on form submission
        }

        // Form submission handler
        document.getElementById('documentForm').addEventListener('submit', function(e) {
            const button = this.querySelector('.btn-primary');
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Adding document...';
            button.style.opacity = '0.7';
            button.disabled = true;
            
            // Reset button after 3 seconds if form doesn't submit
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.opacity = '1';
                button.disabled = false;
            }, 3000);
        });

        // Check if document was just added and send email
        <?php if ($success_message && strpos($success_message, 'Document added successfully') !== false): ?>
        setTimeout(() => {
            const documentData = {
                user_name: '<?php echo isset($_SESSION['name']) ? $_SESSION['name'] : "User"; ?>',
                user_email: '<?php echo $_SESSION['email']; ?>',
                document_name: '<?php echo isset($document_name) ? $document_name : ""; ?>',
                issue_date: '<?php echo isset($mfg_date) && $mfg_date ? date('F j, Y', strtotime($mfg_date)) : "Not specified"; ?>',
                expiry_date: '<?php echo isset($expiry_date) ? date('F j, Y', strtotime($expiry_date)) : ""; ?>',
                days_until_expiry: '<?php echo isset($expiry_date) ? ceil((strtotime($expiry_date) - time()) / (60 * 60 * 24)) : ""; ?>'
            };
            
            if (documentData.document_name) {
                sendDocumentNotification(documentData);
            }
        }, 1000);
        <?php endif; ?>

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initEmailJS();
            
            // Set minimum date to today for expiry date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('expiry_date').min = today;
            document.getElementById('mfg_date').max = today;

            // Validate dates
            document.getElementById('mfg_date').addEventListener('change', function() {
                const mfgDate = this.value;
                const expiryInput = document.getElementById('expiry_date');
                
                if (mfgDate) {
                    expiryInput.min = mfgDate;
                } else {
                    expiryInput.min = today;
                }
            });

            // Search functionality
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const table = document.getElementById('documentsTable');
                if (!table) return;
                
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const documentName = rows[i].getElementsByTagName('td')[0];
                    if (documentName) {
                        const textValue = documentName.textContent || documentName.innerText;
                        if (textValue.toLowerCase().indexOf(searchTerm) > -1) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
