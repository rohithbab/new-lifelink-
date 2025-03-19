<?php
require_once 'config/db_connect.php';

try {
    // Update all read notifications to be deletable
    $stmt = $conn->prepare("UPDATE notifications SET can_delete = 1 WHERE is_read = 1");
    $result = $stmt->execute();
    echo "Successfully updated notifications. Affected rows: " . $stmt->rowCount();
} catch (Exception $e) {
    echo "Error updating notifications: " . $e->getMessage();
}
?>
