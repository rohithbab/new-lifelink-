<?php
require_once '../config/connection.php';

// Start transaction
$conn->begin_transaction();

try {
    // Disable foreign key checks temporarily
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');

    // Array of tables to clear
    $tables = [
        'donor_recipient_matches',
        'notifications',
        'hospital_analytics',
        'donors',
        'recipients',
        'hospitals'
    ];

    // Clear each table
    foreach ($tables as $table) {
        $sql = "TRUNCATE TABLE $table";
        if (!$conn->query($sql)) {
            throw new Exception("Error clearing table $table: " . $conn->error);
        }
        echo "Cleared table: $table\n";
    }

    // Re-enable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');

    // Commit transaction
    $conn->commit();
    echo "\nAll tables have been cleared successfully!\n";

} catch (Exception $e) {
    // If there's an error, roll back the transaction
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    // Always re-enable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
}

// Clear the uploads directory
$upload_dirs = [
    '../uploads/hospitals/license_file/',
    '../uploads/donors/medical_reports_path/',
    '../uploads/donors/id_proof_path/',
    '../uploads/donors/guardian_id_proof_path/',
    '../uploads/recipient_registration/recipient_medical_reports/',
    '../uploads/recipient_registration/id_document/'
];

foreach ($upload_dirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                echo "Deleted file: $file\n";
            }
        }
    }
}

echo "\nDatabase and upload directories have been cleared successfully!\n";
?>
