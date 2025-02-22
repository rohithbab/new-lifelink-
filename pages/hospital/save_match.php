<?php
session_start();
require_once '../../config/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if hospital is logged in
if (!isset($_SESSION['hospital_id'])) {
    error_log("Hospital not logged in");
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get the logged in hospital ID (this will be the match_made_by)
$match_made_by = $_SESSION['hospital_id'];

// Log the received POST data
error_log("Received POST data: " . print_r($_POST, true));

try {
    // Prepare the insert statement
    $query = "INSERT INTO made_matches_by_hospitals (
        match_made_by,
        donor_id,
        donor_name,
        donor_hospital_id,
        donor_hospital_name,
        recipient_id,
        recipient_name,
        recipient_hospital_id,
        recipient_hospital_name,
        organ_type,
        blood_group
    ) VALUES (
        :match_made_by,
        :donor_id,
        :donor_name,
        :donor_hospital_id,
        :donor_hospital_name,
        :recipient_id,
        :recipient_name,
        :recipient_hospital_id,
        :recipient_hospital_name,
        :organ_type,
        :blood_group
    )";

    $stmt = $conn->prepare($query);

    // Convert IDs to integers
    $donor_id = intval($_POST['donor_id']);
    $donor_hospital_id = intval($_POST['donor_hospital_id']);
    $recipient_id = intval($_POST['recipient_id']);
    $recipient_hospital_id = intval($_POST['recipient_hospital_id']);

    // Bind parameters
    $stmt->bindParam(':match_made_by', $match_made_by, PDO::PARAM_INT);
    $stmt->bindParam(':donor_id', $donor_id, PDO::PARAM_INT);
    $stmt->bindParam(':donor_name', $_POST['donor_name'], PDO::PARAM_STR);
    $stmt->bindParam(':donor_hospital_id', $donor_hospital_id, PDO::PARAM_INT);
    $stmt->bindParam(':donor_hospital_name', $_POST['donor_hospital_name'], PDO::PARAM_STR);
    $stmt->bindParam(':recipient_id', $recipient_id, PDO::PARAM_INT);
    $stmt->bindParam(':recipient_name', $_POST['recipient_name'], PDO::PARAM_STR);
    $stmt->bindParam(':recipient_hospital_id', $recipient_hospital_id, PDO::PARAM_INT);
    $stmt->bindParam(':recipient_hospital_name', $_POST['recipient_hospital_name'], PDO::PARAM_STR);
    $stmt->bindParam(':organ_type', $_POST['organ_type'], PDO::PARAM_STR);
    $stmt->bindParam(':blood_group', $_POST['blood_group'], PDO::PARAM_STR);

    // Log the query and parameters
    error_log("Query: " . $query);
    error_log("Parameters: " . print_r([
        'match_made_by' => $match_made_by,
        'donor_id' => $donor_id,
        'donor_name' => $_POST['donor_name'],
        'donor_hospital_id' => $donor_hospital_id,
        'donor_hospital_name' => $_POST['donor_hospital_name'],
        'recipient_id' => $recipient_id,
        'recipient_name' => $_POST['recipient_name'],
        'recipient_hospital_id' => $recipient_hospital_id,
        'recipient_hospital_name' => $_POST['recipient_hospital_name'],
        'organ_type' => $_POST['organ_type'],
        'blood_group' => $_POST['blood_group']
    ], true));

    // Execute the query
    $stmt->execute();

    // Return success response
    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    // Log the error and return failure response
    error_log("Error creating match: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
