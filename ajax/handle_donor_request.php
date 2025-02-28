<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);
$donorId = $data['donorId'] ?? null;
$donorHospitalId = $data['donorHospitalId'] ?? null;
$action = $data['action'] ?? null;
$requestingHospitalId = $_SESSION['hospital_id'];

if (!$donorId || !$donorHospitalId || !$action) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    if ($action === 'request') {
        // Check if there's already a pending or approved request
        $checkStmt = $conn->prepare("
            SELECT status 
            FROM donor_requests 
            WHERE donor_id = ? 
            AND requesting_hospital_id = ? 
            AND status IN ('Pending', 'Approved')
        ");
        $checkStmt->execute([$donorId, $requestingHospitalId]);
        $existingRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRequest) {
            throw new Exception("A request already exists for this donor");
        }

        // Insert new request
        $stmt = $conn->prepare("
            INSERT INTO donor_requests 
            (donor_id, requesting_hospital_id, donor_hospital_id, request_date, status) 
            VALUES (?, ?, ?, NOW(), 'Pending')
        ");
        $stmt->execute([$donorId, $requestingHospitalId, $donorHospitalId]);

        $message = "Request sent successfully";
    } 
    else if ($action === 'cancel') {
        // Check if there's a pending request to cancel
        $stmt = $conn->prepare("
            UPDATE donor_requests 
            SET status = 'Cancelled',
                response_date = NOW(),
                response_message = 'Request cancelled by requesting hospital'
            WHERE donor_id = ? 
            AND requesting_hospital_id = ? 
            AND status = 'Pending'
        ");
        $stmt->execute([$donorId, $requestingHospitalId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No pending request found to cancel");
        }

        $message = "Request cancelled successfully";
    }
    else {
        throw new Exception("Invalid action");
    }

    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $conn->rollBack();
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>
