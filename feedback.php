<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$feedback_sent = false;
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $db->escape($_POST['name']);
    $email = $db->escape($_POST['email']);
    $rating = (int)$_POST['rating'];
    $category = $db->escape($_POST['category']);
    $feedback = $db->escape($_POST['feedback']);
    $suggestions = $db->escape($_POST['suggestions']);
    $user_email = isset($_SESSION['email']) ? $db->escape($_SESSION['email']) : null;
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($feedback) || $rating < 1 || $rating > 5 || empty($category)) {
        $error_message = "Please fill in all required fields and provide a valid rating.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please provide a valid email address.";
    } else {
        // Insert feedback into database
        $stmt = $conn->prepare("INSERT INTO feedback (name, email, rating, category, feedback, suggestions, user_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissss", $name, $email, $rating, $category, $feedback, $suggestions, $user_email);
        
        if ($stmt->execute()) {
            $feedback_sent = true;
            // Clear form data on success
            $name = $email = $feedback = $suggestions = '';
            $rating = 0;
            $category = '';
        } else {
            $error_message = "Error submitting feedback. Please try again.";
        }
        $stmt->close();
    }
}

// Get feedback statistics
$stats_query = "SELECT 
    COUNT(*) as total_feedback,
    AVG(rating) as avg_rating,
    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
    COUNT(CASE WHEN rating >= 4 THEN 1 END) as four_plus_star
    FROM feedback";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get category distribution
