<?php
session_start();
require_once '../config/db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$bloodType = $data['bloodType'] ?? '';
$organType = $data['organType'] ?? '';

if (empty($bloodType) && empty($organType)) {
    echo json_encode(['success' => false, 'message' => 'Search term is required']);
    exit();
}

try {
    // Build the query
    $query = "
        SELECT 
            r.*,
            h.name as hospital_name,
            hra.status as approval_status
        FROM recipient_registration r
        INNER JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id
        INNER JOIN hospitals h ON hra.hospital_id = h.hospital_id
        WHERE hra.status = 'approved'
        AND h.hospital_id != ?
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE recipient_id = r.id
        )
    ";
    
    $params = [$hospital_id];
    
    // Add search conditions
    if (!empty($bloodType)) {
        $query .= " AND LOWER(r.blood_type) LIKE LOWER(?)";
        $params[] = "%$bloodType%";
    }
    
    if (!empty($organType)) {
        $query .= " AND LOWER(r.organ_required) LIKE LOWER(?)";
        $params[] = "%$organType%";
    }
    
    $query .= " ORDER BY r.full_name ASC";
    
    // Log the query and parameters for debugging
    error_log("Blood Type: " . $bloodType);
    error_log("Organ Type: " . $organType);
    error_log("Hospital ID: " . $hospital_id);
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the number of results
    error_log("Number of results: " . count($recipients));

    // Format results
    $formattedResults = array_map(function($row) {
        return [
            'id' => $row['id'],
            'full_name' => htmlspecialchars($row['full_name']),
            'email' => htmlspecialchars($row['email']),
            'phone' => htmlspecialchars($row['phone']),
            'blood_type' => htmlspecialchars($row['blood_type']),
            'organ_required' => htmlspecialchars($row['organ_required']),
            'hospital_name' => htmlspecialchars($row['hospital_name']),
            'approval_status' => htmlspecialchars($row['approval_status'])
        ];
    }, $recipients);

    echo json_encode([
        'success' => true,
        'results' => $formattedResults
    ]);

} catch(Exception $e) {
    error_log("Error in recipient search: " . $e->getMessage());
    error_log("SQL Query: " . $query);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching: ' . $e->getMessage()
    ]);
}
?>
