<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$message_sent = false;
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $db->escape($_POST['name']);
    $email = $db->escape($_POST['email']);
    $subject = $db->escape($_POST['subject']);
    $message = $db->escape($_POST['message']);
    $user_email = isset($_SESSION['email']) ? $db->escape($_SESSION['email']) : null;
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please provide a valid email address.";
    } else {
        // Determine priority based on subject keywords
        $priority = 'medium';
        $subject_lower = strtolower($subject);
        if (strpos($subject_lower, 'urgent') !== false || strpos($subject_lower, 'bug') !== false || strpos($subject_lower, 'error') !== false) {
            $priority = 'high';
        } elseif (strpos($subject_lower, 'question') !== false || strpos($subject_lower, 'help') !== false) {
            $priority = 'medium';
        } else {
            $priority = 'low';
        }
        
        // Insert contact message into database
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, user_email, priority) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $subject, $message, $user_email, $priority);
        
        if ($stmt->execute()) {
            $message_sent = true;
            // Clear form data on success
            $name = $email = $subject = $message = '';
        } else {
            $error_message = "Error sending message. Please try again.";
        }
        $stmt->close();
    }
}

// Get contact statistics
$stats_query = "SELECT 
    COUNT(*) as total_messages,
    COUNT(CASE WHEN status = 'replied' THEN 1 END) as replied_messages,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as recent_messages
    FROM contact_messages";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Calculate response rate
$response_rate = $stats['total_messages'] > 0 ? round(($stats['replied_messages'] / $stats['total_messages']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Expiry Alert</title>
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

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .contact-info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-section h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            transform: translateY(-2px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .btn {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .contact-details h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 5px;
        }

        .contact-details p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .contact-details a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }

        .contact-details a:hover {
            text-decoration: underline;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .back-button:hover {
            background: #4b5563;
            transform: translateY(-2px);
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
        }

        .faq-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .faq-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 20px 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            font-weight: 700;
            color: #374151;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .faq-answer {
            color: #6b7280;
            line-height: 1.6;
        }

        .contact-stats {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
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
            color: #4f46e5;
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-form, .contact-info {
                padding: 25px;
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
            <h1>Contact Us</h1>
            <p>We'd love to hear from you! Get in touch with any questions, suggestions, or feedback.</p>
        </div>

        <div class="contact-stats fade-in">
            <h2>📊 Our Support Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['total_messages']; ?></span>
                    <span class="stat-label">Messages Received</span>
                </div>
                <!-- <div class="stat-item">
                    <span class="stat-number"><?php echo $response_rate; ?>%</span>
                    <span class="stat-label">Response Rate</span>
                </div> -->
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['recent_messages']; ?></span>
                    <span class="stat-label">Recent Messages</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24h</span>
                    <span class="stat-label">Avg Response Time</span>
                </div>
            </div>
        </div>

        <div class="contact-grid">
            <div class="contact-form fade-in">
                <h2>📝 Send us a Message</h2>
                
                <?php if ($message_sent): ?>
                    <div class="success-message">
                        <span>✅</span>
                        <span>Thank you for your message! We'll get back to you soon.</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <span>❌</span>
                        <span><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="contactForm">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : (isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Tell us how we can help you..." required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Send Message</button>
                </form>
            </div>

            <div class="contact-info fade-in">
                <h2>📞 Get in Touch</h2>
                
                <div class="contact-item">
                    <div class="contact-icon">📧</div>
                    <div class="contact-details">
                        <h4>Email Us</h4>
                        <p><a href="mailto:xyz@gmail.com">xyz@gmail.com</a></p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">📱</div>
                    <div class="contact-details">
                        <h4>Call Us</h4>
                        <p><a href="tel:+919376532092">+91 9376532092</a></p>
                        <p><a href="tel:+919874537277">+91 9874537277</a></p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">🕒</div>
                    <div class="contact-details">
                        <h4>Response Time</h4>
                        <p>We typically respond within 24 hours</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">🌍</div>
                    <div class="contact-details">
                        <h4>Support Hours</h4>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM IST</p>
                        <p>Weekend: Limited support available</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="faq-section fade-in">
            <h2>❓ Frequently Asked Questions</h2>
            
            <div class="faq-item">
                <div class="faq-question">How do I reset my password?</div>
                <div class="faq-answer">You can reset your password by clicking the "Forgot Password" link on the login page and following the instructions sent to your email.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Is Expiry Alert free to use?</div>
                <div class="faq-answer">Yes! Expiry Alert is completely free to use. We believe everyone should have access to tools that help them stay organized.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">How do I delete my account?</div>
                <div class="faq-answer">If you wish to delete your account, please contact us directly and we'll help you with the process while ensuring your data is properly removed.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Can I export my data?</div>
                <div class="faq-answer">Currently, data export is not available through the interface, but you can contact us for assistance with exporting your data.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Do you offer mobile notifications?</div>
                <div class="faq-answer">We're working on mobile notifications and email alerts for upcoming expiry dates. This feature will be available soon!</div>
            </div>
        </div>

        <div style="text-align: center;">
            <?php if (isset($_SESSION['email'])): ?>
                <a href="index.php" class="back-button">🏠 Back to Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="back-button">🔐 Login</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Form submission animation
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            const button = this.querySelector('.btn');
            button.innerHTML = '⏳ Sending...';
            button.style.opacity = '0.7';
        });

        // Add stagger animation to contact items
        const contactItems = document.querySelectorAll('.contact-item');
        contactItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('fade-in');
        });

        // Form validation
        const form = document.getElementById('contactForm');
        const inputs = form.querySelectorAll('input, textarea');

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#10b981';
                }
            });
        });

        // Character counter for message textarea
        const messageTextarea = document.getElementById('message');
        const charCounter = document.createElement('div');
        charCounter.style.cssText = 'text-align: right; font-size: 0.8rem; color: #6b7280; margin-top: 5px;';
        messageTextarea.parentNode.appendChild(charCounter);

        messageTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCounter.textContent = `${length}/500 characters`;
            
            if (length > 500) {
                charCounter.style.color = '#ef4444';
            } else {
                charCounter.style.color = '#6b7280';
            }
        });

        // Trigger initial count
        messageTextarea.dispatchEvent(new Event('input'));

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
