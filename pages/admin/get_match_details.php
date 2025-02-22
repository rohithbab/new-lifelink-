<?php
session_start();
require_once '../../backend/php/connection.php';
require_once '../../backend/php/organ_matches.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

// Check if match_id is provided
if (!isset($_GET['match_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Match ID not provided']);
    exit();
}

$match_id = intval($_GET['match_id']);
$match_details = getMatchDetails($conn, $match_id);

if ($match_details) {
    echo json_encode($match_details);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Match not found']);
}
