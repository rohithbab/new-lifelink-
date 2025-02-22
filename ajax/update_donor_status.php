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
$status = $_POST['status'] ?? null;

if (!$approval_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

// Validate status
$valid_statuses = ['Pending', 'Approved', 'Rejected'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if the request exists and belongs to this hospital
    $check_stmt = $conn->prepare("
        SELECT status 
        FROM hospital_donor_approvals 
        WHERE approval_id = ? AND hospital_id = ?
    ");
    $check_stmt->execute([$approval_id, $hospital_id]);
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Invalid request');
    }

    // Get the current timestamp for approval_date
    $current_time = date('Y-m-d H:i:s');
    
    // Update the request status and approval_date
    $update_stmt = $conn->prepare("
        UPDATE hospital_donor_approvals 
        SET status = ?,
            approval_date = CASE 
                WHEN ? = 'Approved' THEN ? 
                ELSE NULL 
            END
        WHERE approval_id = ? AND hospital_id = ?
    ");
    
    $update_stmt->execute([$status, $status, $current_time, $approval_id, $hospital_id]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
