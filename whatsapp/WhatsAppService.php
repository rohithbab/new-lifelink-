<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';

use Twilio\Rest\Client;

class WhatsAppService {
    private $client;
    
    public function __construct() {
        $this->client = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
    }
    
    private function formatPhoneNumber($phoneNumber) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If number starts with 0, remove it
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        // If number doesn't start with country code (91 for India), add it
        if (substr($phone, 0, 2) !== '91') {
            $phone = '91' . $phone;
        }
        
        error_log("Formatted phone number: " . $phone); // Debug log
        return $phone;
    }
    
    public function sendApprovalMessage($phoneNumber, $odmlId) {
        try {
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            error_log("Sending WhatsApp message to: whatsapp:+" . $formattedPhone); // Debug log
            error_log("Using Twilio number: " . TWILIO_WHATSAPP_NUMBER); // Debug log
            
            $message = $this->client->messages->create(
                "whatsapp:+" . $formattedPhone,
                [
                    "from" => TWILIO_WHATSAPP_NUMBER,
                    "body" => "Your registration has been approved! Your ODML ID is: " . $odmlId . "\n\nNote: This is a WhatsApp sandbox message. To continue receiving messages, please send 'join paint-taught' to " . TWILIO_WHATSAPP_NUMBER
                ]
            );
            
            error_log("WhatsApp message sent successfully. SID: " . $message->sid); // Debug log
            return ["success" => true, "message" => "Message sent successfully", "sid" => $message->sid];
        } catch (Exception $e) {
            error_log("WhatsApp Error: " . $e->getMessage()); // Debug log
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    
    public function sendRejectionMessage($phoneNumber, $reason) {
        try {
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            error_log("Sending WhatsApp rejection to: whatsapp:+" . $formattedPhone); // Debug log
            
            $message = $this->client->messages->create(
                "whatsapp:+" . $formattedPhone,
                [
                    "from" => TWILIO_WHATSAPP_NUMBER,
                    "body" => "Your registration status: Not Approved\nReason: " . $reason . "\n\nNote: This is a WhatsApp sandbox message. To continue receiving messages, please send 'join paint-taught' to " . TWILIO_WHATSAPP_NUMBER
                ]
            );
            
            error_log("WhatsApp rejection sent successfully. SID: " . $message->sid); // Debug log
            return ["success" => true, "message" => "Message sent successfully", "sid" => $message->sid];
        } catch (Exception $e) {
            error_log("WhatsApp Error: " . $e->getMessage()); // Debug log
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
