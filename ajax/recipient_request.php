<?php
require_once '../config/db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get POST data
$recipientId = $_POST['recipientId'];
$hospitalId = $_POST['hospitalId'];
$action = $_POST['action'];
$requestingHospitalId = $_SESSION['hospital_id'];

try {
    $conn->beginTransaction();

    // Check if request already exists
    $check_stmt = $conn->prepare("
        SELECT status 
        FROM recipient_requests 
        WHERE recipient_id = ? 
        AND requesting_hospital_id = ? 
        AND recipient_hospital_id = ?
    ");
    $check_stmt->execute([$recipientId, $requestingHospitalId, $hospitalId]);
    $existing_request = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_request) {
        if ($existing_request['status'] === 'Pending') {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'A request for this recipient is already pending']);
            exit();
        } else if ($existing_request['status'] === 'Approved') {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'You already have access to this recipient']);
            exit();
        }
    }

    // Insert new request
    $stmt = $conn->prepare("
        INSERT INTO recipient_requests (
            recipient_id,
            recipient_hospital_id,
            requesting_hospital_id,
            status,
            request_date
        ) VALUES (?, ?, ?, 'Pending', NOW())
    ");
    $stmt->execute([$recipientId, $hospitalId, $requestingHospitalId]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Error processing recipient request: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
