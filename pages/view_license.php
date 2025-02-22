<?php
session_start();
require_once '../config/connection.php';

// Security check - only admin and hospital staff can view licenses
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'hospital')) {
    header("HTTP/1.1 403 Forbidden");
    exit('Access denied');
}

if (!isset($_GET['hospital_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit('Hospital ID not provided');
}

$hospital_id = filter_var($_GET['hospital_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    // Get the license file path from database
    $stmt = $conn->prepare("SELECT license_file FROM hospitals WHERE hospital_id = ?");
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $license_file = $row['license_file'];
        // Use the new directory structure
        $file_path = __DIR__ . '/../uploads/hospitals/license_file/' . basename($license_file);
        
        // Check if file exists
        if (!file_exists($file_path)) {
            header("HTTP/1.1 404 Not Found");
            exit('License file not found');
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
            default:
                header("HTTP/1.1 415 Unsupported Media Type");
                exit('Unsupported file type');
        }
        
        // Prevent caching
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Output the file
        readfile($file_path);
        exit();
    } else {
        header("HTTP/1.1 404 Not Found");
        exit('Hospital not found');
    }
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    exit('Error retrieving license file');
}
?>
