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

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$organ = isset($_GET['organ']) ? $_GET['organ'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // Build query with filters
    $query = "SELECT * FROM donors WHERE hospital_id = ?";
    $params = [$hospital_id];
    $types = "i";

    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR blood_type LIKE ? OR contact LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        $types .= "sss";
    }

    if (!empty($organ)) {
        $query .= " AND organ = ?";
        $params[] = $organ;
        $types .= "s";
    }

    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $donors = [];
    while ($row = $result->fetch_assoc()) {
        // Remove sensitive information
        unset($row['medical_history']);
        $donors[] = $row;
    }

    // Send response
    header('Content-Type: application/json');
    echo json_encode($donors);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

$conn->close();
