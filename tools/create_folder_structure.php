<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base upload directory
$base_dir = __DIR__ . '/../uploads';

// Define the folder structure
$folders = [
    // Hospitals structure
    'hospitals/license_file',
    
    // Donors structure
    'donors/medical_reports_path',
    'donors/id_proof_path',
    'donors/guardian_id_proof_path',
    
    // Recipient structure
    'recipient_registration/recipient_medical_reports',
    'recipient_registration/id_document'
];

echo "Creating folder structure...\n\n";

// Create each directory with proper permissions
foreach ($folders as $folder) {
    $full_path = $base_dir . '/' . $folder;
    
    if (!file_exists($full_path)) {
        if (mkdir($full_path, 0777, true)) {
            echo "✓ Created: $folder\n";
            // Ensure directory has correct permissions
            chmod($full_path, 0777);
        } else {
            echo "❌ Failed to create: $folder\n";
        }
    } else {
        echo "• Already exists: $folder\n";
        // Ensure existing directory has correct permissions
        chmod($full_path, 0777);
    }
}

echo "\nFolder structure creation complete!\n";
