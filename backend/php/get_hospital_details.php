<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['hospital_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Hospital ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT id, name, address, phone, email 
        FROM hospitals 
        WHERE id = :hospital_id
    ");
    
    $stmt->bindParam(':hospital_id', $_POST['hospital_id']);
    $stmt->execute();
    
    $hospital = $stmt->fetch();
    
    if ($hospital) {
        echo json_encode($hospital);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Hospital not found']);
    }
    
} catch(PDOException $e) {
    error_log("Error getting hospital details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching hospital details']);
}
?>
