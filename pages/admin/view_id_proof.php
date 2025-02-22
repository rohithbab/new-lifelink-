<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config/connection.php';
require_once '../../includes/debug_logger.php';

// Security check - only admin can view ID proofs
if (!isset($_SESSION['admin_id'])) {
    debug_log("Access denied: No admin session");
    header("HTTP/1.1 403 Forbidden");
    exit('Access denied');
}

if (!isset($_GET['donor_id'])) {
    debug_log("Missing donor_id parameter");
    header("HTTP/1.1 400 Bad Request");
    exit('Donor ID not provided');
}

$donor_id = filter_var($_GET['donor_id'], FILTER_SANITIZE_NUMBER_INT);
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'donor';

try {
    debug_log("Attempting to fetch ID proof", [
        'donor_id' => $donor_id,
        'type' => $type
    ]);

    // Get the ID proof file path from database based on type
    if ($type === 'guardian') {
        $stmt = $conn->prepare("SELECT guardian_id_proof_path as proof_path FROM donor WHERE donor_id = ?");
        debug_log("Querying guardian ID proof");
    } else {
        $stmt = $conn->prepare("SELECT id_proof_path as proof_path FROM donor WHERE donor_id = ?");
        debug_log("Querying donor ID proof");
    }
    
    $stmt->bind_param("i", $donor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $proof_file = $row['proof_path'];
        debug_log("Database query result", ['proof_path' => $proof_file]);
        
        if (empty($proof_file)) {
            debug_log("Empty proof file path in database");
            header("HTTP/1.1 404 Not Found");
            exit('No ID proof file found');
        }
        
        // Use the new directory structure
        $base_path = __DIR__ . '/../../uploads/';
        if ($type === 'guardian') {
            $file_path = $base_path . 'donors/guardian_id_proof_path/' . $proof_file;
        } elseif ($type === 'recipient') {
            $file_path = $base_path . 'recipient_registration/id_document/' . $proof_file;
        } else {
            $file_path = $base_path . 'donors/id_proof_path/' . $proof_file;
        }
        
        debug_log("Constructed file path", [
            'base_path' => $base_path,
            'full_path' => $file_path,
            'file_exists' => file_exists($file_path)
        ]);
        
        // Check if file exists
        if (!file_exists($file_path)) {
            debug_log("File not found at path", ['path' => $file_path]);
            header("HTTP/1.1 404 Not Found");
            exit('ID proof file not found. Please contact administrator.');
        }
        
        // Get file extension
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        debug_log("File extension", ['extension' => $file_extension]);
        
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
                debug_log("Unsupported file type", ['extension' => $file_extension]);
                header("HTTP/1.1 415 Unsupported Media Type");
                exit('Unsupported file type');
        }
        
        debug_log("Attempting to output file", ['path' => $file_path]);
        // Output file content
        readfile($file_path);
        exit();
        
    } else {
        debug_log("Donor not found", ['donor_id' => $donor_id]);
        header("HTTP/1.1 404 Not Found");
        exit('Donor not found');
    }
    
} catch (Exception $e) {
    debug_log("Error in view_id_proof.php", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    header("HTTP/1.1 500 Internal Server Error");
    exit('Error retrieving ID proof. Please try again later.');
}
