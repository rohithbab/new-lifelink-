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
    // Get pending recipient approvals for this hospital
    $query = "SELECT r.*, hra.request_date 
              FROM recipient_registration r
              INNER JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id
              WHERE hra.hospital_id = ? AND hra.status = 'pending'
              ORDER BY hra.request_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recipients = [];
    while ($row = $result->fetch_assoc()) {
        $recipients[] = $row;
    }
    
    echo json_encode($recipients);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
