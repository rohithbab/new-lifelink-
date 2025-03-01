<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Check if all required data is present
if (!isset($_POST['request_id']) || !isset($_POST['action']) || !isset($_POST['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data']);
    exit();
}

$request_id = $_POST['request_id'];
$action = $_POST['action'];
$message = $_POST['message'];
$hospital_id = $_SESSION['hospital_id'];

try {
    // First verify that this hospital owns this request
    $verify_stmt = $conn->prepare("
        SELECT recipient_hospital_id 
        FROM recipient_requests 
        WHERE request_id = ? AND status = 'Pending'
    ");
    $verify_stmt->execute([$request_id]);
    $request = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request || $request['recipient_hospital_id'] != $hospital_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to update this request']);
        exit();
    }

    // Update the request status
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    
    $update_stmt = $conn->prepare("
        UPDATE recipient_requests 
        SET status = ?, 
            response_message = ?,
            response_date = CURRENT_TIMESTAMP
        WHERE request_id = ?
    ");
    
    $update_stmt->execute([$status, $message, $request_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Request ' . strtolower($status) . ' successfully'
    ]);

} catch(PDOException $e) {
    error_log("Error updating recipient request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while updating the request']);
}
?>
