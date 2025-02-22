<?php
session_start();
require_once '../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$donor_id = $data['donor_id'] ?? 0;
$requesting_hospital_id = $_SESSION['hospital_id'];

if (!$donor_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid donor ID']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Get donor and hospital information
    $stmt = $conn->prepare("
        SELECT d.blood_group, ha.organ_type, ha.hospital_id as donor_hospital_id
        FROM donor d
        JOIN hospital_donor_approvals ha ON d.donor_id = ha.donor_id
        WHERE d.donor_id = ? AND ha.status = 'Approved'
    ");
    $stmt->execute([$donor_id]);
    $donorInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donorInfo) {
        throw new Exception('Donor not found or not approved');
    }

    // Check if donor hospital is the same as requesting hospital
    if ($donorInfo['donor_hospital_id'] == $requesting_hospital_id) {
        throw new Exception('Cannot request your own donor');
    }

    // Check if request already exists
    $stmt = $conn->prepare("
        SELECT status 
        FROM donor_requests 
        WHERE donor_id = ? AND requesting_hospital_id = ? 
        AND status IN ('Pending', 'Approved')
    ");
    $stmt->execute([$donor_id, $requesting_hospital_id]);
    if ($stmt->fetch()) {
        throw new Exception('A request already exists for this donor');
    }

    // Check if donor already has an approved request
    $stmt = $conn->prepare("
        SELECT status 
        FROM donor_requests 
        WHERE donor_id = ? AND status = 'Approved'
    ");
    $stmt->execute([$donor_id]);
    if ($stmt->fetch()) {
        throw new Exception('This donor has already been approved for another hospital');
    }

    // Insert new request
    $stmt = $conn->prepare("
        INSERT INTO donor_requests (
            donor_id, 
            requesting_hospital_id, 
            donor_hospital_id,
            request_date,
            status
        ) VALUES (?, ?, ?, NOW(), 'Pending')
    ");
    $stmt->execute([
        $donor_id,
        $requesting_hospital_id,
        $donorInfo['donor_hospital_id']
    ]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Request sent successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
