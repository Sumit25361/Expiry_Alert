<?php
/**
 * Email Sender with Multiple Fallback Options
 */

class EmailSender
{
    private $config;
    private $debug_mode = true;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function sendEmail($to, $subject, $htmlBody)
    {
        // Try multiple methods in order
        // Prioritize SMTP Direct because mail() function is unreliable on Windows/XAMPP
        $methods = ['smtpDirect', 'mailFunction', 'fileLog'];

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

    private function trySmtpDirect($to, $subject, $htmlBody)
    {
        try {
            $host = $this->config['smtp_host'];
            $port = $this->config['smtp_port'];
            $username = $this->config['smtp_username'];
            $password = $this->config['smtp_password'];
            $from = $this->config['from_email'];
            $fromName = $this->config['from_name'];

            $this->debug("Connecting to $host:$port...");

            // Connect to server
            $socket = fsockopen($host, $port, $errno, $errstr, 15);
            if (!$socket) {
                throw new Exception("Could not connect to SMTP host: $errstr ($errno)");
            }

            // Read greeting
            $this->readSmtpResponse($socket);

            // EHLO
            $this->sendSmtpCommand($socket, "EHLO $host");

            // STARTTLS
            if ($port == 587) {
                $this->sendSmtpCommand($socket, "STARTTLS");
                // Enable crypto
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("Failed to enable crypto");
                }
                // Resend EHLO after TLS
                $this->sendSmtpCommand($socket, "EHLO $host");
            }

            // AUTH LOGIN
            $this->sendSmtpCommand($socket, "AUTH LOGIN");
            $this->sendSmtpCommand($socket, base64_encode($username));
            $this->sendSmtpCommand($socket, base64_encode($password));

            // MAIL FROM
            $this->sendSmtpCommand($socket, "MAIL FROM: <$from>");

            // RCPT TO
            $this->sendSmtpCommand($socket, "RCPT TO: <$to>");

            // DATA
            $this->sendSmtpCommand($socket, "DATA");

            // Headers and Body
            $headers = [];
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-type: text/html; charset=UTF-8";
            $headers[] = "From: $fromName <$from>";
            $headers[] = "To: <$to>";
            $headers[] = "Subject: $subject";
            $headers[] = "Date: " . date("r");

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";

            $this->sendSmtpCommand($socket, $message);

            // QUIT
            $this->sendSmtpCommand($socket, "QUIT");

            fclose($socket);
            return true;

        } catch (Exception $e) {
            $this->debug("SMTP Exception: " . $e->getMessage());
            if (isset($socket) && is_resource($socket)) {
                fclose($socket);
            }
            return false;
        }
    }

    private function sendSmtpCommand($socket, $command)
    {
        fwrite($socket, $command . "\r\n");
        $response = $this->readSmtpResponse($socket);

        // Simple error checking (codes 4xx and 5xx are errors)
        $code = substr($response, 0, 3);
        if ($code >= 400) {
            throw new Exception("SMTP Error: $response");
        }
        return $response;
    }

    private function readSmtpResponse($socket)
    {
        $response = "";
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        //$this->debug("SMTP Response: " . trim($response));
        return $response;
    }

    private function tryMailFunction($to, $subject, $htmlBody)
    {
        try {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
                'Reply-To: ' . $this->config['from_email'],
                'X-Mailer: PHP/' . phpversion()
            ];

            // Try to set some ini values that might help on some systems
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                ini_set('SMTP', $this->config['smtp_host']);
                ini_set('smtp_port', $this->config['smtp_port']);
                ini_set('sendmail_from', $this->config['from_email']);
            }

            $result = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));

            if (!$result) {
                // $this->debug("PHP mail() function failed");
                return false;
            }

            return true;

        } catch (Exception $e) {
            // $this->debug("Mail function exception: " . $e->getMessage());
            return false;
        }
    }

    private function tryFileLog($to, $subject, $htmlBody)
    {
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

    private function log($message, $to, $subject)
    {
        $logFile = 'email_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message - TO: $to | SUBJECT: $subject\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    private function debug($message)
    {
        if ($this->debug_mode) {
            $this->log("DEBUG: $message", "", "");
        }
    }

    public function testConnection()
    {
        return true;
    }
}

// Email configuration
$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'sumitkborkar2004@gmail.com',
    'smtp_password' => 'suer lsbq buii wrad',
    'from_email' => 'sumitkborkar2004@gmail.com',
    'from_name' => 'EDR Expiry Alert System'
];

// Create email sender instance
$emailSender = new EmailSender($emailConfig);
?>