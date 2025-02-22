<?php
session_start();
require_once 'connection.php';
require_once 'queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get the time range
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

try {
    // Get all required analytics data
    $data = [
        'registrations' => [
            'donors' => getDailyRegistrations($conn, 'donors', $days),
            'recipients' => getDailyRegistrations($conn, 'recipients', $days)
        ],
        'organTypes' => getOrganTypeStats($conn),
        'bloodTypes' => getBloodTypeStats($conn),
        'successRate' => getSuccessfulMatches($conn),
        'regional' => getRegionalStats($conn)
    ];

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching analytics data'
    ]);
}

// Helper function to get daily registrations
function getDailyRegistrations($conn, $table, $days) {
    $stmt = $conn->prepare("
        SELECT DATE(registration_date) as date, COUNT(*) as count
        FROM $table
        WHERE registration_date >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)
        GROUP BY DATE(registration_date)
        ORDER BY date
    ");
    $stmt->execute([':days' => $days]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = array_fill(0, $days, 0);
    
    foreach ($results as $row) {
        $dayIndex = floor((strtotime($row['date']) - strtotime("-$days days")) / (60 * 60 * 24));
        if ($dayIndex >= 0 && $dayIndex < $days) {
            $data[$dayIndex] = intval($row['count']);
        }
    }
    
    return $data;
}
?>
