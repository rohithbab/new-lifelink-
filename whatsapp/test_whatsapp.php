<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';
require_once 'WhatsAppService.php';

try {
    // Create WhatsApp service instance
    $whatsappService = new WhatsAppService();
    
    // Your WhatsApp number
    $recipientNumber = "919150272441";
    
    // Test message
    $result = $whatsappService->sendApprovalMessage($recipientNumber, "TEST-ODML-123");
    
    // Print result
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
