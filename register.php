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
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $db->escape($_POST["username"]);
    $phone = $db->escape($_POST["phone"]);
    $email = $db->escape($_POST["email"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    }
    // Validate phone number
    elseif (!preg_match("/^[987][0-9]{9}$/", $phone)) {
        $error_message = "Invalid phone number format";
    }
    // Check password match
    elseif ($password !== $confirm) {
        $error_message = "Passwords do not match";
    }
    // Validate password strength
    elseif (strlen($password) < 10 || !preg_match("/[A-Z]/", $password) || !strpos($password, '@')) {
        $error_message = "Password must be at least 10 characters, contain a capital letter and @ symbol";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Email already registered";
        } else {
            // Hash password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_stmt = $conn->prepare("INSERT INTO users (username, phone, email, password) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $username, $phone, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success_message = "Registration successful! You can now login.";
                
                // Clear form data on success
                $username = $phone = $email = '';
            } else {
                $error_message = "Error registering user: " . $conn->error;
            }
            $insert_stmt->close();
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
    <title>Register - Expiry Alert</title>
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

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .register-header {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .register-header::before {
            content: '🚀';
            font-size: 3rem;
            position: absolute;
            top: 20px;
            left: 30px;
            opacity: 0.3;
        }

        .register-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .register-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .form-container {
            padding: 40px 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
            transform: translateY(-2px);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #059669, #10b981);
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

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
        }

        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-link {
            text-align: center;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #059669;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: #047857;
            text-decoration: underline;
        }

        .success-message {
            background: #dcfce7;
            color: #16a34a;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
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

        .password-requirements {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }

        .password-requirements h4 {
            color: #0369a1;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            color: #6b7280;
        }

        .requirement.valid {
            color: #16a34a;
        }

        .requirement-icon {
            font-size: 0.8rem;
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
            color: #059669;
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
            border-top: 4px solid #059669;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 600px) {
            .register-container {
                margin: 10px;
            }
            
            .register-header {
                padding: 30px 20px;
            }
            
            .register-header h2 {
                font-size: 2rem;
            }
            
            .form-container {
                padding: 30px 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
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
    </style>
</head>
<body>
    <div class="register-container fade-in">
        <div class="register-header">
            <h2>Join Expiry Alert</h2>
            <p>Create your account to start tracking expiry dates</p>
        </div>

        <div class="form-container">
            <?php if ($success_message): ?>
                <div class="success-message">
                    <span>✅</span>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <span>❌</span>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Full Name</label>
                        <input type="text" name="username" id="username" placeholder="Enter your full name" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                        <span class="input-icon">👤</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" name="phone" id="phone" pattern="[987][0-9]{9}" placeholder="Enter phone number" required value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                        <span class="input-icon">📱</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email address" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    <span class="input-icon">📧</span>
                </div>

                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <div class="requirement" id="req-length">
                        <span class="requirement-icon">❌</span>
                        <span>At least 10 characters long</span>
                    </div>
                    <div class="requirement" id="req-uppercase">
                        <span class="requirement-icon">❌</span>
                        <span>Contains at least one uppercase letter</span>
                    </div>
                    <div class="requirement" id="req-special">
                        <span class="requirement-icon">❌</span>
                        <span>Contains @ symbol</span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" 
                               pattern="(?=.*[A-Z])(?=.*[@]).{10,}" 
                               title="Must contain @, a capital letter and be at least 10 characters" 
                               placeholder="Create a strong password" required>
                        <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm">Confirm Password</label>
                        <input type="password" name="confirm" id="confirm" placeholder="Confirm your password" required>
                        <span class="password-toggle" onclick="togglePassword('confirm')" style="right: 15px;">👁️</span>
                    </div>
                </div>

                <button type="submit" class="register-btn" id="submitBtn">
                    Create Account
                </button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>

        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner"></div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Password validation
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm');
        const submitBtn = document.getElementById('submitBtn');

        function validatePassword() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            // Check length
            const lengthReq = document.getElementById('req-length');
            if (lengthReq) {
                if (password.length >= 10) {
                    lengthReq.classList.add('valid');
                    lengthReq.querySelector('.requirement-icon').textContent = '✅';
                } else {
                    lengthReq.classList.remove('valid');
                    lengthReq.querySelector('.requirement-icon').textContent = '❌';
                }
            }
            
            // Check uppercase
            const uppercaseReq = document.getElementById('req-uppercase');
            if (uppercaseReq) {
                if (/[A-Z]/.test(password)) {
                    uppercaseReq.classList.add('valid');
                    uppercaseReq.querySelector('.requirement-icon').textContent = '✅';
                } else {
                    uppercaseReq.classList.remove('valid');
                    uppercaseReq.querySelector('.requirement-icon').textContent = '❌';
                }
            }
            
            // Check @ symbol
            const specialReq = document.getElementById('req-special');
            if (specialReq) {
                if (password.includes('@')) {
                    specialReq.classList.add('valid');
                    specialReq.querySelector('.requirement-icon').textContent = '✅';
                } else {
                    specialReq.classList.remove('valid');
                    specialReq.querySelector('.requirement-icon').textContent = '❌';
                }
            }
            
            // Check if all requirements are met and passwords match
            const allValid = password.length >= 10 && /[A-Z]/.test(password) && password.includes('@');
            const passwordsMatch = password === confirm && confirm !== '';
            
            // ENABLE THE BUTTON - This is the key fix
            if (allValid && passwordsMatch) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                // Don't disable the button completely, just change appearance
                submitBtn.disabled = false; // Keep it enabled
                submitBtn.style.opacity = '0.8';
                submitBtn.style.cursor = 'pointer';
            }
            
            // Password match indicator
            if (confirm !== '') {
                if (passwordsMatch) {
                    confirmInput.style.borderColor = '#10b981';
                } else {
                    confirmInput.style.borderColor = '#ef4444';
                }
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // ENABLE THE BUTTON BY DEFAULT - This is the main fix
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
            
            // Set up event listeners
            passwordInput.addEventListener('input', validatePassword);
            confirmInput.addEventListener('input', validatePassword);
            
            // Also enable button when any field changes
            const allInputs = document.querySelectorAll('input');
            allInputs.forEach(input => {
                input.addEventListener('input', function() {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                });
            });
        });

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const button = this.querySelector('.register-btn');
            const overlay = document.getElementById('loadingOverlay');
            
            // Don't prevent submission - just show loading
            button.innerHTML = '⏳ Creating Account...';
            button.style.opacity = '0.7';
            if (overlay) {
                overlay.style.display = 'flex';
            }
            
            // Let the form submit normally
            return true;
        });

        // Input validation and styling
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '' && this.hasAttribute('required')) {
                    this.style.borderColor = '#ef4444';
                } else if (this.type === 'email' && !isValidEmail(this.value)) {
                    this.style.borderColor = '#ef4444';
                } else if (this.type === 'tel' && !isValidPhone(this.value)) {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#10b981';
                }
            });

            input.addEventListener('focus', function() {
                this.style.borderColor = '#059669';
            });
        });

        // Validation functions
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function isValidPhone(phone) {
            const phoneRegex = /^[987][0-9]{9}$/;
            return phoneRegex.test(phone);
        }

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            this.value = value;
        });

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Shake animation for errors
        <?php if ($error_message): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.register-container');
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