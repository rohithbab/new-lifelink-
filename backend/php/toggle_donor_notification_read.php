<?php
session_start();
require_once '../../config/db_connect.php';

// Check if donor is logged in
if (!isset($_SESSION['is_donor']) || !$_SESSION['is_donor']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;
$is_read = $data['is_read'] ?? null;

if ($notification_id === null || $is_read === null) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    // Verify the notification belongs to the logged-in donor
    $stmt = $conn->prepare("
        SELECT donor_id 
        FROM donor_notifications 
        WHERE notification_id = ?
    ");
    $stmt->execute([$notification_id]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notification || $notification['donor_id'] != $_SESSION['donor_id']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    // Update read status
    $stmt = $conn->prepare("
        UPDATE donor_notifications 
        SET is_read = ? 
        WHERE notification_id = ?
    ");
    $stmt->execute([$is_read, $notification_id]);

    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    error_log("Error toggling notification read status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
