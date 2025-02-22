<?php
session_start();
require_once '../../config/db_connect.php';

// Check if donor is logged in
if (!isset($_SESSION['is_donor']) || !$_SESSION['is_donor']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$donor_id = $_SESSION['donor_id'];

// Get notification ID from POST request
$notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

if (!$notification_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification ID']);
    exit();
}

try {
    // First check if the notification exists, belongs to this donor, and is read
    $stmt = $conn->prepare("
        SELECT id, is_read, can_delete 
        FROM donor_notifications 
        WHERE id = ? AND donor_id = ? AND is_read = 1
    ");
    $stmt->execute([$notification_id, $donor_id]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notification) {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found or cannot be deleted']);
        exit();
    }

    // Delete the notification
    $stmt = $conn->prepare("DELETE FROM donor_notifications WHERE id = ? AND donor_id = ? AND is_read = 1");
    $result = $stmt->execute([$notification_id, $donor_id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete notification']);
    }

} catch(PDOException $e) {
    error_log("Error deleting donor notification: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?>
