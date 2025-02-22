<?php
session_start();
require_once '../../config/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

// Get data from POST request
$data = json_decode(file_get_contents('php://input'), true);
$donor_id = $data['donor_id'] ?? null;
$status = $data['status'] ?? null;
$rejection_reason = $data['rejection_reason'] ?? null;

if (!$donor_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Update donor status
$update_query = "UPDATE donors SET status = ? WHERE donor_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $status, $donor_id);
$success = $stmt->execute();

if ($success) {
    // Get donor details for email
    $get_donor = "SELECT name, email FROM donors WHERE donor_id = ?";
    $stmt = $conn->prepare($get_donor);
    $stmt->bind_param("i", $donor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $donor = $result->fetch_assoc();
    
    // Send email notification
    $to = $donor['email'];
    $subject = "Donor Application Status Update - LifeLink";
    $message = "Dear " . $donor['name'] . ",\n\n";
    $message .= "Your donor application with LifeLink has been reviewed by the admin.\n\n";
    $message .= "Status: " . strtoupper($status) . "\n";
    
    if ($status === 'rejected' && $rejection_reason) {
        $message .= "Reason: " . $rejection_reason . "\n\n";
        // Store rejection details
        $_SESSION['rejection_details'][$donor_id] = [
            'reason' => $rejection_reason,
            'date' => date('Y-m-d H:i:s'),
            'email_sent' => false
        ];
    } else {
        $message .= "\nThank you for your commitment to saving lives through organ donation.\n";
    }
    
    $message .= "\nBest Regards,\nLifeLink Admin Team";
    
    $mail_sent = mail($to, $subject, $message);
    
    if ($status === 'rejected') {
        $_SESSION['rejection_details'][$donor_id]['email_sent'] = $mail_sent;
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating donor status']);
}
