<?php
session_start();
require_once '../../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'];
$is_read = $data['is_read'];
$hospital_id = $_SESSION['hospital_id'];

try {
    // Update notification read status
    $stmt = $conn->prepare("
        UPDATE hospital_notifications 
        SET is_read = ? 
        WHERE notification_id = ? AND hospital_id = ?
    ");
    
    if ($stmt->execute([$is_read, $notification_id, $hospital_id])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update notification status');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
