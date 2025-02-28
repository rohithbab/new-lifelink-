<?php
session_start();
require_once '../config/db_connect.php';

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get search parameters
$bloodType = isset($_POST['bloodType']) ? trim($_POST['bloodType']) : '';
$organType = isset($_POST['organType']) ? trim($_POST['organType']) : '';

try {
    // Build the query
    $query = "
        SELECT 
            d.donor_id,
            d.name as donor_name,
            d.blood_group,
            d.email as donor_email,
            d.phone as donor_phone,
            hda.organ_type,
            h.name as hospital_name,
            h.email as hospital_email,
            h.phone as hospital_phone,
            hda.status as approval_status
        FROM donor d
        INNER JOIN hospital_donor_approvals hda ON d.donor_id = hda.donor_id
        INNER JOIN hospitals h ON hda.hospital_id = h.hospital_id
        WHERE hda.status = 'approved'
        AND hda.hospital_id != ?
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE donor_id = d.donor_id
        )
    ";
    
    $params = [$hospital_id];
    
    // Add search conditions based on filter type
    if (!empty($bloodType)) {
        // Handle common variations of blood types
        $searchBlood = strtolower($bloodType);
        $searchBlood = str_replace(['plus', '+'], '+', $searchBlood);
        $searchBlood = str_replace(['minus', '-'], '-', $searchBlood);
        
        $query .= " AND LOWER(d.blood_group) LIKE ?";
        $params[] = "%" . $searchBlood . "%";
    }
    
    if (!empty($organType)) {
        $query .= " AND LOWER(hda.organ_type) LIKE ?";
        $params[] = "%" . strtolower($organType) . "%";
    }
    
    $query .= " ORDER BY d.name ASC";
    
    // Log the query and parameters for debugging
    error_log("Search Query: " . $query);
    error_log("Parameters: " . print_r($params, true));
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the results for display
    $formattedDonors = array_map(function($donor) {
        return [
            'donor_id' => $donor['donor_id'],
            'donor_name' => htmlspecialchars($donor['donor_name']),
            'blood_group' => htmlspecialchars($donor['blood_group']),
            'organ_type' => htmlspecialchars($donor['organ_type']),
            'donor_email' => htmlspecialchars($donor['donor_email']),
            'donor_phone' => htmlspecialchars($donor['donor_phone']),
            'hospital_name' => htmlspecialchars($donor['hospital_name']),
            'hospital_email' => htmlspecialchars($donor['hospital_email']),
            'hospital_phone' => htmlspecialchars($donor['hospital_phone']),
            'approval_status' => htmlspecialchars($donor['approval_status'])
        ];
    }, $donors);
    
    header('Content-Type: application/json');
    echo json_encode($formattedDonors);
    
} catch(PDOException $e) {
    error_log("Error searching donors: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}
?>
