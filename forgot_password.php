<?php
// forgot_password.php
session_start();
require_once 'config/database.php';
require_once 'services/notification_service.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();

    $email = $db->escape($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Generate Token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expiry = date('Y-m-d H:i:s', time() + 60 * 60); // 1 hour expiry

        // Save to DB
        $update = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?");
        $update->bind_param("ssi", $token_hash, $expiry, $user['id']);

        if ($update->execute()) {
            // Send Email
            $notificationService = new NotificationService();
            // Use a new method we will add, or generic logic
            // For now, let's try to add the method to NotificationService, 
            // but if that fails, we might need a fallback.
            // Let's assume we will add sendPasswordResetNotification next.

            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

            if ($notificationService->sendPasswordResetNotification($email, $user['username'], $reset_link)) {
                $message = "We have sent a password reset link to your email.";
            } else {
                $error = "Failed to send email. Please try again later.";
            }
        } else {
            $error = "Database error. Please try again.";
        }
    } else {
        // Security: Don't reveal if email exists or not, but for UX usually we say "If that email exists..."
        // or just say sent.
        $message = "If an account exists with that email, we have sent a reset link.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Expiry Alert</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #666;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #4338ca;
        }

        .message {
            color: green;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Enter your email address</label>
                <input type="email" name="email" required placeholder="e.g., user@example.com">
            </div>
            <button type="submit">Send Reset Link</button>
        </form>

        <a href="login.php" class="back-link">Back to Login</a>
    </div>
</body>

</html>