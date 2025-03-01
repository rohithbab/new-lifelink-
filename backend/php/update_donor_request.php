<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Check if all required data is present
if (!isset($_POST['request_id']) || !isset($_POST['action']) || !isset($_POST['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data']);
    exit();
}

$request_id = $_POST['request_id'];
$action = $_POST['action'];
$message = $_POST['message'];
$hospital_id = $_SESSION['hospital_id'];

try {
    // First verify that this hospital owns this request and get donor details
    $verify_stmt = $conn->prepare("
        SELECT 
            dr.donor_hospital_id,
            dr.requesting_hospital_id,
            dr.donor_id,
            d.name as donor_name,
            hda.organ_type
        FROM donor_requests dr
        JOIN donor d ON d.donor_id = dr.donor_id
        JOIN hospital_donor_approvals hda ON hda.donor_id = dr.donor_id AND hda.hospital_id = dr.donor_hospital_id
        WHERE dr.request_id = ? AND dr.status = 'Pending'
    ");
    $verify_stmt->execute([$request_id]);
    $request = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request || $request['donor_hospital_id'] != $hospital_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to update this request']);
        exit();
    }

    // Update the request status
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    
    $update_stmt = $conn->prepare("
        UPDATE donor_requests 
        SET status = ?, 
            response_message = ?,
            response_date = CURRENT_TIMESTAMP
        WHERE request_id = ?
    ");
    
    $update_stmt->execute([$status, $message, $request_id]);

    // If approved, create a new donor approval for the requesting hospital
    if ($status === 'Approved') {
        // Create new approval
        $approve_stmt = $conn->prepare("
            INSERT INTO hospital_donor_approvals 
            (donor_id, hospital_id, status, organ_type, approval_date) 
            VALUES (?, ?, 'approved', ?, CURRENT_TIMESTAMP)
        ");
        $approve_stmt->execute([
            $request['donor_id'],
            $request['requesting_hospital_id'],
            $request['organ_type']
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Request ' . strtolower($status) . ' successfully'
    ]);

} catch(PDOException $e) {
    error_log("Error updating donor request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while updating the request']);
}
?>
