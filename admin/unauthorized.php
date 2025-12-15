<?php
require_once 'config/admin_auth.php';
$auth = new AdminAuth();

// Check if logged in
$isLoggedIn = $auth->isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - Expiry Date Reminder</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .error-container {
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #e74a3b;
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <div class="error-message">Unauthorized Access</div>
        <p class="lead mb-4">You do not have permission to access this page.</p>
        <?php if ($isLoggedIn): ?>
            <a href="dashboard.php" class="btn btn-primary mr-2">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary mr-2">Login</a>
            <a href="../index.php" class="btn btn-secondary">Return to Main Site</a>
        <?php endif; ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
