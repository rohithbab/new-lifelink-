<?php
session_start();
require_once 'connection.php';
require_once 'queries.php';
require_once __DIR__ . '/../../whatsapp/WhatsAppService.php';

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

try {
    $success = false;
    $phone = null;
    
    // Create WhatsApp service instance
    $whatsappService = new WhatsAppService();
    
    switch ($type) {
        case 'donor':
            $success = updateDonorODMLID($conn, $id, $odml_id);
            if ($success) {
                // Get donor's phone number
                $stmt = $conn->prepare("SELECT phone FROM donor WHERE donor_id = ?");
                $stmt->execute([$id]);
                $phone = $stmt->fetchColumn();
            }
            break;
            
        case 'hospital':
            $success = updateHospitalODMLID($conn, $id, $odml_id);
            if ($success) {
                // Get hospital's phone number
                $stmt = $conn->prepare("SELECT phone FROM hospitals WHERE hospital_id = ?");
                $stmt->execute([$id]);
                $phone = $stmt->fetchColumn();
            }
            break;
            
        case 'recipient':
            $success = updateRecipientODMLID($conn, $id, $odml_id);
            if ($success) {
                // Get recipient's phone number - using correct column name
                $stmt = $conn->prepare("SELECT phone FROM recipient_registration WHERE id = ?");
                $stmt->execute([$id]);
                $phone = $stmt->fetchColumn();
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid type specified']);
            exit();
    }

    if ($success && $phone) {
        // Send WhatsApp notification
        $whatsappResult = $whatsappService->sendApprovalMessage($phone, $odml_id);
        error_log("WhatsApp Result: " . print_r($whatsappResult, true)); // Debug log
    }

    echo json_encode([
        'success' => $success,
        'whatsapp' => $whatsappResult,
        'message' => $success ? null : 'Failed to update ODML ID'
    ]);
    
} catch (Exception $e) {
    error_log("Error in update_odml_id.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'whatsapp' => $whatsappResult
    ]);
}
