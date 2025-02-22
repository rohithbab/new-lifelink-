<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['donor_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Validate status
$allowed_statuses = ['Pending', 'Approved', 'Rejected'];
if (!in_array($data['status'], $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Update donor status
    $stmt = $conn->prepare("UPDATE donor SET status = ? WHERE donor_id = ?");
    $result = $stmt->execute([$data['status'], $data['donor_id']]);

    if ($result) {
        // Log the update for debugging
        error_log("Successfully updated donor status. Donor ID: " . $data['donor_id'] . ", New Status: " . $data['status']);
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        error_log("Failed to update donor status. Donor ID: " . $data['donor_id']);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    error_log("Error in update_donor_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
