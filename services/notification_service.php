<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

class NotificationService
{
    private $db;
    private $emailService;

    public function __construct()
    {
        $this->db = new Database();
        $this->emailService = new EmailService();
    }

    public function sendItemAddedNotification($user_email, $item_name, $category, $expiry_date)
    {
        // Get user details
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            return $this->emailService->sendItemAddedNotification(
                $user_email,
                $user['username'],
                $item_name,
                $category,
                $expiry_date
            );
        }

        return false;
    }

    public function sendPasswordResetNotification($user_email, $username, $reset_link)
    {
        $subject = "Password Reset Request - Expiry Alert";
        $body = "
            <html>
            <head>
                <style>
                    .container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333; }
                    .button { background-color: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
                    .footer { margin-top: 30px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Password Reset Request</h2>
                    <p>Hello {$username},</p>
                    <p>We received a request to reset your password for Expiry Alert. If you did not make this request, please ignore this email.</p>
                    <p>To reset your password, click the button below (valid for 1 hour):</p>
                    <p><a href='{$reset_link}' class='button'>Reset Password</a></p>
                    <p>Or copy this link to your browser:</p>
                    <p>{$reset_link}</p>
                    <div class='footer'>
                        <p>Expiry Alert System</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        return $this->emailService->sendEmail($user_email, $subject, $body);
    }

    // ... rest of your existing methods remain the same

    public function checkAndSendExpiryReminders()
    {
        // Time check removed to allow external scheduling (cron) to control execution time
        // $current_time = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        // $current_hour = (int)$current_time->format('H');
        // if ($current_hour !== 15) { ... }

        $results = [];

        // Send 1-hour reminders
        $results['1_hour'] = $this->sendRemindersByType('1_hour');

        // Send 1-day reminders
        $results['1_day'] = $this->sendRemindersByType('1_day');

        // Send 7-day reminders
        $results['7_days'] = $this->sendRemindersByType('7_days');

        return $results;
    }

    private function sendRemindersByType($reminder_type)
    {
        $users_with_expiring_items = $this->getUsersWithExpiringItems($reminder_type);
        $sent_count = 0;

        foreach ($users_with_expiring_items as $user_email => $user_data) {
            if (
                $this->emailService->sendExpiryReminder(
                    $user_email,
                    $user_data['username'],
                    $user_data['items'],
                    $reminder_type
                )
            ) {
                $sent_count++;
                $this->logReminderSent($user_email, $reminder_type, count($user_data['items']));
            }
        }

        return [
            'type' => $reminder_type,
            'users_notified' => $sent_count,
            'total_items' => array_sum(array_map(function ($user) {
                return count($user['items']); }, $users_with_expiring_items))
        ];
    }

    private function getUsersWithExpiringItems($reminder_type)
    {
        $conn = $this->db->getConnection();
        $users_items = [];

        // Define time ranges for different reminder types
        $time_conditions = $this->getTimeConditions($reminder_type);

        // Get all categories and their expiring items
        $categories = [
            'documents' => 'Documents',
            'medicines' => 'Medicine',
            'foods' => 'Food',
            'books' => 'Book',
            'cosmetics' => 'Cosmetic',
            'other_items' => 'Other'
        ];

        foreach ($categories as $table => $category_name) {
            $name_column = $this->getNameColumn($table);

            $query = "
                SELECT u.email, u.username, i.{$name_column} as name, i.expiry_date
                FROM {$table} i
                JOIN users u ON i.email = u.email
                WHERE u.account_status = 'active' 
                AND i.expiry_date {$time_conditions}
                ORDER BY i.expiry_date ASC
            ";

            $result = $conn->query($query);

            while ($row = $result->fetch_assoc()) {
                $email = $row['email'];

                if (!isset($users_items[$email])) {
                    $users_items[$email] = [
                        'username' => $row['username'],
                        'items' => []
                    ];
                }

                $users_items[$email]['items'][] = [
                    'name' => $row['name'],
                    'category' => $category_name,
                    'expiry_date' => $row['expiry_date']
                ];
            }
        }

        return $users_items;
    }

    private function getTimeConditions($reminder_type)
    {
        $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));

        switch ($reminder_type) {
            case '1_hour':
                // Items expiring in the next 1-2 hours
                $start = $now->format('Y-m-d H:i:s');
                $end = (clone $now)->add(new DateInterval('PT2H'))->format('Y-m-d H:i:s');
                return "BETWEEN '{$start}' AND '{$end}'";

            case '1_day':
                // Items expiring tomorrow (next 24-48 hours)
                $start = (clone $now)->add(new DateInterval('PT24H'))->format('Y-m-d H:i:s');
                $end = (clone $now)->add(new DateInterval('PT48H'))->format('Y-m-d H:i:s');
                return "BETWEEN '{$start}' AND '{$end}'";

            case '7_days':
                // Items expiring in 7 days (6-8 days from now)
                $start = (clone $now)->add(new DateInterval('P6D'))->format('Y-m-d H:i:s');
                $end = (clone $now)->add(new DateInterval('P8D'))->format('Y-m-d H:i:s');
                return "BETWEEN '{$start}' AND '{$end}'";

            default:
                return "< NOW()";
        }
    }

    private function getNameColumn($table)
    {
        $name_columns = [
            'documents' => 'document_name',
            'medicines' => 'medicine_name',
            'foods' => 'food_name',
            'books' => 'book_name',
            'cosmetics' => 'cosmetic_name',
            'other_items' => 'item_name'
        ];

        return $name_columns[$table] ?? 'name';
    }

    private function logReminderSent($user_email, $reminder_type, $item_count)
    {
        $conn = $this->db->getConnection();

        // Create notifications log table if it doesn't exist
        $create_table = "
            CREATE TABLE IF NOT EXISTS notification_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_email VARCHAR(100) NOT NULL,
                reminder_type ENUM('1_hour', '1_day', '7_days', 'item_added') NOT NULL,
                item_count INT DEFAULT 1,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_email (user_email),
                INDEX idx_sent_at (sent_at)
            )
        ";
        $conn->query($create_table);

        // Log the reminder
        $stmt = $conn->prepare("INSERT INTO notification_logs (user_email, reminder_type, item_count) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $user_email, $reminder_type, $item_count);
        $stmt->execute();
        $stmt->close();
    }

    public function getNotificationStats()
    {
        $conn = $this->db->getConnection();

        $stats_query = "
            SELECT 
                reminder_type,
                COUNT(*) as total_sent,
                SUM(item_count) as total_items,
                DATE(sent_at) as date_sent
            FROM notification_logs 
            WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY reminder_type, DATE(sent_at)
            ORDER BY date_sent DESC, reminder_type
        ";

        $result = $conn->query($stats_query);
        $stats = [];

        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }

        return $stats;
    }
}
?>