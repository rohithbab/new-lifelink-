<?php
require_once 'connection.php';
require_once 'queries.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['type']) || !isset($data['id']) || !isset($data['reason'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$type = $data['type'];
$id = $data['id'];
$reason = $data['reason'];

// Validate reason
if (empty(trim($reason))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit;
}

try {
    $conn = getConnection();
    
    switch ($type) {
        case 'hospital':
            $result = rejectHospital($conn, $id, $reason);
            break;
        case 'donor':
            $result = rejectDonor($conn, $id, $reason);
            break;
        case 'recipient':
            $result = rejectRecipient($conn, $id, $reason);
            break;
        default:
            $result = ['success' => false, 'message' => 'Invalid entity type'];
    }
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(500);
    }
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error in handle_rejection.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing the rejection']);
}
?>
