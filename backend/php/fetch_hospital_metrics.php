<?php
session_start();
require_once '../../config/connection.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

try {
    // Prepare metrics queries
    $metrics = [
        'totalDonors' => "SELECT COUNT(*) FROM donors WHERE hospital_id = ?",
        'totalRecipients' => "SELECT COUNT(*) FROM recipients WHERE hospital_id = ?",
        'pendingRequests' => "SELECT COUNT(*) FROM donors WHERE hospital_id = ? AND status = 'pending'",
        'approvedDonations' => "SELECT COUNT(*) FROM donors WHERE hospital_id = ? AND status = 'approved'"
    ];

    $response = [];

    foreach ($metrics as $key => $query) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $hospital_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response[$key] = $result->fetch_row()[0];
    }

    // Send response
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

$conn->close();
