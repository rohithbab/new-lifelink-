<?php
require_once 'config/db_connect.php';

try {
    // Update notifications without created_at to have current timestamp
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET created_at = CURRENT_TIMESTAMP 
        WHERE created_at IS NULL OR created_at = ''
    ");
    $result = $stmt->execute();
    echo "Successfully updated notifications dates. Affected rows: " . $stmt->rowCount();
} catch (Exception $e) {
    echo "Error updating notifications: " . $e->getMessage();
}
?>
