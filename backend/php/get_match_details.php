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

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['donor_id']) || !isset($data['recipient_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    // Get donor details
    $stmt = $conn->prepare("
        SELECT d.*, h.name as hospital_name
        FROM donors d
        JOIN hospitals h ON d.hospital_id = h.id
        WHERE d.id = :id
    ");
    $stmt->execute([':id' => $data['donor_id']]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recipient details
    $stmt = $conn->prepare("
        SELECT r.*, h.name as hospital_name
        FROM recipients r
        JOIN hospitals h ON r.hospital_id = h.id
        WHERE r.id = :id
    ");
    $stmt->execute([':id' => $data['recipient_id']]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$donor || !$recipient) {
        throw new Exception('Donor or recipient not found');
    }
    
    // Calculate compatibility score
    $compatibility_score = calculateDetailedCompatibilityScore($donor, $recipient);
    
    echo json_encode([
        'success' => true,
        'donor' => $donor,
        'recipient' => $recipient,
        'compatibility_score' => $compatibility_score,
        'compatibility_factors' => [
            'blood_type_match' => checkBloodTypeCompatibility($donor['blood_type'], $recipient['blood_type']),
            'age_factor' => calculateAgeFactor($donor['age'], $recipient['age']),
            'urgency_factor' => calculateUrgencyFactor($recipient['urgency_level']),
            'waiting_time' => calculateWaitingTimeFactor($recipient['registration_date'])
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error getting match details'
    ]);
}

function calculateDetailedCompatibilityScore($donor, $recipient) {
    $score = 0;
    
    // Blood type compatibility (40% of total score)
    $bloodTypeScore = checkBloodTypeCompatibility($donor['blood_type'], $recipient['blood_type']);
    $score += $bloodTypeScore * 0.4;
    
    // Age factor (20% of total score)
    $ageFactor = calculateAgeFactor($donor['age'], $recipient['age']);
    $score += $ageFactor * 0.2;
    
    // Urgency level (25% of total score)
    $urgencyFactor = calculateUrgencyFactor($recipient['urgency_level']);
    $score += $urgencyFactor * 0.25;
    
    // Waiting time (15% of total score)
    $waitingFactor = calculateWaitingTimeFactor($recipient['registration_date']);
    $score += $waitingFactor * 0.15;
    
    return round($score);
}

function checkBloodTypeCompatibility($donorType, $recipientType) {
    $compatibility = [
        'O-' => ['O-'],
        'O+' => ['O+', 'O-'],
        'A-' => ['A-', 'O-'],
        'A+' => ['A+', 'A-', 'O+', 'O-'],
        'B-' => ['B-', 'O-'],
        'B+' => ['B+', 'B-', 'O+', 'O-'],
        'AB-' => ['AB-', 'A-', 'B-', 'O-'],
        'AB+' => ['AB+', 'AB-', 'A+', 'A-', 'B+', 'B-', 'O+', 'O-']
    ];
    
    return in_array($donorType, $compatibility[$recipientType]) ? 100 : 0;
}

function calculateAgeFactor($donorAge, $recipientAge) {
    $ageDiff = abs($donorAge - $recipientAge);
    if ($ageDiff <= 5) return 100;
    if ($ageDiff <= 10) return 90;
    if ($ageDiff <= 15) return 80;
    if ($ageDiff <= 20) return 70;
    return 60;
}

function calculateUrgencyFactor($urgencyLevel) {
    switch ($urgencyLevel) {
        case 'high':
            return 100;
        case 'medium':
            return 75;
        case 'low':
            return 50;
        default:
            return 0;
    }
}

function calculateWaitingTimeFactor($registrationDate) {
    $waitingDays = (strtotime('now') - strtotime($registrationDate)) / (60 * 60 * 24);
    return min(($waitingDays / 365) * 100, 100); // Max score after 1 year of waiting
}
?>
