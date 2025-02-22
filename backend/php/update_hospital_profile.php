<?php
session_start();
require_once 'connection.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$hospital_id = $_SESSION['hospital_id'];

// Validate required fields
$required_fields = ['name', 'email', 'phone', 'address', 'region'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

try {
    // Update hospital profile
    $query = "UPDATE hospitals 
              SET name = ?, 
                  email = ?, 
                  phone = ?, 
                  address = ?, 
                  region = ?,
                  updated_at = CURRENT_TIMESTAMP 
              WHERE hospital_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssi', 
        $data['name'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['region'],
        $hospital_id
    );
    
    if ($stmt->execute()) {
        // Update session data
        $_SESSION['hospital_name'] = $data['name'];
        $_SESSION['hospital_email'] = $data['email'];
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update profile');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
