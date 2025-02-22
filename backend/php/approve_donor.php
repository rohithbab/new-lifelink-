<?php
session_start();
require_once 'connection.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$donor_id = $data['donor_id'];
$hospital_id = $_SESSION['hospital_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get donor details
    $query = "SELECT d.name, d.blood_group, d.organs_to_donate 
              FROM donor d 
              WHERE d.donor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $donor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $donor = $result->fetch_assoc();

    if (!$donor) {
        throw new Exception('Donor not found');
    }

    // Update approval status
    $query = "UPDATE hospital_donor_approvals 
              SET status = 'approved', 
                  approval_date = CURRENT_TIMESTAMP 
              WHERE donor_id = ? AND hospital_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $donor_id, $hospital_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No matching donor approval request found');
    }

    // Create notification with donor details
    $message = "New donor {$donor['name']} (Blood Group: {$donor['blood_group']}) has registered to donate {$donor['organs_to_donate']}";
    $query = "INSERT INTO hospital_notifications (hospital_id, type, message, related_id, is_read, created_at, link_url) 
              VALUES (?, 'donor_registration', ?, ?, 0, NOW(), 'hospitals_handles_donors_status.php')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isi', $hospital_id, $message, $donor_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
