<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db_connect.php';

// Check if recipient is logged in
if (!isset($_SESSION['is_recipient']) || !$_SESSION['is_recipient']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;
$is_read = $data['is_read'] ?? null;

if ($notification_id === null || $is_read === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    // Update notification read status
    $stmt = $conn->prepare("
        UPDATE recipient_notifications 
        SET is_read = ?
        WHERE notification_id = ? 
        AND recipient_id = ?
    ");
    
    $result = $stmt->execute([$is_read, $notification_id, $_SESSION['recipient_id']]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
    }
} catch(PDOException $e) {
    error_log("Error updating notification: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
