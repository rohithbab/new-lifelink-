<?php
require_once 'connection.php';
require_once 'queries.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $odml_id = $_POST['odml_id'] ?? null;

    if (!$id || !$odml_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE recipient_registration SET odml_id = ?, request_status = 'approved' WHERE id = ?");
        $result = $stmt->execute([$odml_id, $id]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update ODML ID']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
