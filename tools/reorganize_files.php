<?php
require_once '../config/connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$base_dir = __DIR__ . '/../uploads/';
$donors_dir = $base_dir . 'donors/';

// Directory structure
$directories = [
    $donors_dir . 'medical_reports_path',
    $donors_dir . 'id_proof_path',
    $donors_dir . 'guardian_id_proof_path'
];

echo "Starting file organization...\n";

// Create required directories
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created directory: $dir\n";
            // Ensure directory has correct permissions
            chmod($dir, 0777);
        } else {
            echo "Failed to create directory: $dir\n";
        }
    } else {
        // Ensure existing directory has correct permissions
        chmod($dir, 0777);
    }
}

// Function to safely move a file
function safe_move_file($source, $destination) {
    // If file is already at destination, just ensure permissions
    if ($source === $destination && file_exists($destination)) {
        chmod($destination, 0666); // Set file permissions
        echo "File already in correct location: " . basename($destination) . "\n";
        return true;
    }

    if (!file_exists($source)) {
        echo "Source file not found: $source\n";
        return false;
    }

    $destination_dir = dirname($destination);
    if (!file_exists($destination_dir)) {
        mkdir($destination_dir, 0777, true);
    }

    if (copy($source, $destination)) {
        chmod($destination, 0666); // Set file permissions
        echo "Successfully moved: " . basename($source) . " to " . basename(dirname($destination)) . "\n";
        // Only delete source if it's different from destination
        if ($source !== $destination && file_exists($destination) && filesize($destination) === filesize($source)) {
            unlink($source);
        }
        return true;
    } else {
        echo "Failed to move: $source\n";
        return false;
    }
}

// Function to find a file in any subdirectory
function find_file($filename, $search_path) {
    // First check if file exists in expected location
    foreach (['medical_reports_path', 'id_proof_path', 'guardian_id_proof_path'] as $subdir) {
        $expected_path = $search_path . 'donors/' . $subdir . '/' . $filename;
        if (file_exists($expected_path)) {
            return $expected_path;
        }
    }

    // If not found in expected location, search all directories
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($search_path, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getFilename() === $filename) {
            return $file->getPathname();
        }
    }
    return null;
}

// Get all donor records
$query = "SELECT donor_id, name, medical_reports_path, id_proof_path, guardian_id_proof_path FROM donor";
$result = $conn->query($query);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

echo "\nFound " . $result->num_rows . " donor records\n\n";

// Process each donor
while ($donor = $result->fetch_assoc()) {
    echo "\nProcessing donor #{$donor['donor_id']} - {$donor['name']}\n";
    
    // Process ID proof
    if (!empty($donor['id_proof_path'])) {
        $filename = $donor['id_proof_path'];
        $source = find_file($filename, $base_dir);
        $destination = $donors_dir . 'id_proof_path/' . $filename;
        
        if ($source) {
            safe_move_file($source, $destination);
        } else {
            echo "⚠️ ID proof file not found: $filename\n";
        }
    }
    
    // Process Guardian ID proof
    if (!empty($donor['guardian_id_proof_path'])) {
        $filename = $donor['guardian_id_proof_path'];
        $source = find_file($filename, $base_dir);
        $destination = $donors_dir . 'guardian_id_proof_path/' . $filename;
        
        if ($source) {
            safe_move_file($source, $destination);
        } else {
            echo "⚠️ Guardian ID proof file not found: $filename\n";
        }
    }
    
    // Process Medical Reports
    if (!empty($donor['medical_reports_path'])) {
        $reports = explode(',', $donor['medical_reports_path']);
        foreach ($reports as $report) {
            if (empty($report)) continue;
            
            $source = find_file($report, $base_dir);
            $destination = $donors_dir . 'medical_reports_path/' . $report;
            
            if ($source) {
                safe_move_file($source, $destination);
            } else {
                echo "⚠️ Medical report file not found: $report\n";
            }
        }
    }
}

echo "\nFile reorganization completed!\n";
echo "\nVerifying final structure...\n";

// Verify final structure
foreach ($directories as $dir) {
    $files = glob("$dir/*");
    $count = count($files);
    echo "\nFiles in " . basename($dir) . ": $count\n";
    
    // Set permissions for all files
    foreach ($files as $file) {
        chmod($file, 0666);
    }
}

echo "\nDone! All files have been organized and permissions have been set.\n";
