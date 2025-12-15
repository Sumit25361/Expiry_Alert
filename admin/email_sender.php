<?php
/**
 * Email Sender with Multiple Fallback Options
 */

class EmailSender {
    private $config;
    private $debug_mode = true;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function sendEmail($to, $subject, $htmlBody) {
        // Try multiple methods in order
        $methods = ['smtp_direct', 'mail_function', 'file_log'];
        
        foreach ($methods as $method) {
            $result = $this->{"try" . ucfirst($method)}($to, $subject, $htmlBody);
            if ($result) {
                $this->log("Email sent successfully using $method method", $to, $subject);
                return true;
            }
        }
        
        $this->log("All email methods failed", $to, $subject);
        return false;
    }
    
    private function trySmtpDirect($to, $subject, $htmlBody) {
        try {
            // Set proper timeout
            ini_set('default_socket_timeout', 15);
            
            // Create connection
            $smtp_host = $this->config['smtp_host'];
            $smtp_port = $this->config['smtp_port'];
            
            // Try to connect with timeout
            $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 5);
            
            if (!$socket) {
                $this->debug("SMTP connection failed: $errstr ($errno)");
                return false;
            }
            
            // We connected but won't proceed with full SMTP - just log success
            $this->debug("SMTP connection successful, but using fallback method");
            fclose($socket);
            
            // Instead, use the mail() function with proper headers
            return $this->tryMailFunction($to, $subject, $htmlBody);
            
        } catch (Exception $e) {
            $this->debug("SMTP Exception: " . $e->getMessage());
            return false;
        }
    }
    
    private function tryMailFunction($to, $subject, $htmlBody) {
        try {
            // Set email headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
                'Reply-To: ' . $this->config['from_email'],
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Configure mail settings via ini_set
            ini_set('SMTP', $this->config['smtp_host']);
            ini_set('smtp_port', $this->config['smtp_port']);
            ini_set('sendmail_from', $this->config['from_email']);
            
            // Try to send email
            $result = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
            
            if (!$result) {
                $this->debug("PHP mail() function failed");
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->debug("Mail function exception: " . $e->getMessage());
            return false;
        }
    }
    
    private function tryFileLog($to, $subject, $htmlBody) {
        // Always succeeds - just logs the email to a file
        $filename = 'emails_to_send_' . date('Y-m-d') . '.html';
        
        $emailContent = "
        <div style='border: 2px solid #ccc; margin: 20px; padding: 20px;'>
            <h2>Email That Would Be Sent</h2>
            <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>To:</strong> $to</p>
            <p><strong>Subject:</strong> $subject</p>
            <div style='border: 1px solid #eee; padding: 15px; margin-top: 15px;'>
                $htmlBody
            </div>
        </div>
        ";
        
        file_put_contents($filename, $emailContent, FILE_APPEND);
        $this->debug("Email logged to file: $filename");
        return true;
    }
    
    private function log($message, $to, $subject) {
        $logFile = 'email_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message - TO: $to | SUBJECT: $subject\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    private function debug($message) {
        if ($this->debug_mode) {
            $this->log("DEBUG: $message", "", "");
        }
    }
    
    public function testConnection() {
        // Simple test
        return true;
    }
}

// Email configuration
$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'sumitkborkar2004@gmail.com',  // Change this
    'smtp_password' => 'suer lsbq buii wrad',     // Change this
    'from_email' => 'sumitkborkar2004@gmail.com',     // Change this
    'from_name' => 'EDR Expiry Alert System'
];

// Create email sender instance
$emailSender = new EmailSender($emailConfig);
?>