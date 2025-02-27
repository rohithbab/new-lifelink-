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
        
        // If number has more than 10 digits and starts with "91", take last 10 digits
        if (strlen($phone) > 10 && substr($phone, 0, 2) === '91') {
            $phone = substr($phone, -10);
        }
        
        // If number is exactly 10 digits, use it as is
        if (strlen($phone) === 10) {
            // Add country code
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
            
            $message = "🎉 Congratulations!\n\n";
            $message .= "Your registration with LifeLink has been successfully approved!\n\n";
            $message .= "Login Credentials:\n";
            $message .= "📱 ODML ID: " . $odmlId . "\n";
            $message .= "📧 Email: Your registered email\n";
            $message .= "🔑 Password: The one you created during registration\n\n";
            $message .= "To access the system:\n";
            $message .= "1. Visit our webapp\n";
            $message .= "2. Login using your Email, Password and ODML ID\n";
            $message .= "3. Save this WhatsApp contact for important updates\n\n";
            $message .= "Welcome to the LifeLink family! Together, we can save lives. 🤝\n\n";
            $message .= "Best regards,\nLifeLink Team\n\n";
            $message .= "Note: This is a WhatsApp sandbox message. To continue receiving messages, please send 'join paint-taught' to " . TWILIO_WHATSAPP_NUMBER;
            
            $message = $this->client->messages->create(
                "whatsapp:+" . $formattedPhone,
                [
                    "from" => TWILIO_WHATSAPP_NUMBER,
                    "body" => $message
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
            
            $message = "Dear User,\n\n";
            $message .= "We sincerely regret to inform you that your registration with LifeLink could not be approved at this time.\n\n";
            $message .= "Reason for rejection: " . $reason . "\n\n";
            $message .= "If you believe this was a mistake or would like to discuss this further:\n";
            $message .= "1. Contact our support team\n";
            $message .= "2. You can reapply with updated information\n";
            $message .= "3. Save this WhatsApp contact for future communication\n\n";
            $message .= "We appreciate your interest in LifeLink and hope to work with you in the future.\n\n";
            $message .= "Best regards,\nLifeLink Team\n\n";
            $message .= "Note: This is a WhatsApp sandbox message. To continue receiving messages, please send 'join paint-taught' to " . TWILIO_WHATSAPP_NUMBER;
            
            $message = $this->client->messages->create(
                "whatsapp:+" . $formattedPhone,
                [
                    "from" => TWILIO_WHATSAPP_NUMBER,
                    "body" => $message
                ]
            );
            
            error_log("WhatsApp rejection sent successfully. SID: " . $message->sid); // Debug log
            return ["success" => true, "message" => "Message sent successfully", "sid" => $message->sid];
        } catch (Exception $e) {
            error_log("WhatsApp Error: " . $e->getMessage()); // Debug log
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    
    public function sendHospitalApprovalMessage($phoneNumber, $userType, $hospitalName, $reason) {
        try {
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $message = "🎉 Great News!\n\n";
            $message .= "Your " . ucfirst($userType) . " request has been APPROVED by " . $hospitalName . "! 🏥\n\n";
            $message .= "Message from the Hospital:\n";
            $message .= "\"" . $reason . "\"\n\n";
            $message .= "What's Next:\n";
            $message .= "1. You are now officially registered under " . $hospitalName . "\n";
            $message .= "2. The hospital will provide you with necessary medical support\n";
            $message .= "3. Keep this contact saved for important updates\n";
            $message .= "4. Stay in touch with your hospital for guidance\n\n";
            $message .= "Together, we're making a difference! 💝\n\n";
            $message .= "Best regards,\nLifeLink & " . $hospitalName . "\n\n";
            $message .= "Note: This is a WhatsApp sandbox message. To continue receiving messages, please send 'join paint-taught' to " . TWILIO_WHATSAPP_NUMBER;
            
            $message = $this->client->messages->create(
                "whatsapp:+" . $formattedPhone,
                [
                    "from" => TWILIO_WHATSAPP_NUMBER,
                    "body" => $message
                ]
            );
            
            return ["success" => true, "message" => "Message sent successfully", "sid" => $message->sid];
        } catch (Exception $e) {
            error_log("WhatsApp Error: " . $e->getMessage());
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    
    public function sendHospitalRejectionMessage($phoneNumber, $userType, $hospitalName, $reason) {
        try {
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $message = "Dear User,\n\n";
            $message .= "We regret to inform you that your " . ucfirst($userType) . " request at " . $hospitalName . " was not approved at this time.\n\n";
            $message .= "Message from the Hospital:\n";
            $message .= "\"" . $reason . "\"\n\n";
            $message .= "Don't lose hope! Here's what you can do:\n";
            $message .= "1. Review the feedback provided\n";
            $message .= "2. Consider applying to other hospitals in our network\n";
            $message .= "3. Contact our support team if you need guidance\n\n";
            $message .= "We understand this might be disappointing, but remember there are many hospitals in our network ready to help! 🙏\n\n";
            $message .= "Best regards,\nLifeLink & " . $hospitalName . "\n\n";
            $message .= "Note: This is a WhatsApp sandbox message. To continue receiving messages, please send 'join paint-taught' to " . TWILIO_WHATSAPP_NUMBER;
            
            $message = $this->client->messages->create(
                "whatsapp:+" . $formattedPhone,
                [
                    "from" => TWILIO_WHATSAPP_NUMBER,
                    "body" => $message
                ]
            );
            
            return ["success" => true, "message" => "Message sent successfully", "sid" => $message->sid];
        } catch (Exception $e) {
            error_log("WhatsApp Error: " . $e->getMessage());
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
