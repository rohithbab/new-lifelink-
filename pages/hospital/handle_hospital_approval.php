<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../whatsapp/WhatsAppService.php';

if (!isset($_SESSION['hospital_id']) || !isset($_POST['approval_id']) || !isset($_POST['type']) || !isset($_POST['approval_reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$approval_id = $_POST['approval_id'];
$type = $_POST['type'];
$approval_reason = $_POST['approval_reason'];

try {
    // Get hospital name
    $stmt = $conn->prepare("SELECT hospital_name FROM hospitals WHERE hospital_id = ?");
    $stmt->execute([$hospital_id]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);
    $hospital_name = $hospital['hospital_name'];

    if ($type === 'donor') {
        // Update donor approval status and reason
        $stmt = $conn->prepare("
            UPDATE hospital_donor_approvals 
            SET status = 'approved', 
                approval_reason = ?, 
                approval_date = NOW() 
            WHERE approval_id = ? AND hospital_id = ?
        ");
        $stmt->execute([$approval_reason, $approval_id, $hospital_id]);

        // Get donor details for WhatsApp message
        $stmt = $conn->prepare("
            SELECT d.phone_number, d.donor_name 
            FROM donor_registration d 
            JOIN hospital_donor_approvals hda ON d.id = hda.donor_id 
            WHERE hda.approval_id = ?
        ");
        $stmt->execute([$approval_id]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);

        // Send WhatsApp message
        $whatsapp = new WhatsAppService();
        $result = $whatsapp->sendHospitalApprovalMessage(
            $donor['phone_number'],
            'donor',
            $hospital_name,
            $approval_reason
        );
    } else {
        // Update recipient approval status and reason
        $stmt = $conn->prepare("
            UPDATE hospital_recipient_approvals 
            SET status = 'approved', 
                approval_reason = ?, 
                approval_date = NOW() 
            WHERE approval_id = ? AND hospital_id = ?
        ");
        $stmt->execute([$approval_reason, $approval_id, $hospital_id]);

        // Get recipient details for WhatsApp message
        $stmt = $conn->prepare("
            SELECT r.phone_number, r.full_name 
            FROM recipient_registration r 
            JOIN hospital_recipient_approvals hra ON r.id = hra.recipient_id 
            WHERE hra.approval_id = ?
        ");
        $stmt->execute([$approval_id]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

        // Send WhatsApp message
        $whatsapp = new WhatsAppService();
        $result = $whatsapp->sendHospitalApprovalMessage(
            $recipient['phone_number'],
            'recipient',
            $hospital_name,
            $approval_reason
        );
    }

    echo json_encode([
        'success' => true, 
        'message' => ucfirst($type) . ' request approved successfully',
        'whatsapp_status' => $result
    ]);

} catch (PDOException $e) {
    error_log("Error in handle_hospital_approval.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in handle_hospital_approval.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
