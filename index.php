<?php
session_start();
if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get user information
$user_email = $db->escape($_SESSION['email']);
$user_stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
$user_stmt->bind_param("s", $user_email);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$username = $user_data['username'] ?? 'User';
$user_stmt->close();

// Get statistics for the user
$stats = [];

// Total items tracked
$total_query = "
    SELECT 
        (SELECT COUNT(*) FROM documents WHERE email = ?) +
        (SELECT COUNT(*) FROM medicines WHERE email = ?) +
        (SELECT COUNT(*) FROM foods WHERE email = ?) +
        (SELECT COUNT(*) FROM books WHERE email = ?) +
        (SELECT COUNT(*) FROM cosmetics WHERE email = ?) +
        (SELECT COUNT(*) FROM other_items WHERE email = ?) as total_items
";
$total_stmt = $conn->prepare($total_query);
$total_stmt->bind_param("ssssss", $user_email, $user_email, $user_email, $user_email, $user_email, $user_email);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$stats['total_items'] = $total_result->fetch_assoc()['total_items'];
$total_stmt->close();

// Items expiring soon (within 7 days)
$expiring_soon_query = "
    SELECT COUNT(*) as expiring_soon FROM (
        SELECT expiry_date FROM documents WHERE email = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        UNION ALL
        SELECT expiry_date FROM medicines WHERE email = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        UNION ALL
        SELECT expiry_date FROM foods WHERE email = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        UNION ALL
        SELECT expiry_date FROM books WHERE email = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        UNION ALL
        SELECT expiry_date FROM cosmetics WHERE email = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        UNION ALL
        SELECT expiry_date FROM other_items WHERE email = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ) as all_items
";
$expiring_stmt = $conn->prepare($expiring_soon_query);
$expiring_stmt->bind_param("ssssss", $user_email, $user_email, $user_email, $user_email, $user_email, $user_email);
$expiring_stmt->execute();
$expiring_result = $expiring_stmt->get_result();
$stats['expiring_soon'] = $expiring_result->fetch_assoc()['expiring_soon'];
$expiring_stmt->close();

// Expired items
$expired_query = "
    SELECT COUNT(*) as expired FROM (
        SELECT expiry_date FROM documents WHERE email = ? AND expiry_date < CURDATE()
        UNION ALL
        SELECT expiry_date FROM medicines WHERE email = ? AND expiry_date < CURDATE()
        UNION ALL
        SELECT expiry_date FROM foods WHERE email = ? AND expiry_date < CURDATE()
        UNION ALL
        SELECT expiry_date FROM books WHERE email = ? AND expiry_date < CURDATE()
        UNION ALL
        SELECT expiry_date FROM cosmetics WHERE email = ? AND expiry_date < CURDATE()
        UNION ALL
        SELECT expiry_date FROM other_items WHERE email = ? AND expiry_date < CURDATE()
    ) as all_items
";
$expired_stmt = $conn->prepare($expired_query);
$expired_stmt->bind_param("ssssss", $user_email, $user_email, $user_email, $user_email, $user_email, $user_email);
$expired_stmt->execute();
$expired_result = $expired_stmt->get_result();
$stats['expired'] = $expired_result->fetch_assoc()['expired'];
$expired_stmt->close();

// Get recent activity (last 5 items added)
$recent_query = "
    SELECT 'Document' as type, document_name as name, created_at FROM documents WHERE email = ?
    UNION ALL
    SELECT 'Medicine' as type, medicine_name as name, created_at FROM medicines WHERE email = ?
    UNION ALL
    SELECT 'Food' as type, food_name as name, created_at FROM foods WHERE email = ?
    UNION ALL
    SELECT 'Book' as type, book_name as name, created_at FROM books WHERE email = ?
    UNION ALL
    SELECT 'Cosmetic' as type, cosmetic_name as name, created_at FROM cosmetics WHERE email = ?
    UNION ALL
    SELECT 'Other' as type, item_name as name, created_at FROM other_items WHERE email = ?
    ORDER BY created_at DESC LIMIT 5
