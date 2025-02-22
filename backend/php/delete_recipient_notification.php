<?php
session_start();
require_once '../../config/db_connect.php';

// Check if recipient is logged in
if (!isset($_SESSION['is_recipient']) || !$_SESSION['is_recipient']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;

if ($notification_id === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    // Only allow deletion of read notifications
    $stmt = $conn->prepare("
        DELETE FROM recipient_notifications 
        WHERE notification_id = ? 
        AND recipient_id = ? 
        AND is_read = 1 
        AND can_delete = 1
    ");
    
    $stmt->execute([$notification_id, $_SESSION['recipient_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found or cannot be deleted']);
    }
} catch(PDOException $e) {
    error_log("Error deleting notification: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
