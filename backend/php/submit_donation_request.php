<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['donor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Validate input
if (!isset($_POST['hospital_id']) || !isset($_POST['organ_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if donor already has a pending request for the same organ type
    $stmt = $conn->prepare("
        SELECT id 
        FROM donor_requests 
        WHERE donor_id = :donor_id 
        AND organ_type = :organ_type 
        AND status = 'pending'
    ");
    
    $stmt->execute([
        ':donor_id' => $_SESSION['donor_id'],
        ':organ_type' => $_POST['organ_type']
    ]);

    if ($stmt->fetch()) {
        $conn->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'You already have a pending request for this organ type']);
        exit;
    }

    // Insert donation request
    $stmt = $conn->prepare("
        INSERT INTO donor_requests (
            donor_id, 
            hospital_id, 
            organ_type, 
            request_date, 
            status
        ) VALUES (
            :donor_id,
            :hospital_id,
            :organ_type,
            NOW(),
            'pending'
        )
    ");

    $stmt->execute([
        ':donor_id' => $_SESSION['donor_id'],
        ':hospital_id' => $_POST['hospital_id'],
        ':organ_type' => $_POST['organ_type']
    ]);

    $requestId = $conn->lastInsertId();

    // Create notification for the hospital
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            user_type,
            user_id,
            type,
            message,
            created_at
        ) VALUES (
            'hospital',
            :hospital_id,
            'new_donor_request',
            :message,
            NOW()
        )
    ");

    // Get donor name for the notification
    $stmt2 = $conn->prepare("SELECT name FROM donors WHERE id = :donor_id");
    $stmt2->execute([':donor_id' => $_SESSION['donor_id']]);
    $donor = $stmt2->fetch();
    $donorName = $donor['name'];

    $message = "New donor request received from $donorName for " . $_POST['organ_type'] . " donation.";
    $stmt->execute([
        ':hospital_id' => $_POST['hospital_id'],
        ':message' => $message
    ]);

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Your donation request has been submitted successfully. The hospital will review your request.',
        'request_id' => $requestId
    ]);

} catch(PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    error_log("Error submitting donation request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while submitting your request']);
}
?>
