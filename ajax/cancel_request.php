<?php
require_once '../config/db_connect.php';
session_start();

if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$requestId = $data['requestId'];
$type = $data['type'];
$hospitalId = $_SESSION['hospital_id'];

try {
    $conn->beginTransaction();

    if ($type === 'donor') {
        // Update donor_requests table
        $query = "
            UPDATE donor_requests 
            SET status = 'Canceled', 
                updated_at = NOW() 
            WHERE request_id = :requestId 
            AND requesting_hospital_id = :hospitalId 
            AND status = 'Pending'";
    } else {
        // Update recipient_requests table
        $query = "
            UPDATE recipient_requests 
            SET status = 'Canceled', 
                updated_at = NOW() 
            WHERE request_id = :requestId 
            AND requesting_hospital_id = :hospitalId 
            AND status = 'Pending'";
    }

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':requestId', $requestId, PDO::PARAM_INT);
    $stmt->bindParam(':hospitalId', $hospitalId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Request canceled successfully']);
    } else {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
    }

} catch (PDOException $e) {
    $conn->rollBack();
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
