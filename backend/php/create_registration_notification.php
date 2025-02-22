<?php
require_once '../../config/db_connect.php';

function createRegistrationNotification($conn, $hospital_id, $type, $name, $blood_group, $organ_type, $related_id) {
    try {
        // Prepare the message based on type
        $message = '';
        $link_url = '';
        
        if ($type === 'donor_registration') {
            $message = "New donor $name ($blood_group) has registered for $organ_type donation";
            $link_url = "../pages/hospital/view_donor.php?id=" . $related_id;
        } else if ($type === 'recipient_registration') {
            $message = "New recipient $name ($blood_group) has registered needing $organ_type";
            $link_url = "../pages/hospital/view_recipient.php?id=" . $related_id;
        }

        // Insert notification
        $stmt = $conn->prepare("
            INSERT INTO hospital_notifications 
            (hospital_id, type, message, is_read, created_at, link_url, related_id)
            VALUES 
            (?, ?, ?, 0, NOW(), ?, ?)
        ");

        $stmt->execute([
            $hospital_id,
            $type,
            $message,
            $link_url,
            $related_id
        ]);

        return true;
    } catch (PDOException $e) {
        error_log("Error creating registration notification: " . $e->getMessage());
        return false;
    }
}
?>
