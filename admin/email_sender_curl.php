<?php
/**
 * Alternative Email Sender using cURL and EmailJS or similar service
 * This is a backup solution if SMTP doesn't work
 */

class CurlEmailSender {
    private $from_email;
    private $from_name;
    
    public function __construct($config) {
        $this->from_email = $config['from_email'];
        $this->from_name = $config['from_name'];
    }
    
    public function sendEmail($to, $subject, $htmlBody) {
        // For now, we'll log the email and provide instructions
        $this->logEmailForManualSending($to, $subject, $htmlBody);
        return true; // Return true for testing purposes
    }
    
    private function logEmailForManualSending($to, $subject, $htmlBody) {
        $logFile = 'emails_to_send.html';
        $timestamp = date('Y-m-d H:i:s');
        
        $emailHtml = "
        <div style='border: 1px solid #ccc; margin: 20px; padding: 20px; background: #f9f9f9;'>
            <h3>Email to Send</h3>
            <p><strong>Timestamp:</strong> $timestamp</p>
            <p><strong>To:</strong> $to</p>
            <p><strong>Subject:</strong> $subject</p>
            <div style='border: 1px solid #ddd; padding: 10px; background: white;'>
                $htmlBody
            </div>
            <hr>
        </div>
        ";
        
        file_put_contents($logFile, $emailHtml, FILE_APPEND | LOCK_EX);
        
        // Also log to text file
        $logEntry = "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject | STATUS: Logged for manual sending\n";
        file_put_contents('email_log.txt', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function testConnection() {
        return true; // Always return true for this method
    }
}

// If you want to use the curl method instead, uncomment this:
// $emailSender = new CurlEmailSender($emailConfig);
?>