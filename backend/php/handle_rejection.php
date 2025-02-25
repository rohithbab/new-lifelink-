<?php
require_once 'connection.php';
require_once 'queries.php';
require_once '../../whatsapp/config.php';
require_once '../../vendor/autoload.php';
use Twilio\Rest\Client;

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
    global $conn;
    // Begin transaction
    $conn->beginTransaction();
    
    $success = false;
    $phone = '';
    $name = '';
    
    // Update status based on type
    switch (strtolower($type)) {
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
            $stmt = $conn->prepare("UPDATE donor SET status = 'rejected', rejection_reason = ?, rejection_date = NOW() WHERE donor_id = ?");
            $stmt->execute([$reason, $id]);
            
            // Get donor details for WhatsApp
            $stmt = $conn->prepare("SELECT phone, name FROM donor WHERE donor_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $phone = $result['phone'];
                $name = $result['name'];
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
    
    if ($success && $phone && $name) {
        // Format the message
        $message = "Dear " . $name . ",\n\n";
        $message .= "We regret to inform you that your registration for LifeLink has been rejected.\n\n";
        $message .= "Reason: " . $reason . "\n\n";
        $message .= "If you have any questions, please contact our support team.\n\n";
        $message .= "Best regards,\nLifeLink Team";
        
        // Debug log
        error_log("Attempting to send WhatsApp message to: " . $phone);
        error_log("Message content: " . $message);
        
        // Send WhatsApp message using Twilio
        try {
            $client = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
            
            // Format the phone number to WhatsApp format
            $to = 'whatsapp:+' . ltrim($phone, '+');
            $from = TWILIO_WHATSAPP_NUMBER;
            
            error_log("Sending from: " . $from . " to: " . $to);
            
            $message = $client->messages->create(
                $to,
                [
                    'from' => $from,
                    'body' => $message
                ]
            );
            
            error_log("WhatsApp message sent successfully. Message SID: " . $message->sid);
            $whatsappStatus = "Message sent successfully";
        } catch (Exception $e) {
            error_log("Error sending WhatsApp message: " . $e->getMessage());
            if (strpos($e->getMessage(), 'exceeded the null daily messages limit') !== false) {
                $whatsappStatus = "Not sent - Daily message limit exceeded";
            } else {
                $whatsappStatus = "Not sent - " . $e->getMessage();
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success with WhatsApp status
    echo json_encode([
        'success' => true, 
        'message' => 'Registration rejected successfully',
        'whatsappStatus' => isset($whatsappStatus) ? $whatsappStatus : 'Not attempted'
    ]);
    
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
?>
