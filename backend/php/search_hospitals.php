<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['search'])) {
    echo json_encode([]);
    exit;
}

$search = '%' . $_POST['search'] . '%';

try {
    $stmt = $conn->prepare("
        SELECT id, name, address, phone, email 
        FROM hospitals 
        WHERE name LIKE :search 
        OR address LIKE :search 
        ORDER BY name ASC 
        LIMIT 10
    ");
    
    $stmt->bindParam(':search', $search);
    $stmt->execute();
    
    $hospitals = $stmt->fetchAll();
    echo json_encode($hospitals);
    
} catch(PDOException $e) {
    error_log("Error searching hospitals: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while searching hospitals']);
}
?>
