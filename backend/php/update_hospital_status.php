<?php
session_start();
require_once 'connection.php';
require_once 'queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

if (!isset($data['hospital_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$hospital_id = $data['hospital_id'];
$status = $data['status'];

// Validate status
if (!in_array($status, ['approved', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Begin transaction
    $conn->beginTransaction();

    // Get hospital details
    $stmt = $conn->prepare("SELECT hospital_name, email FROM hospitals WHERE hospital_id = ?");
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospital = $result->fetch_assoc();

    if (!$hospital) {
        throw new Exception("Hospital not found");
    }

    // Update hospital status
    if (updateHospitalStatus($conn, $hospital_id, $status, $_SESSION['admin_id'])) {
        // Create notification
        createNotification(
            $conn,
            'hospital',
            $status,
            $hospital_id,
            "Hospital " . $hospital['hospital_name'] . " has been " . $status
        );

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Hospital status updated successfully'
        ]);
    } else {
        throw new Exception("Failed to update hospital status");
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
