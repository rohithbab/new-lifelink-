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
$hospital_id = $_SESSION['hospital_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get recipient details
    $query = "SELECT r.full_name, r.blood_type, r.organ_required 
              FROM recipient_registration r 
              WHERE r.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $recipient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipient = $result->fetch_assoc();

    if (!$recipient) {
        throw new Exception('Recipient not found');
    }

    // Update approval status
    $query = "UPDATE hospital_recipient_approvals 
              SET status = 'approved', 
                  approval_date = CURRENT_TIMESTAMP 
              WHERE recipient_id = ? AND hospital_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $recipient_id, $hospital_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No matching recipient approval request found');
    }

    // Create notification with recipient details
    $message = "New recipient {$recipient['full_name']} (Blood Type: {$recipient['blood_type']}) needs {$recipient['organ_required']} transplant";
    $query = "INSERT INTO hospital_notifications (hospital_id, type, message, related_id, is_read, created_at, link_url) 
              VALUES (?, 'recipient_registration', ?, ?, 0, NOW(), 'hospitals_handles_recipients_status.php')";
    
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
