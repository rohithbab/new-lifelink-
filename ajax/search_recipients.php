<?php
session_start();
require_once '../config/db_connect.php';

// Create a debug log function
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= "\n" . print_r($data, true);
    }
    $log .= "\n----------------------------------------\n";
    error_log($log, 3, '../logs/recipient_search_debug.log');
}

debug_log("Search request started");
debug_log("POST data received:", $_POST);

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    debug_log("Unauthorized access attempt");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
debug_log("Hospital ID:", $hospital_id);

// Get search parameters
$bloodType = isset($_POST['bloodType']) ? trim($_POST['bloodType']) : '';
$organType = isset($_POST['organType']) ? trim($_POST['organType']) : '';

debug_log("Search parameters:", [
    'bloodType' => $bloodType,
    'organType' => $organType
]);

try {
    // Build the query
    $query = "
        SELECT DISTINCT
            r.id as recipient_id,
            r.full_name as recipient_name,
            r.blood_type,
            r.medical_condition,
            r.urgency_level,
            r.phone_number as recipient_phone,
            r.email as recipient_email,
            r.organ_required,
            h.hospital_id,
            h.name as hospital_name,
            COALESCE(rr.status, 'Not Requested') as request_status
        FROM recipient_registration r
        INNER JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id
        INNER JOIN hospitals h ON hra.hospital_id = h.hospital_id
        LEFT JOIN recipient_requests rr ON r.id = rr.recipient_id AND rr.requesting_hospital_id = ?
        WHERE hra.status = 'approved'
        AND h.hospital_id != ?
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE recipient_id = r.id
        )
    ";
    
    $params = [$hospital_id, $hospital_id];
    
    // Add search conditions based on filter type
    if (!empty($bloodType)) {
        // Handle common variations of blood types
        $searchBlood = strtolower($bloodType);
        $searchBlood = str_replace(['plus', '+'], '+', $searchBlood);
        $searchBlood = str_replace(['minus', '-'], '-', $searchBlood);
        
        $query .= " AND r.blood_type LIKE ?";
        $params[] = "%" . $searchBlood . "%";
    }
    
    if (!empty($organType)) {
        $query .= " AND r.organ_required LIKE ?";
        $params[] = "%" . strtolower($organType) . "%";
    }
    
    $query .= " ORDER BY 
        CASE WHEN hra.hospital_id = ? THEN 0 ELSE 1 END,
        r.urgency_level DESC,
        r.full_name ASC";
    
    $params[] = $hospital_id;
    
    debug_log("Final SQL Query:", $query);
    debug_log("Query Parameters:", $params);
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    debug_log("Number of results found:", count($recipients));
    
    // Format the results for display
    $formattedRecipients = array_map(function($recipient) {
        return [
            'recipient_id' => $recipient['recipient_id'],
            'recipient_name' => htmlspecialchars($recipient['recipient_name']),
            'blood_type' => htmlspecialchars($recipient['blood_type']),
            'organ_required' => htmlspecialchars($recipient['organ_required']),
            'recipient_email' => htmlspecialchars($recipient['recipient_email']),
            'recipient_phone' => htmlspecialchars($recipient['recipient_phone']),
            'medical_condition' => htmlspecialchars($recipient['medical_condition']),
            'urgency_level' => htmlspecialchars($recipient['urgency_level']),
            'hospital_id' => $recipient['hospital_id'],
            'hospital_name' => htmlspecialchars($recipient['hospital_name']),
            'request_status' => $recipient['request_status']
        ];
    }, $recipients);
    
    header('Content-Type: application/json');
    echo json_encode($formattedRecipients);
    
} catch(PDOException $e) {
    debug_log("Database error:", $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error occurred']);
}
?>