$category_query = "SELECT category, COUNT(*) as count FROM feedback GROUP BY category ORDER BY count DESC";
$category_result = $conn->query($category_query);
$categories = [];
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Expiry Alert</title>
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
            max-width: 800px;
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

        .feedback-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-section h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 25px;
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

        .form-group input,
        .form-group select,
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
        .form-group select:focus,
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

        .rating-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .star-rating {
            display: flex;
            gap: 5px;
        }

        .star {
            font-size: 2rem;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .rating-text {
            margin-left: 15px;
            font-weight: 600;
            color: #374151;
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
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.1rem;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.1rem;
        }

        .feedback-stats {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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

        .feedback-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .category-card {
            background: #f8fafc;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-5px);
            border-color: #4f46e5;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.15);
        }

        .category-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .category-card h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 10px;
        }

        .category-card p {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .feedback-form {
                padding: 25px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .star {
                font-size: 1.5rem;
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
            <h1>Your Feedback Matters</h1>
            <p>Help us improve Expiry Alert by sharing your thoughts, suggestions, and experiences with us.</p>
        </div>

        <?php if ($feedback_sent): ?>
            <div class="success-message fade-in">
                <span style="font-size: 2rem;">🎉</span>
                <div>
                    <strong>Thank you for your feedback!</strong><br>
                    Your input helps us make Expiry Alert better for everyone. We truly appreciate you taking the time to share your thoughts with us.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message fade-in">
                <span style="font-size: 1.5rem;">❌</span>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <div class="feedback-form fade-in">
            <h2>💬 Share Your Experience</h2>
            
            <form method="POST" id="feedbackForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : (isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Overall Rating</label>
                    <div class="rating-group">
                        <div class="star-rating">
                            <span class="star" data-rating="1">⭐</span>
                            <span class="star" data-rating="2">⭐</span>
                            <span class="star" data-rating="3">⭐</span>
                            <span class="star" data-rating="4">⭐</span>
                            <span class="star" data-rating="5">⭐</span>
                        </div>
                        <span class="rating-text" id="ratingText">Click to rate</span>
                    </div>
                    <input type="hidden" id="rating" name="rating" value="<?php echo isset($rating) ? $rating : '0'; ?>">
                </div>

                <div class="form-group">
                    <label for="category">Feedback Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="user-interface" <?php echo (isset($category) && $category == 'user-interface') ? 'selected' : ''; ?>>User Interface</option>
                        <option value="functionality" <?php echo (isset($category) && $category == 'functionality') ? 'selected' : ''; ?>>Functionality</option>
                        <option value="performance" <?php echo (isset($category) && $category == 'performance') ? 'selected' : ''; ?>>Performance</option>
                        <option value="bug-report" <?php echo (isset($category) && $category == 'bug-report') ? 'selected' : ''; ?>>Bug Report</option>
                        <option value="feature-request" <?php echo (isset($category) && $category == 'feature-request') ? 'selected' : ''; ?>>Feature Request</option>
                        <option value="general" <?php echo (isset($category) && $category == 'general') ? 'selected' : ''; ?>>General Feedback</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="feedback">Your Feedback</label>
                    <textarea id="feedback" name="feedback" placeholder="Tell us about your experience with Expiry Alert..." required><?php echo isset($feedback) ? htmlspecialchars($feedback) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="suggestions">Suggestions for Improvement (Optional)</label>
                    <textarea id="suggestions" name="suggestions" placeholder="What features would you like to see? How can we make Expiry Alert better?"><?php echo isset($suggestions) ? htmlspecialchars($suggestions) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn">Submit Feedback</button>
            </form>
        </div>

        <div class="feedback-stats fade-in">
            <h2>📊 Community Feedback</h2>
            <p>See how our community is helping us improve</p>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['total_feedback']; ?>+</span>
                    <span class="stat-label">Feedback Received</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['avg_rating'], 1); ?></span>
                    <span class="stat-label">Average Rating</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['five_star']; ?></span>
                    <span class="stat-label">5-Star Reviews</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo round(($stats['four_plus_star'] / max($stats['total_feedback'], 1)) * 100); ?>%</span>
                    <span class="stat-label">User Satisfaction</span>
                </div>
            </div>

            <div class="feedback-categories">
                <div class="category-card">
                    <span class="category-icon">🎨</span>
                    <h4>UI/UX Feedback</h4>
                    <p>Help us improve the user interface and experience</p>
                </div>
                
                <div class="category-card">
                    <span class="category-icon">🔧</span>
                    <h4>Feature Requests</h4>
                    <p>Suggest new features that would be helpful</p>
                </div>
                
                <div class="category-card">
                    <span class="category-icon">🐛</span>
                    <h4>Bug Reports</h4>
                    <p>Report any issues or bugs you encounter</p>
                </div>
                
                <div class="category-card">
                    <span class="category-icon">⚡</span>
                    <h4>Performance</h4>
                    <p>Share feedback about app speed and performance</p>
                </div>
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
        // Star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');
        const ratingText = document.getElementById('ratingText');
        
        const ratingLabels = {
            1: 'Poor',
            2: 'Fair', 
            3: 'Good',
            4: 'Very Good',
            5: 'Excellent'
        };

        // Set initial rating if exists
        const initialRating = parseInt(ratingInput.value);
        if (initialRating > 0) {
            updateStars(initialRating);
            ratingText.textContent = ratingLabels[initialRating];
        }

        function updateStars(rating) {
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        }

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                ratingText.textContent = ratingLabels[rating];
                updateStars(rating);
            });

            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#fbbf24';
                        s.style.transform = 'scale(1.1)';
                    } else {
                        s.style.color = '#d1d5db';
                        s.style.transform = 'scale(1)';
                    }
                });
            });
        });

        document.querySelector('.star-rating').addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value);
            updateStars(currentRating);
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.style.color = '#fbbf24';
                    s.style.transform = 'scale(1.1)';
                } else {
                    s.style.color = '#d1d5db';
                    s.style.transform = 'scale(1)';
                }
            });
        });

        // Form submission animation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const rating = parseInt(ratingInput.value);
            if (rating === 0) {
                e.preventDefault();
                alert('Please provide a rating before submitting.');
                return;
            }
            
            const button = this.querySelector('.btn');
            button.innerHTML = '⏳ Submitting...';
            button.style.opacity = '0.7';
        });

        // Character counter for textareas
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            const charCounter = document.createElement('div');
            charCounter.style.cssText = 'text-align: right; font-size: 0.8rem; color: #6b7280; margin-top: 5px;';
            textarea.parentNode.appendChild(charCounter);

            textarea.addEventListener('input', function() {
                const length = this.value.length;
                const maxLength = this.id === 'feedback' ? 500 : 300;
                charCounter.textContent = `${length}/${maxLength} characters`;
                
                if (length > maxLength) {
                    charCounter.style.color = '#ef4444';
                } else {
                    charCounter.style.color = '#6b7280';
                }
            });

            // Trigger initial count
            textarea.dispatchEvent(new Event('input'));
        });

        // Form validation
        const form = document.getElementById('feedbackForm');
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && this.value.trim() === '') {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#10b981';
                }
            });
        });

        // Add stagger animation to category cards
        const categoryCards = document.querySelectorAll('.category-card');
        categoryCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in');
        });
    </script>
</body>
</html>
