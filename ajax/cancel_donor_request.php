<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in as donor
if (!isset($_SESSION['is_donor']) || !$_SESSION['is_donor']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$donor_id = $_SESSION['donor_id'];
$approval_id = $_POST['approval_id'] ?? null;

if (!$approval_id) {
    echo json_encode(['success' => false, 'error' => 'Missing approval ID']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if the request exists and belongs to this donor
    $check_stmt = $conn->prepare("
        SELECT status 
        FROM hospital_donor_approvals 
        WHERE approval_id = ? AND donor_id = ?
    ");
    $check_stmt->execute([$approval_id, $donor_id]);
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Invalid request');
    }

    if ($request['status'] !== 'Pending') {
        throw new Exception('Only pending requests can be cancelled');
    }

    // Update the request status
    $update_stmt = $conn->prepare("
        UPDATE hospital_donor_approvals 
        SET status = 'Cancelled',
            processed_at = NOW()
        WHERE approval_id = ?
    ");
    $update_stmt->execute([$approval_id]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