";
$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->bind_param("ssssss", $user_email, $user_email, $user_email, $user_email, $user_email, $user_email);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_items = [];
while ($row = $recent_result->fetch_assoc()) {
    $recent_items[] = $row;
}
$recent_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expiry Alert - Dashboard</title>
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
            color: #333;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #374151;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .menu {
            position: relative;
        }

        .menu-btn {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .menu-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        .dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            min-width: 200px;
            overflow: hidden;
        }

        .dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #374151;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .dropdown a:hover {
            background: #f8fafc;
            color: #4f46e5;
            padding-left: 25px;
        }

        .dropdown a:last-child {
            border-bottom: none;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 60px;
            color: white;
        }

        .welcome-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .welcome-section p {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .stats-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            text-align: center;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .container-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px 30px;
            text-decoration: none;
            color: #374151;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            background: white;
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .card-description {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.5;
        }

        .recent-activity {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .recent-activity h3 {
            color: #374151;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: #e2e8f0;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .activity-details h4 {
            color: #374151;
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .activity-details p {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .footer {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .footer-section h3 {
            color: #374151;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 8px 0;
        }

        .footer-links a:hover {
            color: #4f46e5;
            padding-left: 10px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .contact-info a {
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .contact-info a:hover {
            color: #4f46e5;
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 0 15px;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .welcome-section h1 {
                font-size: 2.5rem;
            }
            
            .welcome-section p {
                font-size: 1.1rem;
            }
            
            .container-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .main-content {
                padding: 40px 15px;
            }

            .user-info span {
                display: none;
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        .slide-up {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
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
    </style>
</head>
<body>
    <header class="fade-in">
        <div class="header-content">
            <div class="logo">🔔 Expiry Alert</div>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <span>Welcome, <?php echo htmlspecialchars($username); ?>!</span>
            </div>
            <div class="menu">
                <button class="menu-btn">⋮</button>
                <div class="dropdown">
                    <a href="about.php">📋 About</a>
                    <a href="contact.php">📞 Contact</a>
                    <a href="feedback.php">💬 Feedback</a>
                    <a href="logout.php">🚪 Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="welcome-section slide-up">
            <h1>Welcome Back, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Keep track of all your important expiry dates in one place. Never miss an important deadline again.</p>
        </div>

        <div class="stats-section slide-up">
            <h2>Your Dashboard Overview</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">6</span>
                    <span class="stat-label">Categories</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['total_items']; ?></span>
                    <span class="stat-label">Items Tracked</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['expiring_soon']; ?></span>
                    <span class="stat-label">Expiring Soon</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['expired']; ?></span>
                    <span class="stat-label">Expired</span>
                </div>
            </div>
        </div>

        <?php if (!empty($recent_items)): ?>
        <div class="recent-activity slide-up">
            <h3>Recent Activity</h3>
            <?php foreach ($recent_items as $item): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php 
                        $icons = [
                            'Document' => '📄',
                            'Medicine' => '💊',
                            'Food' => '🍱',
                            'Book' => '📚',
                            'Cosmetic' => '💄',
                            'Other' => '📦'
                        ];
                        echo $icons[$item['type']] ?? '📦';
                        ?>
                    </div>
                    <div class="activity-details">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p><?php echo $item['type']; ?> • Added <?php echo date('M d, Y', strtotime($item['created_at'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="container-grid">
            <a href="document.php" class="card slide-up">
                <span class="card-icon">📄</span>
                <div class="card-title">Documents</div>
                <div class="card-description">Track passports, licenses, certificates and other important documents</div>
            </a>
            
            <a href="medicine.php" class="card slide-up">
                <span class="card-icon">💊</span>
                <div class="card-title">Medicine</div>
                <div class="card-description">Monitor medication expiry dates and never use expired medicines</div>
            </a>
            
            <a href="food.php" class="card slide-up">
                <span class="card-icon">🍱</span>
                <div class="card-title">Food Items</div>
                <div class="card-description">Keep track of food expiration dates to avoid waste and health risks</div>
            </a>
            
            <a href="books.php" class="card slide-up">
                <span class="card-icon">📚</span>
                <div class="card-title">Books & Media</div>
                <div class="card-description">Track library books, subscriptions and rental return dates</div>
            </a>
            
            <a href="cosmetics.php" class="card slide-up">
                <span class="card-icon">💄</span>
                <div class="card-title">Cosmetics</div>
                <div class="card-description">Monitor beauty products and cosmetics expiration dates</div>
            </a>
            
            <a href="other.php" class="card slide-up">
                <span class="card-icon">📦</span>
                <div class="card-title">Other Items</div>
                <div class="card-description">Track any other items with expiration or renewal dates</div>
            </a>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Quick Links</h3>
                <div class="footer-links">
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact</a>
                    <a href="feedback.php">Feedback</a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Contact Information</h3>
                <div class="contact-info">
                    <a href="mailto:xyz@gmail.com">📧 xyz@gmail.com</a><br>
                    <a href="tel:+919376532092">📞 +91 9376532092</a><br>
                    <a href="tel:+919874537277">📞 +91 9874537277</a><br>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>About Expiry Alert</h3>
                <p style="color: #6b7280; line-height: 1.6;">Your personal assistant for tracking expiry dates and important deadlines. Stay organized and never miss important dates again.</p>
            </div>
        </div>
    </footer>

    <script>
        // Menu functionality
        const menuBtn = document.querySelector(".menu-btn");
        const dropdown = document.querySelector(".dropdown");

        menuBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdown.classList.toggle("show");
        });

        // Close dropdown when clicking outside
        window.addEventListener("click", (event) => {
            if (!event.target.closest('.menu')) {
                dropdown.classList.remove('show');
            }
        });

        // Add stagger animation to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });

        // Add hover sound effect (optional)
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation
        window.addEventListener('load', () => {
            document.body.style.opacity = '1';
        });
    </script>
</body>
</html>
