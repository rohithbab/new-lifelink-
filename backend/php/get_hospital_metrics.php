<?php
session_start();
require_once 'connection.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

try {
    // Get total approved donors
    $query = "SELECT COUNT(*) as total FROM hospital_donor_approvals 
              WHERE hospital_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_donors = $result->fetch_assoc()['total'];

    // Get total approved recipients
    $query = "SELECT COUNT(*) as total FROM hospital_recipient_approvals 
              WHERE hospital_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_recipients = $result->fetch_assoc()['total'];

    // Get total pending requests (both donors and recipients)
    $query = "SELECT 
                (SELECT COUNT(*) FROM hospital_donor_approvals 
                 WHERE hospital_id = ? AND status = 'pending') +
                (SELECT COUNT(*) FROM hospital_recipient_approvals 
                 WHERE hospital_id = ? AND status = 'pending') as total";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $hospital_id, $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_requests = $result->fetch_assoc()['total'];

    // Get total approved matches
    $query = "SELECT COUNT(*) as total FROM organ_matches 
              WHERE hospital_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $approved_matches = $result->fetch_assoc()['total'];

    // Return metrics
    echo json_encode([
        'total_donors' => $total_donors,
        'total_recipients' => $total_recipients,
        'pending_requests' => $pending_requests,
        'approved_matches' => $approved_matches
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
