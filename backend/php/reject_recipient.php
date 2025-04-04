<?php
session_start();
require_once 'connection.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$recipient_id = $data['recipient_id'];
$reason = $data['reason'];
$hospital_id = $_SESSION['hospital_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Update approval status
    $query = "UPDATE hospital_recipient_approvals 
              SET status = 'rejected', 
                  rejection_reason = ?,
                  approval_date = CURRENT_TIMESTAMP 
              WHERE recipient_id = ? AND hospital_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sii', $reason, $recipient_id, $hospital_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No matching recipient approval request found');
    }

    // Create notification
    $message = "Your recipient registration has been rejected. Reason: " . $reason;
    $query = "INSERT INTO hospital_notifications (hospital_id, type, message, related_id) 
              VALUES (?, 'recipient_rejection', ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isi', $hospital_id, $message, $recipient_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
