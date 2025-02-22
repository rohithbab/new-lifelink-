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

if (!isset($_GET['donor_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Donor ID is required']);
    exit();
}

try {
    $matches = findPotentialMatches($conn, $_GET['donor_id']);
    
    // Calculate compatibility scores
    foreach ($matches as &$match) {
        $match['compatibility_score'] = calculateCompatibilityScore($match);
    }
    
    // Sort by compatibility score and urgency
    usort($matches, function($a, $b) {
        if ($a['urgency_level'] === $b['urgency_level']) {
            return $b['compatibility_score'] - $a['compatibility_score'];
        }
        return $a['urgency_level'] === 'high' ? -1 : 1;
    });
    
    echo json_encode([
        'success' => true,
        'matches' => $matches
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error finding matches'
    ]);
}

function calculateCompatibilityScore($recipient) {
    $score = 100;
    
    // Reduce score based on waiting time (longer wait = higher priority)
    $waitingDays = (strtotime('now') - strtotime($recipient['registration_date'])) / (60 * 60 * 24);
    $score += min($waitingDays / 30 * 10, 20); // Up to 20 points for waiting time
    
    // Adjust score based on urgency
    switch ($recipient['urgency_level']) {
        case 'high':
            $score += 30;
            break;
        case 'medium':
            $score += 15;
            break;
    }
    
    // Cap the score at 100
    return min($score, 100);
}
?>
