<?php
require_once '../config/connection.php';

echo "=== File Check Report ===\n\n";

// Get all donors
$query = "SELECT donor_id, name, id_proof_path, guardian_id_proof_path FROM donor";
$result = $conn->query($query);

echo "Found " . $result->num_rows . " donors in database\n\n";

$base_path = __DIR__ . '/../uploads/donors/';
$id_proof_dir = $base_path . 'id_proof_path/';
$guardian_proof_dir = $base_path . 'guardian_id_proof_path/';

// Get all files in directories
$id_proof_files = array_diff(scandir($id_proof_dir), array('.', '..'));
$guardian_proof_files = array_diff(scandir($guardian_proof_dir), array('.', '..'));

echo "Found " . count($id_proof_files) . " files in id_proof_path directory\n";
echo "Found " . count($guardian_proof_files) . " files in guardian_id_proof_path directory\n\n";

$missing_files = [];
$misplaced_files = [];

while ($donor = $result->fetch_assoc()) {
    echo "\nChecking donor #{$donor['donor_id']} - {$donor['name']}\n";
    
    // Check ID proof
    if (!empty($donor['id_proof_path'])) {
        $expected_path = $id_proof_dir . $donor['id_proof_path'];
        if (!file_exists($expected_path)) {
            echo "❌ ID proof missing: {$donor['id_proof_path']}\n";
            $missing_files[] = [
                'donor_id' => $donor['donor_id'],
                'type' => 'id_proof',
                'filename' => $donor['id_proof_path']
            ];
        } else {
            echo "✓ ID proof found\n";
        }
    }
    
    // Check Guardian ID proof
    if (!empty($donor['guardian_id_proof_path'])) {
        $expected_path = $guardian_proof_dir . $donor['guardian_id_proof_path'];
        if (!file_exists($expected_path)) {
            echo "❌ Guardian ID proof missing: {$donor['guardian_id_proof_path']}\n";
            $missing_files[] = [
                'donor_id' => $donor['donor_id'],
                'type' => 'guardian_id_proof',
                'filename' => $donor['guardian_id_proof_path']
            ];
        } else {
            echo "✓ Guardian ID proof found\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo count($missing_files) . " missing files found\n";

if (!empty($missing_files)) {
    echo "\nMissing Files:\n";
    foreach ($missing_files as $file) {
        echo "- Donor #{$file['donor_id']}: {$file['type']} - {$file['filename']}\n";
    }
}
