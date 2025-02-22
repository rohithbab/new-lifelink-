<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;

if (!$notification_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Notification ID is required']);
    exit();
}

try {
    // Mark notification as read
    $stmt = $conn->prepare("
        UPDATE hospital_notifications 
        SET is_read = 1 
        WHERE notification_id = ? AND hospital_id = ?
    ");
    
    $stmt->execute([$notification_id, $hospital_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Error marking notification as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
