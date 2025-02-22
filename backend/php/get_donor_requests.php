<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['donor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            dr.*,
            h.name as hospital_name
        FROM donor_requests dr
        JOIN hospitals h ON dr.hospital_id = h.id
        WHERE dr.donor_id = :donor_id
        ORDER BY dr.request_date DESC
    ");

    $stmt->execute([':donor_id' => $_SESSION['donor_id']]);
    $requests = $stmt->fetchAll();

    echo json_encode($requests);

} catch(PDOException $e) {
    error_log("Error fetching donor requests: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching your requests']);
}
?>
