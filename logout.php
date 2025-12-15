<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Expiry Alert</title>
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

        .logout-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .logout-icon {
            font-size: 4rem;
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease-out;
        }

        .logout-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #374151;
            margin-bottom: 20px;
            animation: slideUp 0.8s ease-out 0.2s both;
        }

        .logout-message {
            font-size: 1.2rem;
            color: #6b7280;
            margin-bottom: 40px;
            line-height: 1.6;
            animation: slideUp 0.8s ease-out 0.4s both;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: slideUp 0.8s ease-out 0.6s both;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .logout-container {
                padding: 40px 20px;
            }
            
            .logout-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">👋</div>
        <h1 class="logout-title">Successfully Logged Out</h1>
        <p class="logout-message">
            Thank you for using Expiry Alert! Your session has been securely ended. 
            We hope to see you again soon to help you stay organized with your expiry dates.
        </p>
        <div class="action-buttons">
            <a href="login.php" class="btn btn-primary">
                🔐 Login Again
            </a>
            <a href="register.php" class="btn btn-secondary">
                🚀 Create New Account
            </a>
        </div>
    </div>

    <script>
        // Auto redirect after 10 seconds
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 10000);

        // Show countdown
        let countdown = 10;
        const countdownElement = document.createElement('div');
        countdownElement.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            animation: fadeIn 0.5s ease-out;
        `;
        document.body.appendChild(countdownElement);

        const updateCountdown = () => {
            countdownElement.textContent = `Redirecting to login in ${countdown}s`;
            countdown--;
            if (countdown < 0) {
                clearInterval(countdownInterval);
            }
        };

        updateCountdown();
        const countdownInterval = setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
