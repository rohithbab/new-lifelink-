<?php
session_start();
require_once '../config/connection.php';

// Security check - only admin can view ID proofs
if (!isset($_SESSION['admin_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit('Access denied');
}

if (!isset($_GET['donor_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit('Donor ID not provided');
}

$donor_id = filter_var($_GET['donor_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    // Get the ID proof file path from database
    $stmt = $conn->prepare("SELECT id_proof_path FROM donor WHERE donor_id = ?");
    $stmt->bind_param("i", $donor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $id_proof_file = $row['id_proof_path'];
        
        // Use the new directory structure
        $base_path = __DIR__ . '/../uploads/';
        if (isset($_GET['type']) && $_GET['type'] === 'guardian') {
            $file_path = $base_path . 'donors/guardian_id_proof_path/' . basename($id_proof_file);
        } else {
            $file_path = $base_path . 'donors/id_proof_path/' . basename($id_proof_file);
        }
        
        // Check if file exists
        if (!file_exists($file_path)) {
            error_log("File not found at: " . $file_path);
            header("HTTP/1.1 404 Not Found");
            exit('ID proof file not found. Please contact administrator.');
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
        
        // Output file content
        readfile($file_path);
        exit();
        
    } else {
        header("HTTP/1.1 404 Not Found");
        exit('Donor not found');
    }
    
} catch (Exception $e) {
    error_log("Error in view_id_proof.php: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit('Error retrieving ID proof. Please try again later.');
}
