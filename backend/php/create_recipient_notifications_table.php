<?php
require_once '../../config/db_connect.php';

try {
    // Read the SQL file
    $sql = file_get_contents('../../database/recipient_notifications.sql');

    // Execute the SQL commands
    $conn->exec($sql);
    echo "Recipient notifications table created and populated successfully!";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
