<?php
// reset_password.php
require_once 'config/database.php';

// Enable Error Reporting for Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid request.");
}

$token_hash = hash('sha256', $token);

$db = new Database();
$conn = $db->getConnection();

// Verify Token
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
if (!$stmt) {
    die("Database error (prepare failed): " . $conn->error);
}
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "This password reset link is invalid or has expired.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$error) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $user = $result->fetch_assoc();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update Password and Clear Token
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user['id']);

        if ($update->execute()) {
            $success = "Password has been reset successfully! <a href='login.php'>Login here</a>";
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Set New Password</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Set New Password</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php elseif ($result->num_rows > 0): ?>
            <form method="POST">
                <label>New Password</label>
                <input type="password" name="password" required>

                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>

                <button type="submit">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>