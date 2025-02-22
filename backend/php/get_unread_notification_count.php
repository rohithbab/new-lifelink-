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
        SELECT COUNT(*) as count
        FROM notifications
        WHERE user_type = 'donor'
        AND user_id = :donor_id
        AND read_status = 0
    ");

    $stmt->execute([':donor_id' => $_SESSION['donor_id']]);
    $result = $stmt->fetch();

    echo json_encode(['count' => (int)$result['count']]);

} catch(PDOException $e) {
    error_log("Error getting notification count: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching notification count']);
}
?>
