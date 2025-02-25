<?php
require_once 'connection.php';
require_once 'queries.php';

header('Content-Type: application/json');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Validate required fields
if (!isset($data['type']) || !isset($data['id']) || !isset($data['reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$type = $data['type'];
$id = $data['id'];
$reason = $data['reason'];

try {
    $conn = getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    $success = false;
    $phone = '';
    $name = '';
    
    // Update status based on type
    switch ($type) {
        case 'hospital':
            $stmt = $conn->prepare("UPDATE hospitals SET status = 'rejected', rejection_reason = ?, rejected_at = NOW() WHERE hospital_id = ?");
            $stmt->execute([$reason, $id]);
            
            // Get hospital details for WhatsApp
            $stmt = $conn->prepare("SELECT phone, name FROM hospitals WHERE hospital_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $phone = $result['phone'];
                $name = $result['name'];
            }
            $success = true;
            break;
            
        case 'donor':
            $stmt = $conn->prepare("UPDATE donor SET status = 'rejected', rejection_reason = ?, rejected_at = NOW() WHERE donor_id = ?");
            $stmt->execute([$reason, $id]);
            
            // Get donor details for WhatsApp
            $stmt = $conn->prepare("SELECT phone_number, full_name FROM donor WHERE donor_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $phone = $result['phone_number'];
                $name = $result['full_name'];
            }
            $success = true;
            break;
            
        case 'recipient':
            $stmt = $conn->prepare("UPDATE recipient_registration SET request_status = 'rejected', rejection_reason = ?, rejected_at = NOW() WHERE id = ?");
            $stmt->execute([$reason, $id]);
            
            // Get recipient details for WhatsApp
            $stmt = $conn->prepare("SELECT phone_number, full_name FROM recipient_registration WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $phone = $result['phone_number'];
                $name = $result['full_name'];
            }
            $success = true;
            break;
            
        default:
            throw new Exception('Invalid registration type');
    }
    
    if ($success) {
        // Send WhatsApp notification
        $message = "Dear " . $name . ",\n\n";
        $message .= "We regret to inform you that your registration for LifeLink has been rejected.\n\n";
        $message .= "Reason: " . $reason . "\n\n";
        $message .= "If you have any questions, please contact our support team.\n\n";
        $message .= "Best regards,\nLifeLink Team";
        
        // Use your WhatsApp API integration here
        // This is a placeholder - implement your actual WhatsApp sending logic
        sendWhatsAppMessage($phone, $message);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Registration rejected successfully']);
    } else {
        throw new Exception('Failed to update registration status');
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing rejection: ' . $e->getMessage()
    ]);
}

// Helper function to send WhatsApp message
function sendWhatsAppMessage($phone, $message) {
    // Implement your WhatsApp sending logic here
    // This is where you'll integrate with your WhatsApp API
    
    // For now, just log the message
    error_log("WhatsApp message would be sent to $phone: $message");
    return true;
}
?>
