<?php
session_start();
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['donor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

try {
    $donor_id = $_SESSION['donor_id'];
    $response = [];

    // Get request statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM donor_requests 
        WHERE donor_id = :donor_id
    ");
    $stmt->execute([':donor_id' => $donor_id]);
    $stats = $stmt->fetch();

    $response['statistics'] = [
        'total' => (int)$stats['total'],
        'pending' => (int)$stats['pending'],
        'approved' => (int)$stats['approved'],
        'completed' => (int)$stats['completed'],
        'rejected' => (int)$stats['rejected']
    ];

    // Get recent activities (last 5)
    $stmt = $conn->prepare("
        SELECT 
            dr.id,
            dr.organ_type,
            dr.request_date,
            dr.status,
            h.name as hospital_name
        FROM donor_requests dr
        JOIN hospitals h ON dr.hospital_id = h.id
        WHERE dr.donor_id = :donor_id
        ORDER BY dr.request_date DESC
        LIMIT 5
    ");
    $stmt->execute([':donor_id' => $donor_id]);
    $response['recent_activities'] = $stmt->fetchAll();

    // Get suggestion based on user state
    $suggestion = [];
    if ($stats['total'] == 0) {
        $suggestion = [
            'message' => 'Start your journey of saving lives – Search for a hospital to donate!',
            'action' => 'Search Hospitals',
            'action_type' => 'search'
        ];
    } elseif ($stats['pending'] > 0) {
        $suggestion = [
            'message' => 'You have ' . $stats['pending'] . ' pending request(s). Check their status!',
            'action' => 'View Requests',
            'action_type' => 'requests'
        ];
    } elseif ($stats['completed'] > 0 && $stats['total'] < 3) {
        $suggestion = [
            'message' => 'Your donations have already helped save lives. Ready to help more?',
            'action' => 'Donate Again',
            'action_type' => 'search'
        ];
    } else {
        $suggestion = [
            'message' => 'Every donation counts! Search hospitals to start a new request.',
            'action' => 'Start Request',
            'action_type' => 'search'
        ];
    }
    $response['suggestion'] = $suggestion;

    echo json_encode($response);

} catch(PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching dashboard data']);
}
?>
