<?php
require_once '../config/connection.php';

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Disable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');
    
    // List of tables to drop
    $tables = [
        'donor_recipient_matches',
        'organ_matches',
        'messages',
        'notifications',
        'hospital_analytics',
        'hospital_specializations',
        'donors',
        'recipients'
    ];
    
    // Drop each table
    foreach ($tables as $table) {
        if ($conn->query("DROP TABLE IF EXISTS $table")) {
            echo "Successfully dropped table: $table\n";
        } else {
            throw new Exception("Error dropping table $table: " . $conn->error);
        }
    }
    
    // Re-enable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    
    // Commit transaction
    $conn->commit();
    
    echo "\nAll unwanted tables have been dropped successfully!";
    
} catch (Exception $e) {
    // If there's an error, roll back changes
    $conn->rollback();
    echo "Error: " . $e->getMessage();
} finally {
    // Always make sure foreign key checks are re-enabled
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
}
?>
