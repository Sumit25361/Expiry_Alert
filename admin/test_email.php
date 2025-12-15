<?php
// Test email script
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing Email Functionality</h2>";

// Method 1: Direct mail() function
$to = "your-test-email@gmail.com";
$subject = "Test Email from EDR";
$message = "<html><body><h1>Test Email</h1><p>This is a test email from EDR system.</p></body></html>";
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: EDR System <your-email@gmail.com>\r\n";

echo "<p>Attempting to send email using mail() function...</p>";
$result = mail($to, $subject, $message, $headers);
echo $result ? "<p style='color:green'>Mail sent successfully!</p>" : "<p style='color:red'>Mail failed to send.</p>";

// Method 2: File logging fallback
echo "<p>Logging email to file as fallback...</p>";
$filename = 'test_email_' . date('Y-m-d_H-i-s') . '.html';
file_put_contents($filename, $message);
echo "<p>Email content logged to $filename</p>";
?>