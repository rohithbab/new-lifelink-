<?php
session_start();
require_once '../config/connection.php';

// Security check - only admin and hospital staff can view medical reports
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'hospital')) {
    header("HTTP/1.1 403 Forbidden");
    exit('Access denied');
}

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit('Missing required parameters');
}

$type = filter_var($_GET['type'], FILTER_SANITIZE_STRING);
$id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

try {
    // Get the medical report file path from database based on type
    if ($type === 'donor') {
        $stmt = $conn->prepare("SELECT medical_reports_path FROM donor WHERE donor_id = ?");
    } elseif ($type === 'recipient') {
        $stmt = $conn->prepare("SELECT recipient_medical_reports FROM recipient_registration WHERE recipient_id = ?");
    } else {
        header("HTTP/1.1 400 Bad Request");
        exit('Invalid type parameter');
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $medical_report_file = $type === 'donor' ? $row['medical_reports_path'] : $row['recipient_medical_reports'];
        
        // Use the new directory structure
        $base_path = __DIR__ . '/../uploads/';
        $file_path = $base_path . ($type === 'donor' ? 'donors/medical_reports_path/' : 'recipient_registration/recipient_medical_reports/') . basename($medical_report_file);
        
        // Check if file exists
        if (!file_exists($file_path)) {
            error_log("File not found at: " . $file_path);
            header("HTTP/1.1 404 Not Found");
            exit('Medical report file not found. Please contact administrator.');
        }
        
        // Get file extension
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Set appropriate content type
        switch ($file_extension) {
            case 'jpg':
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            case 'pdf':
                header('Content-Type: application/pdf');
                break;
            case 'doc':
            case 'docx':
                header('Content-Type: application/msword');
                break;
            default:
                header("HTTP/1.1 415 Unsupported Media Type");
                exit('Unsupported file type');
        }
        
        // Output file
        readfile($file_path);
        exit();
    } else {
        header("HTTP/1.1 404 Not Found");
        exit('Record not found');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit('An error occurred while retrieving the file');
}
