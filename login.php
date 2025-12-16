<?php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['email'])) {
    header("Location: index.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $db->escape($_POST["email"]);
    $password = $_POST["password"];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Use prepared statement for security
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify password (using password_verify if passwords are hashed)
            if (password_verify($password, $user["password"]) || $password === $user["password"]) {
                // Set session variables
                $_SESSION["email"] = $email;
                $_SESSION["username"] = $user["username"];
                $_SESSION["user_id"] = $user["id"];

                // Update last login time
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user["id"]);
                $update_stmt->execute();
                $update_stmt->close();

                // Redirect to dashboard
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Incorrect password";
            }
        } else {
            $error_message = "User not found";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Expiry Alert</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            position: relative;
        }

        .login-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .login-header::before {
            content: '🔐';
            font-size: 3rem;
            position: absolute;
            top: 20px;
            left: 30px;
            opacity: 0.3;
        }

        .login-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .login-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .form-container {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            transform: translateY(-2px);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: #7c3aed;
            text-decoration: underline;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .features-list {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .features-list h4 {
            color: #374151;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .feature-item:last-child {
            margin-bottom: 0;
        }

        .feature-icon {
            font-size: 1.2rem;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #4f46e5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }

            .login-header {
                padding: 30px 20px;
            }

            .login-header h2 {
                font-size: 2rem;
            }

            .form-container {
                padding: 30px 20px;
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
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

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.2rem;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #4f46e5;
        }

        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #4f46e5;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: #7c3aed;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="login-container fade-in">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Sign in to your Expiry Alert account</p>
        </div>

        <div class="form-container">
            <?php if ($error_message): ?>
                <div class="error-message">
                    <span>❌</span>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <!-- <div class="features-list">
                <h4>✨ What you can do with Expiry Alert</h4>
                <div class="feature-item">
                    <span class="feature-icon">📄</span>
                    <span>Track document expiry dates</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">💊</span>
                    <span>Monitor medicine expiration</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🍱</span>
                    <span>Manage food freshness dates</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">💄</span>
                    <span>Keep track of cosmetics</span>
                </div>
            </div> -->

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <span class="input-icon">📧</span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>

                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot password?</a>
                </div>

                <button type="submit" class="login-btn">
                    Sign In
                </button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create one here</a>
            </div>
        </div>

        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner"></div>
        </div>
    </div>

    <script>
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const button = this.querySelector('.login-btn');
            const overlay = document.getElementById('loadingOverlay');

            // Validate form
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                return;
            }

            button.innerHTML = '⏳ Signing In...';
            button.style.opacity = '0.7';
            overlay.style.display = 'flex';
        });

        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Input validation and styling
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('blur', function () {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#ef4444';
                } else if (this.type === 'email' && !isValidEmail(this.value)) {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#10b981';
                }
            });

            input.addEventListener('focus', function () {
                this.style.borderColor = '#4f46e5';
            });
        });

        // Email validation
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('email').focus();
        });

        // Enter key navigation
        document.getElementById('email').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
                e.preventDefault();
            }
        });

        // Shake animation for errors
        <?php if ($error_message): ?>
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.querySelector('.login-container');
                container.style.animation = 'shake 0.5s ease-in-out';
            });
        <?php endif; ?>

        // Add shake keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>