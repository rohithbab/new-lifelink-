<?php
session_start();
require_once '../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['donor_id']) || !isset($data['recipient_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$donor_id = $data['donor_id'];
$recipient_id = $data['recipient_id'];

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if donor is available (either owned or shared)
    $stmt = $conn->prepare("
        SELECT ha.*, d.blood_group, d.name as donor_name, 
               r.blood_group as recipient_blood_group, r.name as recipient_name
        FROM hospital_donor_approvals ha
        JOIN donor d ON d.donor_id = ha.donor_id
        JOIN recipient r ON r.recipient_id = ?
        WHERE ha.donor_id = ? 
        AND ha.hospital_id = ?
        AND (ha.status = 'Approved' OR ha.status = 'Shared')
        AND ha.is_matched = FALSE
    ");
    $stmt->execute([$recipient_id, $donor_id, $hospital_id]);
    $match_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match_data) {
        throw new Exception('Donor not found or already matched');
    }

    // Verify blood group compatibility
    if ($match_data['blood_group'] !== $match_data['recipient_blood_group']) {
        throw new Exception('Blood group mismatch');
    }

    // Mark both original and shared approvals as matched
    if ($match_data['status'] === 'Shared') {
        // Update original approval
        $stmt = $conn->prepare("
            UPDATE hospital_donor_approvals 
            SET is_matched = TRUE 
            WHERE donor_id = ? AND status = 'Approved'
        ");
        $stmt->execute([$donor_id]);
    }

    // Update current approval
    $stmt = $conn->prepare("
        UPDATE hospital_donor_approvals 
        SET is_matched = TRUE 
        WHERE donor_id = ? AND hospital_id = ?
    ");
    $stmt->execute([$donor_id, $hospital_id]);

    // Create match record
    $stmt = $conn->prepare("
        INSERT INTO donor_recipient_matches (
            donor_id,
            recipient_id,
            hospital_id,
            match_date,
            status,
            organ_type
        ) VALUES (?, ?, ?, NOW(), 'Matched', ?)
    ");
    $stmt->execute([
        $donor_id,
        $recipient_id,
        $hospital_id,
        $match_data['organ_type']
    ]);

    // If this was a shared donor, notify the original hospital
    if ($match_data['status'] === 'Shared' && $match_data['shared_from']) {
        // Add notification logic here if needed
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Successfully matched donor ' . $match_data['donor_name'] . 
                    ' with recipient ' . $match_data['recipient_name']
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
