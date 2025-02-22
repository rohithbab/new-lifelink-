<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['donor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT *
        FROM notifications
        WHERE user_type = 'donor'
        AND user_id = :donor_id
        ORDER BY created_at DESC
        LIMIT 50
    ");

    $stmt->execute([':donor_id' => $_SESSION['donor_id']]);
    $notifications = $stmt->fetchAll();

    // Mark notifications as read
    $stmt = $conn->prepare("
        UPDATE notifications
        SET read_status = 1
        WHERE user_type = 'donor'
        AND user_id = :donor_id
        AND read_status = 0
    ");

    $stmt->execute([':donor_id' => $_SESSION['donor_id']]);

    echo json_encode($notifications);

} catch(PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching notifications']);
}
?>
