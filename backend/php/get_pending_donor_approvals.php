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
    // Get pending donor approvals for this hospital
    $query = "SELECT d.*, hda.request_date 
              FROM donor d
              INNER JOIN hospital_donor_approvals hda ON d.donor_id = hda.donor_id
              WHERE hda.hospital_id = ? AND hda.status = 'pending'
              ORDER BY hda.request_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $donors = [];
    while ($row = $result->fetch_assoc()) {
        $donors[] = $row;
    }
    
    echo json_encode($donors);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
