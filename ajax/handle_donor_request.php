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

if (!isset($data['request_id']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$request_id = $data['request_id'];
$action = $data['action'];
$message = isset($data['message']) ? $data['message'] : '';

try {
    // Start transaction
    $conn->beginTransaction();

    // First, verify the request exists and check permissions
    $stmt = $conn->prepare("
        SELECT dr.*, ha.organ_type 
        FROM donor_requests dr
        JOIN hospital_donor_approvals ha ON ha.donor_id = dr.donor_id AND ha.hospital_id = dr.donor_hospital_id
        WHERE dr.request_id = ? AND (dr.donor_hospital_id = ? OR dr.requesting_hospital_id = ?)
    ");
    $stmt->execute([$request_id, $hospital_id, $hospital_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Request not found or you do not have permission to modify it');
    }

    // Check if the action is valid based on the current status and user role
    $isOwnerHospital = $request['donor_hospital_id'] == $hospital_id;
    $isRequesterHospital = $request['requesting_hospital_id'] == $hospital_id;

    if ($action === 'approve' && (!$isOwnerHospital || $request['status'] !== 'Pending')) {
        throw new Exception('Invalid action: Only the donor hospital can approve pending requests');
    }

    if ($action === 'reject' && (!$isOwnerHospital || $request['status'] !== 'Pending')) {
        throw new Exception('Invalid action: Only the donor hospital can reject pending requests');
    }

    if ($action === 'cancel' && (!$isRequesterHospital || $request['status'] !== 'Approved')) {
        throw new Exception('Invalid action: Only the requesting hospital can cancel approved requests');
    }

    // Check if donor is already matched
    $stmt = $conn->prepare("
        SELECT is_matched 
        FROM hospital_donor_approvals 
        WHERE donor_id = ? AND (status = 'Approved' OR status = 'Shared') AND is_matched = TRUE
    ");
    $stmt->execute([$request['donor_id']]);
    if ($stmt->fetch()) {
        throw new Exception('This donor has already been matched with a recipient');
    }

    // Update request status - ONLY for this specific request
    $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
    
    $stmt = $conn->prepare("
        UPDATE donor_requests 
        SET status = ?, response_date = NOW(), response_message = ? 
        WHERE request_id = ? 
        AND donor_hospital_id = ? 
        AND requesting_hospital_id = ?
        AND status = 'Pending'
    ");
    $stmt->execute([
        $newStatus, 
        $message, 
        $request_id,
        $request['donor_hospital_id'],
        $request['requesting_hospital_id']
    ]);

    // If approving, create shared approval for requesting hospital
    if ($action === 'approve') {
        // First check if shared approval already exists
        $stmt = $conn->prepare("
            SELECT share_id 
            FROM shared_donor_approvals 
            WHERE donor_id = ? AND to_hospital_id = ? AND request_id = ?
        ");
        $stmt->execute([$request['donor_id'], $request['requesting_hospital_id'], $request_id]);
        if (!$stmt->fetch()) {
            // Create shared approval
            $stmt = $conn->prepare("
                INSERT INTO shared_donor_approvals (
                    donor_id,
                    from_hospital_id,
                    to_hospital_id,
                    request_id,
                    organ_type,
                    is_matched
                ) VALUES (?, ?, ?, ?, ?, FALSE)
            ");
            $stmt->execute([
                $request['donor_id'],
                $request['donor_hospital_id'],
                $request['requesting_hospital_id'],
                $request_id,
                $request['organ_type']
            ]);
        }
    }

    // If cancelling an approved request, remove shared approval
    if ($action === 'cancel') {
        $stmt = $conn->prepare("
            DELETE FROM shared_donor_approvals 
            WHERE donor_id = ? AND to_hospital_id = ? AND request_id = ?
        ");
        $stmt->execute([$request['donor_id'], $request['requesting_hospital_id'], $request_id]);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
