<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['hospital_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if (!isset($_POST['approval_id']) || !isset($_POST['status']) || !isset($_POST['reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$approval_id = $_POST['approval_id'];
$status = $_POST['status'];
$reason = $_POST['reason'];
$hospital_id = $_SESSION['hospital_id'];

try {
    // First verify that this approval belongs to the hospital
    $check_stmt = $conn->prepare("SELECT donor_id FROM hospital_donor_approvals WHERE approval_id = ? AND hospital_id = ?");
    $check_stmt->execute([$approval_id, $hospital_id]);
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid approval ID']);
        exit();
    }

    // Update the status
    $update_stmt = $conn->prepare("
        UPDATE hospital_donor_approvals 
        SET status = ?, reason = ?, updated_at = NOW() 
        WHERE approval_id = ? AND hospital_id = ?
    ");
    
    $result = $update_stmt->execute([$status, $reason, $approval_id, $hospital_id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

} catch(PDOException $e) {
    error_log("Error updating donor status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
