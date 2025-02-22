<?php
require_once '../../config/db_connect.php';

try {
    // Read and execute the SQL file
    $sql = file_get_contents('../../database/populate_recipient_notifications.sql');
    
    // Execute the SQL
    $conn->exec($sql);
    echo "Sample notifications added successfully!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
