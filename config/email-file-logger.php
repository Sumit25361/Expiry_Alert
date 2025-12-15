<?php
define('EMAIL_LOG_DIR', __DIR__ . '/../logs/emails/');

class EmailService {
    public function __construct() {
        // Create email log directory if it doesn't exist
        if (!file_exists(EMAIL_LOG_DIR)) {
            mkdir(EMAIL_LOG_DIR, 0777, true);
        }
    }
    
    public function sendItemAddedNotification($user_email, $username, $item_name, $category, $expiry_date) {
        $subject = "Item Added Successfully - {$item_name}";
        $expiry_formatted = date('F j, Y g:i A', strtotime($expiry_date));
        
        $message = "
            To: {$user_email}
            Subject: {$subject}
            
            Hello {$username},
            
            Your item has been successfully added to your EDR system.
            
            Item Details:
            Name: {$item_name}
            Category: {$category}
            Expiry Date: {$expiry_formatted}
            
            We'll send you reminders before this item expires.
            
            Thank you for using our EDR system!
        ";
        
        return $this->logEmail($user_email, $subject, $message);
    }
    
    public function sendExpiryReminder($user_email, $username, $items, $reminder_type) {
        $reminder_titles = [
            '1_hour' => 'Items Expiring in 1 Hour!',
            '1_day' => 'Items Expiring Tomorrow!',
            '7_days' => 'Items Expiring in 7 Days'
        ];
        
        $subject = $reminder_titles[$reminder_type] ?? 'Expiry Reminder';
        
        $items_text = '';
        foreach ($items as $item) {
            $expiry_formatted = date('F j, Y g:i A', strtotime($item['expiry_date']));
            $items_text .= "- {$item['name']} ({$item['category']}) - Expires: {$expiry_formatted}\n";
        }
        
        $message = "
            To: {$user_email}
            Subject: {$subject}
            
            Hello {$username},
            
            The following items are about to expire:
            
            {$items_text}
            
            Please check these items and take appropriate action.
            
            Thank you for using our EDR system!
        ";
        
        return $this->logEmail($user_email, $subject, $message);
    }
    
    private function logEmail($to_email, $subject, $message) {
        try {
            $filename = EMAIL_LOG_DIR . 'email_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.txt';
            $log_content = "Timestamp: " . date('Y-m-d H:i:s') . "\n";
            $log_content .= "To: {$to_email}\n";
            $log_content .= "Subject: {$subject}\n";
            $log_content .= str_repeat('-', 50) . "\n";
            $log_content .= $message . "\n";
            $log_content .= str_repeat('=', 50) . "\n\n";
            
            file_put_contents($filename, $log_content);
            
            // Also log to main log file
            error_log("Email logged to file: {$filename} for {$to_email}");
            
            return true;
        } catch (Exception $e) {
            error_log("Email logging error: " . $e->getMessage());
            return false;
        }
    }
}
?>
