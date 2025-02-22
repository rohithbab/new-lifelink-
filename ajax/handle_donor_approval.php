<?php
session_start();
require_once '../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$approval_id = $_POST['approval_id'] ?? null;
$action = $_POST['action'] ?? null;
$reason = $_POST['reason'] ?? null;

if (!$approval_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if the approval exists and belongs to this hospital
    $check_stmt = $conn->prepare("
        SELECT donor_id, status 
        FROM hospital_donor_approvals 
        WHERE approval_id = ? AND hospital_id = ?
    ");
    $check_stmt->execute([$approval_id, $hospital_id]);
    $approval = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$approval) {
        throw new Exception('Invalid approval request');
    }

    if ($approval['status'] !== 'Pending') {
        throw new Exception('This request has already been processed');
    }

    // Update the approval status
    $update_stmt = $conn->prepare("
        UPDATE hospital_donor_approvals 
        SET status = ?, 
            rejection_reason = ?,
            processed_at = NOW() 
        WHERE approval_id = ?
    ");

    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $update_stmt->execute([$status, $reason, $approval_id]);

    // If approved, update donor status
    if ($action === 'approve') {
        $donor_stmt = $conn->prepare("
            UPDATE donor 
            SET status = 'Approved'
            WHERE donor_id = ?
        ");
        $donor_stmt->execute([$approval['donor_id']]);
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
