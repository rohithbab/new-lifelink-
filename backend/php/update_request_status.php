<?php
session_start();
require_once 'connection.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if all required fields are present
if (!isset($_POST['request_id']) || !isset($_POST['action']) || !isset($_POST['type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$request_id = $_POST['request_id'];
$action = $_POST['action'];
$type = $_POST['type'];
$reason = isset($_POST['reason']) ? $_POST['reason'] : '';
$hospital_id = $_SESSION['hospital_id'];

try {
    // Start transaction
    $conn->beginTransaction();

    // Determine which table to update based on request type
    $table = $type === 'donor' ? 'donor_requests' : 'recipient_requests';
    
    // Update request status
    $query = "UPDATE $table SET 
              status = :status,
              rejection_reason = :reason,
              updated_at = NOW()
              WHERE id = :request_id 
              AND hospital_id = :hospital_id";
              
    $stmt = $conn->prepare($query);
    $status = $action === 'approve' ? 'approved' : 'rejected';
    
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->bindParam(':hospital_id', $hospital_id);
    
    $stmt->execute();

    // If no rows were updated, the request doesn't exist or doesn't belong to this hospital
    if ($stmt->rowCount() === 0) {
        throw new Exception('Request not found or unauthorized');
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Request status updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
