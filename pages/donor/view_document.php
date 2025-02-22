<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in as donor
if (!isset($_SESSION['is_donor']) || !$_SESSION['is_donor']) {
    die("Unauthorized access");
}

// Get file path from query parameter
$file = isset($_GET['file']) ? $_GET['file'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Validate that this donor has access to this file
$donor_id = $_SESSION['donor_id'];
$stmt = $conn->prepare("SELECT * FROM donor WHERE donor_id = :donor_id");
$stmt->execute([':donor_id' => $donor_id]);
$donor = $stmt->fetch();

$valid_file = false;
if ($donor) {
    switch($type) {
        case 'id_proof':
            $valid_file = ($file === $donor['id_proof_path']);
            break;
        case 'medical':
            $valid_file = ($file === $donor['medical_reports_path']);
            break;
        case 'guardian':
            $valid_file = ($file === $donor['guardian_id_proof_path']);
            break;
    }
}

if (!$valid_file) {
    die("Unauthorized access to file");
}

// Set the file path
$file_path = "../../uploads/donors/" . $file;

// Check if file exists
if (!file_exists($file_path)) {
    die("File not found");
}

// Get file extension
$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Set appropriate headers based on file type
switch($ext) {
    case 'pdf':
        header('Content-Type: application/pdf');
        break;
    case 'jpg':
    case 'jpeg':
        header('Content-Type: image/jpeg');
        break;
    case 'png':
        header('Content-Type: image/png');
        break;
    default:
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
}

// Output the file
readfile($file_path);
exit;
?>
