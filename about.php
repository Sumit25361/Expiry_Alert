<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Expiry Alert</title>
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

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .header p {
            font-size: 1.2rem;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .content-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .content-section h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .content-section p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4b5563;
            margin-bottom: 20px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .feature-card {
            background: #f8fafc;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: #4f46e5;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.6;
        }

        .team-section {
            text-align: center;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .team-member {
            background: #f8fafc;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .team-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 20px;
        }

        .team-member h4 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 10px;
        }

        .team-member p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            text-decoration: none;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 30px;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .stats-section {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .content-section {
                padding: 25px;
            }
            
            .features-grid, .team-grid {
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
    <div class="container">
        <div class="header fade-in">
            <h1>About Expiry Alert</h1>
            <p>Your trusted companion for managing expiry dates and important deadlines</p>
        </div>

        <div class="content-section fade-in">
            <h2>🎯 Our Mission</h2>
            <p>
                At Expiry Alert, we believe that staying organized shouldn't be complicated. Our mission is to help individuals and families keep track of important expiry dates, from documents and medications to food items and cosmetics, ensuring you never miss a critical deadline again.
            </p>
            <p>
                We understand how overwhelming it can be to remember all the different expiry dates in your life. That's why we created a simple, intuitive platform that centralizes all your important dates in one place, giving you peace of mind and helping you stay on top of your responsibilities.
            </p>
        </div>

        <!-- <div class="content-section stats-section fade-in">
            <h2>📊 Why Choose Expiry Alert?</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">6</span>
                    <span class="stat-label">Categories Covered</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Free to Use</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Access Anywhere</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">∞</span>
                    <span class="stat-label">Items to Track</span>
                </div>
            </div>
        </div> -->

        <div class="content-section fade-in">
            <h2>✨ Key Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">📄</span>
                    <h3>Document Tracking</h3>
                    <p>Keep track of passports, licenses, certificates, and other important documents with expiry dates.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">💊</span>
                    <h3>Medicine Management</h3>
                    <p>Monitor medication expiry dates to ensure you never use expired medicines that could be harmful.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🍱</span>
                    <h3>Food Safety</h3>
                    <p>Track food expiration dates to reduce waste and avoid consuming expired products.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">📚</span>
                    <h3>Library & Rentals</h3>
                    <p>Never pay late fees again by tracking library books, movie rentals, and subscription renewals.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">💄</span>
                    <h3>Beauty Products</h3>
                    <p>Monitor cosmetics and beauty products to ensure you're using fresh, safe products.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🔔</span>
                    <h3>Smart Alerts</h3>
                    <p>Get timely notifications before items expire, so you can take action in advance.</p>
                </div>
            </div>
        </div>

        <!-- <div class="content-section fade-in">
            <h2>🚀 Our Story</h2>
            <p>
                Expiry Alert was born out of a simple frustration - forgetting important expiry dates and facing the consequences. Whether it was an expired passport during travel planning, expired medication in the medicine cabinet, or spoiled food in the refrigerator, we realized that many people struggle with the same challenges.
            </p>
            <p>
                Our team of developers and designers came together to create a solution that would be simple enough for anyone to use, yet powerful enough to handle all types of expiry tracking needs. We focused on creating an intuitive interface that makes adding and managing expiry dates as easy as possible.
            </p>
            <p>
                Today, Expiry Alert serves users who want to stay organized, reduce waste, save money, and most importantly, ensure their safety by never using expired products.
            </p>
        </div> -->

        <!-- <div class="content-section team-section fade-in">
            <h2>👥 Our Values</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="team-avatar">🎯</div>
                    <h4>Simplicity</h4>
                    <p>We believe in keeping things simple and user-friendly, making organization accessible to everyone.</p>
                </div>
                
                <div class="team-member">
                    <div class="team-avatar">🔒</div>
                    <h4>Privacy</h4>
                    <p>Your data is yours. We prioritize user privacy and data security in everything we do.</p>
                </div>
                
                <div class="team-member">
                    <div class="team-avatar">💡</div>
                    <h4>Innovation</h4>
                    <p>We continuously improve our platform based on user feedback and emerging needs.</p>
                </div>
                
                <div class="team-member">
                    <div class="team-avatar">🤝</div>
                    <h4>Support</h4>
                    <p>We're here to help you succeed in staying organized and never missing important dates.</p>
                </div>
            </div>
        </div> -->

        <div class="content-section fade-in">
            <h2>🌟 Get Started Today</h2>
            <p>
                Ready to take control of your expiry dates? Join thousands of users who have already simplified their lives with Expiry Alert. It's completely free to use, and you can start tracking your first items in just minutes.
            </p>
            <p>
                Whether you're a busy professional, a parent managing a household, or someone who simply wants to stay more organized, Expiry Alert is designed to fit seamlessly into your life.
            </p>
            
            <?php if (isset($_SESSION['email'])): ?>
                <a href="index.php" class="back-button">🏠 Back to Dashboard</a>
            <?php else: ?>
                <a href="register.php" class="back-button">🚀 Get Started Now</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add stagger animation to sections
        const sections = document.querySelectorAll('.content-section');
        sections.forEach((section, index) => {
            section.style.animationDelay = `${index * 0.2}s`;
        });

        // Add hover effects to feature cards
        const featureCards = document.querySelectorAll('.feature-card');
        featureCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px) scale(1.02)';
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
    </script>
</body>
</html>
