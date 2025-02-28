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
    // First, let's check what statuses exist in the table
    $statusQuery = "SELECT DISTINCT status FROM hospital_recipient_approvals";
    $statusStmt = $conn->query($statusQuery);
    $existingStatuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);
    debug_log("Existing status values in hospital_recipient_approvals:", $existingStatuses);

    // Build the query
    $query = "
        SELECT 
            r.id as recipient_id,
            r.full_name as recipient_name,
            r.blood_type,
            r.organ_required,
            r.email as recipient_email,
            r.phone_number as recipient_phone,
            r.medical_condition,
            r.urgency_level,
            h.name as hospital_name,
            h.email as hospital_email,
            h.phone as hospital_phone,
            hra.status as approval_status
        FROM recipient_registration r
        INNER JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id
        INNER JOIN hospitals h ON hra.hospital_id = h.hospital_id
        WHERE hra.status IN (" . implode(',', array_fill(0, count($existingStatuses), '?')) . ")
        AND h.hospital_id != ?
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE recipient_id = r.id
        )
    ";
    
    $params = array_merge($existingStatuses, [$hospital_id]);
    
    // Add search conditions based on filter type
    if (!empty($bloodType)) {
        // Handle common variations of blood types
        $searchBlood = strtolower($bloodType);
        $searchBlood = str_replace(['plus', '+'], '+', $searchBlood);
        $searchBlood = str_replace(['minus', '-'], '-', $searchBlood);
        
        $query .= " AND LOWER(r.blood_type) LIKE ?";
        $params[] = "%" . $searchBlood . "%";
    }
    
    if (!empty($organType)) {
        $query .= " AND LOWER(r.organ_required) LIKE ?";
        $params[] = "%" . strtolower($organType) . "%";
    }
    
    $query .= " ORDER BY r.full_name ASC";
    
    debug_log("Final SQL Query:", $query);
    debug_log("Query Parameters:", $params);
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    debug_log("Number of results found:", count($recipients));
    if (count($recipients) > 0) {
        debug_log("First result:", $recipients[0]);
    } else {
        debug_log("No results found");
        
        // Let's check if there are any approved recipients at all
        $checkQuery = "
            SELECT COUNT(*) as count, status 
            FROM hospital_recipient_approvals 
            GROUP BY status
        ";
        $checkStmt = $conn->query($checkQuery);
        $statusCounts = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        debug_log("Recipients by status:", $statusCounts);

        // Let's also check a sample recipient
        $sampleQuery = "
            SELECT r.id, r.full_name, hra.status
            FROM recipient_registration r
            INNER JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id
            LIMIT 1
        ";
        $sampleStmt = $conn->query($sampleQuery);
        $sampleRecipient = $sampleStmt->fetch(PDO::FETCH_ASSOC);
        debug_log("Sample recipient record:", $sampleRecipient);
    }
    
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
            'hospital_name' => htmlspecialchars($recipient['hospital_name']),
            'hospital_email' => htmlspecialchars($recipient['hospital_email']),
            'hospital_phone' => htmlspecialchars($recipient['hospital_phone']),
            'approval_status' => htmlspecialchars($recipient['approval_status'])
        ];
    }, $recipients);
    
    debug_log("Formatted results:", $formattedRecipients);
    
    header('Content-Type: application/json');
    echo json_encode($formattedRecipients);
    
} catch(PDOException $e) {
    debug_log("Database error occurred:", [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}

debug_log("Search request completed");
?>
