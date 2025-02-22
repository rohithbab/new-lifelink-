<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['hospital_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['approval_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$approval_id = $_POST['approval_id'];

try {
    $stmt = $conn->prepare("UPDATE hospital_recipient_approvals SET status = 'approved', approval_date = NOW() WHERE approval_id = ? AND hospital_id = ?");
    $result = $stmt->execute([$approval_id, $hospital_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
