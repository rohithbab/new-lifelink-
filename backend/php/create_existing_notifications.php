<?php
require_once '../../config/db_connect.php';

try {
    $conn->beginTransaction();

    // Get all hospitals
    $stmt = $conn->query("SELECT hospital_id FROM hospitals");
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($hospitals as $hospital) {
        $hospital_id = $hospital['hospital_id'];

        // Get existing donor approvals for this hospital
        $stmt = $conn->prepare("
            SELECT hda.*, h.name as hospital_name, d.name as donor_name, d.blood_group, d.organs_to_donate
            FROM hospital_donor_approvals hda
            JOIN hospitals h ON h.hospital_id = hda.hospital_id
            JOIN donor d ON d.donor_id = hda.donor_id
            WHERE hda.hospital_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM hospital_notifications n 
                WHERE n.hospital_id = ? 
                AND n.type = 'donor_registration' 
                AND n.related_id = hda.donor_id
            )
        ");
        $stmt->execute([$hospital_id, $hospital_id]);
        $donor_approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Create notifications for donor approvals
        foreach ($donor_approvals as $approval) {
            $message = "New donor {$approval['donor_name']} (Blood Group: {$approval['blood_group']}) has registered to donate {$approval['organs_to_donate']}";
            $stmt = $conn->prepare("
                INSERT INTO hospital_notifications 
                (hospital_id, type, message, is_read, created_at, link_url, related_id)
                VALUES 
                (?, 'donor_registration', ?, 0, NOW(), ?, ?)
            ");
            $stmt->execute([
                $hospital_id,
                $message,
                "hospitals_handles_donors_status.php",
                $approval['donor_id']
            ]);
        }

        // Get existing recipient approvals for this hospital
        $stmt = $conn->prepare("
            SELECT hra.*, h.name as hospital_name, r.full_name as recipient_name, r.blood_type, r.organ_required
            FROM hospital_recipient_approvals hra
            JOIN hospitals h ON h.hospital_id = hra.hospital_id
            JOIN recipient_registration r ON r.id = hra.recipient_id
            WHERE hra.hospital_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM hospital_notifications n 
                WHERE n.hospital_id = ? 
                AND n.type = 'recipient_registration' 
                AND n.related_id = hra.recipient_id
            )
        ");
        $stmt->execute([$hospital_id, $hospital_id]);
        $recipient_approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Create notifications for recipient approvals
        foreach ($recipient_approvals as $approval) {
            $message = "New recipient {$approval['recipient_name']} (Blood Type: {$approval['blood_type']}) needs {$approval['organ_required']} transplant";
            $stmt = $conn->prepare("
                INSERT INTO hospital_notifications 
                (hospital_id, type, message, is_read, created_at, link_url, related_id)
                VALUES 
                (?, 'recipient_registration', ?, 0, NOW(), ?, ?)
            ");
            $stmt->execute([
                $hospital_id,
                $message,
                "hospitals_handles_recipients_status.php",
                $approval['recipient_id']
            ]);
        }
    }

    $conn->commit();
    echo "Successfully created notifications for existing registrations";
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Error creating existing notifications: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?>
