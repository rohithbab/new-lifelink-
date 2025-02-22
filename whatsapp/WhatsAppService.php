<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';

use Twilio\Rest\Client;

class WhatsAppService {
    private $client;
    
    public function __construct() {
        $this->client = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
    }
    
    public function sendApprovalMessage($phoneNumber, $odmlId) {
        try {
            $message = $this->client->messages->create(
                "whatsapp:+" . $phoneNumber, // To
                [
                    "from" => TWILIO_WHATSAPP_NUMBER,
                    "body" => "Your registration has been approved! Your ODML ID is: " . $odmlId
                ]
            );
            return ["success" => true, "message" => "Message sent successfully", "sid" => $message->sid];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    
    public function sendRejectionMessage($phoneNumber, $reason) {
        try {
            $message = $this->client->messages->create(
                "whatsapp:+" . $phoneNumber, // To
                [
                    "from" => TWILIO_WHATSAPP_NUMBER,
                    "body" => "Your registration status: Not Approved\nReason: " . $reason
                ]
            );
            return ["success" => true, "message" => "Message sent successfully", "sid" => $message->sid];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
