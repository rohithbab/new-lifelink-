<?php
session_start();
require_once 'connection.php';
require_once 'queries.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['recipient_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Validate status
$allowed_statuses = ['Pending', 'Accepted', 'Rejected'];
if (!in_array($data['status'], $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Begin transaction
    $conn->beginTransaction();

    // Get recipient details for notification
    $stmt = $conn->prepare("SELECT full_name FROM recipient_registration WHERE id = ?");
    $stmt->execute([$data['recipient_id']]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update recipient status
    $stmt = $conn->prepare("UPDATE recipient_registration SET request_status = ? WHERE id = ?");
    $result = $stmt->execute([$data['status'], $data['recipient_id']]);

    if ($result) {
        // Create notification based on status
        $action = strtolower($data['status']);
        $message = "Recipient " . $recipient['full_name'] . " has been " . $action;
        
        createNotification(
            $conn,
            'recipient',
            $action,
            $data['recipient_id'],
            $message
        );

        // Commit transaction
        $conn->commit();
        
        // Log the update for debugging
        error_log("Successfully updated recipient status. Recipient ID: " . $data['recipient_id'] . ", New Status: " . $data['status']);
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        $conn->rollback();
        error_log("Failed to update recipient status. Recipient ID: " . $data['recipient_id']);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    error_log("Error in update_recipient_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
