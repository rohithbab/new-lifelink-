<?php
session_start();
require_once '../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get search parameters
$bloodType = isset($_POST['bloodType']) ? trim($_POST['bloodType']) : '';
$organType = isset($_POST['organType']) ? trim($_POST['organType']) : '';

try {
    // Build the query
    $query = "
        SELECT 
            d.*,
            ha.organ_type,
            ha.status
        FROM donor d
        JOIN hospital_donor_approvals ha ON d.donor_id = ha.donor_id
        WHERE ha.status = 'Approved'
        AND ha.hospital_id != ?
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE donor_id = d.donor_id
        )
    ";
    
    $params = [$hospital_id];
    
    // Add search conditions
    if (!empty($bloodType)) {
        $query .= " AND LOWER(d.blood_group) LIKE LOWER(?)";
        $params[] = "%$bloodType%";
    }
    
    if (!empty($organType)) {
        $query .= " AND LOWER(ha.organ_type) LIKE LOWER(?)";
        $params[] = "%$organType%";
    }
    
    $query .= " ORDER BY d.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($donors);
    
} catch(PDOException $e) {
    error_log("Error searching donors: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}
?>
