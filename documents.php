<?php
// In your document form processing section, replace the existing notification code:

if ($stmt->execute()) {
    $success_message = "Document added successfully!";

    // Send email notification with issue date included
    require_once 'config/email.php';
    $emailService = new EmailService();
    
    // Get user name (you might want to store this in session or database)
    $user_name = "User"; // Replace with actual user name from database/session
    
    $emailService->sendDocumentAddedNotification(
        $email,           // user email
        $user_name,       // user name  
        $document_name,   // document name
        $mfg_date,        // issue date (can be null)
        $expiry_date      // expiry date
    );

} else {
    $error_message = "Error adding document.";
}
?>
