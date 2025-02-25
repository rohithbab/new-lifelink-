<?php
session_start();
require_once 'connection.php';
require_once 'queries.php';
require_once __DIR__ . '/../../whatsapp/WhatsAppService.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required parameters are set
if (!isset($_POST['type']) || !isset($_POST['id']) || !isset($_POST['odml_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$type = $_POST['type'];
$id = $_POST['id'];
$odml_id = $_POST['odml_id'];
$whatsappResult = null;

// Log incoming request
error_log("Processing ODML update - Type: $type, ID: $id, ODML ID: $odml_id");

try {
    $success = false;
    $phone = null;
    
    // Create WhatsApp service instance
    $whatsappService = new WhatsAppService();
    
    switch ($type) {
        case 'donor':
            $success = updateDonorODMLID($conn, $id, $odml_id);
            if ($success) {
                $stmt = $conn->prepare("SELECT phone FROM donor WHERE donor_id = ?");
                $stmt->execute([$id]);
                $phone = $stmt->fetchColumn();
                error_log("Donor phone: $phone");
            }
            break;
            
        case 'hospital':
            $success = updateHospitalODMLID($conn, $id, $odml_id);
            if ($success) {
                $stmt = $conn->prepare("SELECT phone FROM hospitals WHERE hospital_id = ?");
                $stmt->execute([$id]);
                $phone = $stmt->fetchColumn();
                error_log("Hospital phone: $phone");
            }
            break;
            
        case 'recipient':
            // First verify recipient exists
            $checkStmt = $conn->prepare("SELECT id, phone_number FROM recipient_registration WHERE id = ?");
            $checkStmt->execute([$id]);
            $recipient = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$recipient) {
                throw new Exception("Recipient not found with ID: $id");
            }
            
            error_log("Found recipient: " . print_r($recipient, true));
            
            // Use the updateRecipientODMLID function which sets status to 'accepted'
            $success = updateRecipientODMLID($conn, $id, $odml_id);
            
            if ($success) {
                error_log("Successfully updated recipient ODML ID");
                $phone = $recipient['phone_number'];
                error_log("Recipient phone: $phone");
            } else {
                error_log("Failed to update recipient ODML ID");
            }
            break;
            
        default:
            throw new Exception('Invalid type specified: ' . $type);
    }

    if ($success && $phone) {
        // Send WhatsApp notification
        $whatsappResult = $whatsappService->sendApprovalMessage($phone, $odml_id);
        error_log("WhatsApp Result for $type: " . print_r($whatsappResult, true));
    } else {
        error_log("Failed to proceed with WhatsApp notification. Success: " . ($success ? 'true' : 'false') . ", Phone: " . ($phone ?: 'not found'));
    }

    echo json_encode([
        'success' => $success,
        'whatsapp' => $whatsappResult,
        'message' => $success ? null : 'Failed to update ODML ID'
    ]);
    
} catch (Exception $e) {
    error_log("Error in update_odml_id.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'whatsapp' => $whatsappResult
    ]);
}
