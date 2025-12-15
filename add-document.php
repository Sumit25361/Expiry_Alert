<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require_once 'config/database.php';
require_once 'services/notification_service.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $db = new Database();
    $conn = $db->getConnection();

    $document_name = $db->escape($_POST['document_name']);
    $mfg_date = !empty($_POST['mfg_date']) ? $_POST['mfg_date'] : null;
    $expiry_date = $_POST['expiry_date'];
    $email = $db->escape($_SESSION['email']);

    // Validate dates
    $today = date('Y-m-d');
    if ($expiry_date <= $today) {
        echo json_encode(['success' => false, 'error' => 'Expiry date must be in the future']);
        exit;
    }

    if ($mfg_date && $mfg_date >= $expiry_date) {
        echo json_encode(['success' => false, 'error' => 'Issue date must be before expiry date']);
        exit;
    }

    // Insert document
    $stmt = $conn->prepare("INSERT INTO documents (email, document_name, mfg_date, expiry_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $document_name, $mfg_date, $expiry_date);

    if ($stmt->execute()) {
        // Send email notification
        try {
            $notificationService = new NotificationService();
            // User name fetching is now handled inside NotificationService

            $email_sent = $notificationService->sendItemAddedNotification(
                $email,
                $document_name,
                'Document', // Category
                $expiry_date
            );

            $response = [
                'success' => true,
                'message' => 'Document added successfully!',
                'email_sent' => $email_sent,
                'document' => [
                    'name' => $document_name,
                    'issue_date' => $mfg_date,
                    'expiry_date' => $expiry_date
                ]
            ];

            if ($email_sent) {
                $response['message'] .= ' Email notification sent. 📧';
            } else {
                $response['message'] .= ' (Email notification failed)';
            }

            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                'success' => true,
                'message' => 'Document added successfully! (Email error)',
                'email_sent' => false,
                'email_error' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add document']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>