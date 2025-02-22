<?php
session_start();
require_once '../../config/connection.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$hospital_id = $_SESSION['hospital_id'];

// Validate required fields
$required_fields = ['name', 'age', 'blood_type', 'organ', 'contact', 'email', 'address'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit();
    }
}

try {
    // Prepare insert statement
    $stmt = $conn->prepare("
        INSERT INTO donors (
            hospital_id, name, age, blood_type, organ, 
            contact, email, address, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->bind_param(
        "isisssss",
        $hospital_id,
        $data['name'],
        $data['age'],
        $data['blood_type'],
        $data['organ'],
        $data['contact'],
        $data['email'],
        $data['address']
    );

    if ($stmt->execute()) {
        $donor_id = $stmt->insert_id;

        // Create notification for admin
        $notification_stmt = $conn->prepare("
            INSERT INTO notifications (
                type, recipient_type, recipient_id, 
                title, message, created_at
            ) VALUES (
                'new_donor', 'admin', 1,
                'New Donor Registration',
                ?, NOW()
            )
        ");

        $message = "New donor registration from " . $_SESSION['hospital_name'] . 
                  " - " . $data['name'] . " (" . $data['organ'] . ")";
        $notification_stmt->bind_param("s", $message);
        $notification_stmt->execute();

        // Send response
        echo json_encode([
            'success' => true,
            'message' => 'Donor added successfully',
            'donor_id' => $donor_id
        ]);
    } else {
        throw new Exception("Error adding donor");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
