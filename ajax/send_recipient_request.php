<?php
session_start();
require_once '../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$recipient_id = $data['recipient_id'] ?? 0;
$recipient_hospital_id = $data['recipient_hospital_id'] ?? 0;
$requesting_hospital_id = $_SESSION['hospital_id'];

if (!$recipient_id || !$recipient_hospital_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid recipient or hospital ID']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Get recipient and hospital information
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.blood_type,
            ha.required_organ,
            ha.hospital_id as recipient_hospital_id
        FROM recipient_registration r
        JOIN hospital_recipient_approvals ha ON r.id = ha.recipient_id
        WHERE r.id = ? AND ha.status = 'Approved'
    ");
    $stmt->execute([$recipient_id]);
    $recipientInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipientInfo) {
        throw new Exception('Recipient not found or not approved');
    }

    // Check if recipient hospital is the same as requesting hospital
    if ($recipientInfo['recipient_hospital_id'] == $requesting_hospital_id) {
        throw new Exception('Cannot request your own recipient');
    }

    // Check if request already exists
    $stmt = $conn->prepare("
        SELECT status 
        FROM recipient_requests 
        WHERE recipient_id = ? AND requesting_hospital_id = ? 
        AND status IN ('Pending', 'Approved')
    ");
    $stmt->execute([$recipient_id, $requesting_hospital_id]);
    if ($stmt->fetch()) {
        throw new Exception('A request already exists for this recipient');
    }

    // Check if recipient already has an approved request
    $stmt = $conn->prepare("
        SELECT status 
        FROM recipient_requests 
        WHERE recipient_id = ? AND status = 'Approved'
    ");
    $stmt->execute([$recipient_id]);
    if ($stmt->fetch()) {
        throw new Exception('This recipient has already been approved for another hospital');
    }

    // Insert new request
    $stmt = $conn->prepare("
        INSERT INTO recipient_requests (
            recipient_id, 
            requesting_hospital_id, 
            recipient_hospital_id,
            request_date,
            status
        ) VALUES (?, ?, ?, NOW(), 'Pending')
    ");
    $stmt->execute([
        $recipient_id,
        $requesting_hospital_id,
        $recipient_hospital_id
    ]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Request sent successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
