<?php
require_once __DIR__ . '/../backend/php/connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up directory structure
$base_upload_dir = __DIR__ . '/../uploads/';
$old_upload_dirs = [
    'licenses' => __DIR__ . '/../uploads/licenses/',
    'medical_reports' => __DIR__ . '/../uploads/medical_reports/',
    'id_proofs' => __DIR__ . '/../uploads/id_proofs/',
    'guardian_proofs' => __DIR__ . '/../uploads/guardian_proofs/',
    'recipient_docs' => __DIR__ . '/../uploads/recipient_docs/'
];

// Directory structure for new organization
$directories = [
    // Hospital directories
    $base_upload_dir . 'hospitals/license_file',
    
    // Donor directories
    $base_upload_dir . 'donors/medical_reports_path',
    $base_upload_dir . 'donors/id_proof_path',
    $base_upload_dir . 'donors/guardian_id_proof_path',
    
    // Recipient directories
    $base_upload_dir . 'recipient_registration/recipient_medical_reports',
    $base_upload_dir . 'recipient_registration/id_document'
];

echo "Starting file organization...\n";

// Create required directories
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created directory: $dir\n";
            chmod($dir, 0777);
        } else {
            echo "Failed to create directory: $dir\n";
        }
    } else {
        chmod($dir, 0777);
    }
}

// Function to recursively search for a file
function find_file($filename, $search_path) {
    if (!is_dir($search_path)) {
        return null;
    }
    
    $files = glob($search_path . '/*' . $filename);
    if (!empty($files)) {
        return $files[0];
    }
    
    $dirs = glob($search_path . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $found = find_file($filename, $dir);
        if ($found) {
            return $found;
        }
    }
    return null;
}

// Function to safely move a file
function safe_move_file($source, $destination) {
    if (!file_exists($source)) {
        return false;
    }
    
    $dir = dirname($destination);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    if (copy($source, $destination)) {
        chmod($destination, 0666);
        return true;
    }
    return false;
}

// Process Hospital Files
$query = "SELECT hospital_id, name, license_file FROM hospitals WHERE license_file IS NOT NULL";
try {
    $stmt = $conn->query($query);
    echo "\nProcessing hospital licenses...\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['license_file'])) {
            $source = find_file($row['license_file'], __DIR__ . '/..');
            $target_path = $base_upload_dir . 'hospitals/license_file/' . $row['license_file'];
            
            if ($source && safe_move_file($source, $target_path)) {
                echo "✓ Hospital #{$row['hospital_id']} - License file organized\n";
            } else {
                echo "❌ Hospital #{$row['hospital_id']} - License file not found: {$row['license_file']}\n";
            }
        }
    }
} catch (PDOException $e) {
    echo "Database error (hospitals): " . $e->getMessage() . "\n";
}

// Process Donor Files
$query = "SELECT donor_id, name, medical_reports_path, id_proof_path, guardian_id_proof_path FROM donor";
try {
    $stmt = $conn->query($query);
    echo "\nProcessing donor files...\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Processing donor #{$row['donor_id']} - {$row['name']}\n";
        
        // Process medical reports
        if (!empty($row['medical_reports_path'])) {
            $source = find_file($row['medical_reports_path'], __DIR__ . '/..');
            $target_path = $base_upload_dir . 'donors/medical_reports_path/' . $row['medical_reports_path'];
            
            if ($source && safe_move_file($source, $target_path)) {
                echo "✓ Medical reports organized\n";
            } else {
                echo "❌ Medical reports not found: {$row['medical_reports_path']}\n";
            }
        }
        
        // Process ID proof
        if (!empty($row['id_proof_path'])) {
            $source = find_file($row['id_proof_path'], __DIR__ . '/..');
            $target_path = $base_upload_dir . 'donors/id_proof_path/' . $row['id_proof_path'];
            
            if ($source && safe_move_file($source, $target_path)) {
                echo "✓ ID proof organized\n";
            } else {
                echo "❌ ID proof not found: {$row['id_proof_path']}\n";
            }
        }
        
        // Process guardian ID proof
        if (!empty($row['guardian_id_proof_path'])) {
            $source = find_file($row['guardian_id_proof_path'], __DIR__ . '/..');
            $target_path = $base_upload_dir . 'donors/guardian_id_proof_path/' . $row['guardian_id_proof_path'];
            
            if ($source && safe_move_file($source, $target_path)) {
                echo "✓ Guardian ID proof organized\n";
            } else {
                echo "❌ Guardian ID proof not found: {$row['guardian_id_proof_path']}\n";
            }
        }
    }
} catch (PDOException $e) {
    echo "Database error (donors): " . $e->getMessage() . "\n";
}

// Process Recipient Files
$query = "SELECT id, full_name, recipient_medical_reports, id_document FROM recipient_registration";
try {
    $stmt = $conn->query($query);
    echo "\nProcessing recipient files...\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Processing recipient #{$row['id']} - {$row['full_name']}\n";
        
        // Process medical reports
        if (!empty($row['recipient_medical_reports'])) {
            $source = find_file($row['recipient_medical_reports'], __DIR__ . '/..');
            $target_path = $base_upload_dir . 'recipient_registration/recipient_medical_reports/' . $row['recipient_medical_reports'];
            
            if ($source && safe_move_file($source, $target_path)) {
                echo "✓ Medical reports organized\n";
            } else {
                echo "❌ Medical reports not found: {$row['recipient_medical_reports']}\n";
            }
        }
        
        // Process ID document
        if (!empty($row['id_document'])) {
            $source = find_file($row['id_document'], __DIR__ . '/..');
            $target_path = $base_upload_dir . 'recipient_registration/id_document/' . $row['id_document'];
            
            if ($source && safe_move_file($source, $target_path)) {
                echo "✓ ID document organized\n";
            } else {
                echo "❌ ID document not found: {$row['id_document']}\n";
            }
        }
    }
} catch (PDOException $e) {
    echo "Database error (recipients): " . $e->getMessage() . "\n";
}

echo "\nDone processing files.\n";
