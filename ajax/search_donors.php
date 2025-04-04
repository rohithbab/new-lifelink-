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
$data = json_decode(file_get_contents('php://input'), true);
$searchTerm = isset($data['searchTerm']) ? trim($data['searchTerm']) : '';
$filterType = isset($data['filterType']) ? trim($data['filterType']) : '';

try {
    // Build the query
    $query = "
        SELECT 
            d.donor_id,
            d.name as donor_name,
            d.blood_group,
            d.email as donor_email,
            d.phone as donor_phone,
            d.organs_to_donate,
            h.hospital_id,
            h.name as hospital_name,
            h.email as hospital_email,
            h.phone as hospital_phone,
            dr.status as request_status
        FROM donor d
        INNER JOIN hospital_donor_approvals hda ON d.donor_id = hda.donor_id
        INNER JOIN hospitals h ON hda.hospital_id = h.hospital_id
        LEFT JOIN donor_requests dr ON d.donor_id = dr.donor_id 
            AND dr.requesting_hospital_id = ? 
            AND dr.status IN ('Pending', 'Approved', 'Rejected')
        WHERE hda.status = 'approved'
        AND h.hospital_id != ?
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE donor_id = d.donor_id
        )
    ";
    
    $params = [$hospital_id, $hospital_id];
    
    // Add search conditions based on filter type
    if (!empty($searchTerm)) {
        if ($filterType === 'blood') {
            // Handle common variations of blood types
            $searchBlood = strtolower($searchTerm);
            $searchBlood = str_replace(['plus', '+'], '+', $searchBlood);
            $searchBlood = str_replace(['minus', '-'], '-', $searchBlood);
            
            $query .= " AND LOWER(d.blood_group) LIKE ?";
            $params[] = "%" . $searchBlood . "%";
        } elseif ($filterType === 'organ') {
            $query .= " AND LOWER(d.organs_to_donate) LIKE ?";
            $params[] = "%" . strtolower($searchTerm) . "%";
        }
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
            'organs_to_donate' => htmlspecialchars($donor['organs_to_donate']),
            'donor_email' => htmlspecialchars($donor['donor_email']),
            'donor_phone' => htmlspecialchars($donor['donor_phone']),
            'hospital_id' => $donor['hospital_id'],
            'hospital_name' => htmlspecialchars($donor['hospital_name']),
            'hospital_email' => htmlspecialchars($donor['hospital_email']),
            'hospital_phone' => htmlspecialchars($donor['hospital_phone']),
            'request_status' => $donor['request_status']
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
