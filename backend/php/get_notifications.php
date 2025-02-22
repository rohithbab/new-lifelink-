<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in (either admin or hospital)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['hospital_logged_in'])) {
    http_response_code(403);
    exit('Unauthorized');
}

header('Content-Type: application/json');

try {
    if (isset($_SESSION['admin_id'])) {
        // Admin notifications
        $notifications = getAdminNotifications($conn);
    } else {
        // Hospital notifications
        $hospital_id = $_SESSION['hospital_id'];
        $notifications = getHospitalNotifications($conn, $hospital_id);
    }
    
    echo json_encode($notifications);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Function to get admin notifications
function getAdminNotifications($conn) {
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE admin_id IS NOT NULL 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get hospital notifications
function getHospitalNotifications($conn, $hospital_id) {
    // Get donor requests notifications
    $stmt = $conn->prepare("
        SELECT 
            'donor_request' as type,
            dr.request_id,
            dr.requesting_hospital_id,
            dr.donor_hospital_id,
            dr.request_date,
            dr.status,
            dr.response_date,
            dr.response_message,
            h.name as hospital_name,
            d.name as donor_name,
            d.blood_group,
            ha.organ_type,
            NULL as is_read,
            dr.request_date as created_at
        FROM donor_requests dr
        JOIN hospitals h ON (h.hospital_id = dr.requesting_hospital_id OR h.hospital_id = dr.donor_hospital_id)
        JOIN donor d ON d.donor_id = dr.donor_id
        JOIN hospital_donor_approvals ha ON ha.donor_id = d.donor_id
        WHERE (dr.requesting_hospital_id = ? OR dr.donor_hospital_id = ?)
    ");
    $stmt->execute([$hospital_id, $hospital_id]);
    $request_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get registration notifications
    $stmt = $conn->prepare("
        SELECT *
        FROM hospital_notifications
        WHERE hospital_id = ? 
        AND (type = 'donor_registration' OR type = 'recipient_registration')
    ");
    $stmt->execute([$hospital_id]);
    $registration_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge and sort notifications by date
    $notifications = array_merge($request_notifications, $registration_notifications);
    usort($notifications, function($a, $b) {
        $date_a = isset($a['request_date']) ? $a['request_date'] : $a['created_at'];
        $date_b = isset($b['request_date']) ? $b['request_date'] : $b['created_at'];
        return strtotime($date_b) - strtotime($date_a);
    });

    return array_slice($notifications, 0, 50); // Return only the latest 50
}
?>
