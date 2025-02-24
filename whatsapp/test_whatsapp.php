<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';
require_once 'WhatsAppService.php';

try {
    // Create WhatsApp service instance
    $whatsappService = new WhatsAppService();
    
    // Test phone number - this should be the number that sent 'join paint-taught'
    $recipientNumber = "919150272441"; // Update this with the actual number
    
    echo "<h2>WhatsApp Test</h2>";
    echo "<p>Testing with phone number: " . $recipientNumber . "</p>";
    echo "<p>Twilio WhatsApp number: " . TWILIO_WHATSAPP_NUMBER . "</p>";
    
    // Test message
    $result = $whatsappService->sendApprovalMessage($recipientNumber, "TEST-ODML-123");
    
    // Print result
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // Check error logs
    echo "<h3>Recent Error Logs:</h3>";
    $logFile = "C:/xampp/php/logs/php_error_log"; // Update this path if needed
    if (file_exists($logFile)) {
        $logs = shell_exec("tail -n 20 " . escapeshellarg($logFile));
        echo "<pre>" . htmlspecialchars($logs) . "</pre>";
    } else {
        echo "<p>Error log file not found at: " . htmlspecialchars($logFile) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
