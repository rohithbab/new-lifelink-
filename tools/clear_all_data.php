<?php
require_once '../config/connection.php';

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Disable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');
    
    // Clear all tables in correct order
    $tables = ['donor', 'recipient_registration', 'hospitals'];
    
    foreach ($tables as $table) {
        // Delete all data
        $result = $conn->query("DELETE FROM $table");
        if (!$result) {
            throw new Exception("Error deleting from $table: " . $conn->error);
        }
        
        // Reset auto increment
        $result = $conn->query("ALTER TABLE $table AUTO_INCREMENT = 1");
        if (!$result) {
            throw new Exception("Error resetting auto increment for $table: " . $conn->error);
        }
        
        echo "Cleared table: $table\n";
    }
    
    // Re-enable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    
    // Commit transaction
    $conn->commit();
    
    echo "\nAll tables cleared successfully!";
    
} catch (Exception $e) {
    // If there's an error, roll back changes
    $conn->rollback();
    echo "Error: " . $e->getMessage();
} finally {
    // Always make sure foreign key checks are re-enabled
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
}
?>
