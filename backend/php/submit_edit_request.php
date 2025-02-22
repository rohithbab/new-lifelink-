<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['donor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if (!isset($_POST['fieldsToEdit']) || !isset($_POST['reason'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $stmt = $conn->prepare("
        INSERT INTO edit_requests (
            user_id,
            user_type,
            fields_to_edit,
            reason,
            status,
            request_date
        ) VALUES (
            :user_id,
            'donor',
            :fields_to_edit,
            :reason,
            'pending',
            NOW()
        )
    ");

    $stmt->execute([
        ':user_id' => $_SESSION['donor_id'],
        ':fields_to_edit' => $_POST['fieldsToEdit'],
        ':reason' => $_POST['reason']
    ]);

    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    error_log("Error submitting edit request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit edit request']);
}
?>
