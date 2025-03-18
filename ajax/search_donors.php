<?php
require_once '../config/db_connect.php';
session_start();

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$searchTerm = $data['searchTerm'];
$filterType = $data['filterType'];

try {
    $query = "
        SELECT DISTINCT
            d.donor_id,
            d.name as donor_name,
            d.blood_group,
            d.email as donor_email,
            d.phone as donor_phone,
            d.organs_to_donate,
            h.hospital_id,
            h.name as hospital_name,
            COALESCE(dr.status, 'Not Requested') as request_status
        FROM donor d
        INNER JOIN hospital_donor_approvals hda ON d.donor_id = hda.donor_id
        INNER JOIN hospitals h ON hda.hospital_id = h.hospital_id
        LEFT JOIN donor_requests dr ON d.donor_id = dr.donor_id AND dr.requesting_hospital_id = :hospitalId
        WHERE hda.status = 'approved'
        AND h.hospital_id != :hospitalId
        AND NOT EXISTS (
            SELECT 1 FROM donor_and_recipient_requests 
            WHERE donor_id = d.donor_id
        )";

    // Add filter conditions
    if ($filterType === 'blood') {
        $query .= " AND d.blood_group LIKE :searchTerm";
    } elseif ($filterType === 'organ') {
        $query .= " AND d.organs_to_donate LIKE :searchTerm";
    }

    $query .= " ORDER BY 
        CASE WHEN hda.hospital_id = :hospitalId THEN 0 ELSE 1 END,
        d.name ASC";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':hospitalId', $hospital_id, PDO::PARAM_INT);
    $stmt->bindValue(':searchTerm', "%$searchTerm%", PDO::PARAM_STR);
    $stmt->execute();

    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($donors);

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
