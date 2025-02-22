<?php
require_once '../../config/db_connect.php';

try {
    // Start transaction
    $conn->beginTransaction();

    // Sync request status notifications
    $stmt = $conn->prepare("
        INSERT INTO recipient_notifications (recipient_id, type, reference_id, message, created_at)
        SELECT 
            recipient_id,
            'request_status' as type,
            approval_id as reference_id,
            CASE 
                WHEN status = 'pending' THEN CONCAT('You have requested for ', required_organ, ' at ', 
                    (SELECT h.name FROM hospitals h WHERE h.hospital_id = hra.hospital_id), 
                    '. Your request is pending approval.')
                WHEN status = 'approved' THEN CONCAT('Great news! Your request for ', required_organ, ' has been approved by ',
                    (SELECT h.name FROM hospitals h WHERE h.hospital_id = hra.hospital_id))
                WHEN status = 'rejected' THEN CONCAT('Your request for ', required_organ, ' has been rejected by ',
                    (SELECT h.name FROM hospitals h WHERE h.hospital_id = hra.hospital_id),
                    CASE 
                        WHEN rejection_reason IS NOT NULL AND rejection_reason != '' 
                        THEN CONCAT('. Reason: ', rejection_reason)
                        ELSE ''
                    END)
            END as message,
            CASE 
                WHEN status = 'pending' THEN request_date
                ELSE approval_date
            END as created_at
        FROM hospital_recipient_approvals hra
        WHERE NOT EXISTS (
            SELECT 1 
            FROM recipient_notifications rn 
            WHERE rn.reference_id = hra.approval_id 
            AND rn.type = 'request_status'
        )
    ");
    $stmt->execute();

    // Sync match notifications
    $stmt = $conn->prepare("
        INSERT INTO recipient_notifications (recipient_id, type, reference_id, message, created_at)
        SELECT 
            recipient_id,
            'match_found' as type,
            match_id as reference_id,
            CONCAT('Great news! A potential donor has been found for your ', organ_type, ' requirement. ',
                   'This match has been made by ', recipient_hospital_name, '. ',
                   'You will be contacted by the hospital via email and call for further meetings.') as message,
            match_date as created_at
        FROM made_matches_by_hospitals mmh
        WHERE NOT EXISTS (
            SELECT 1 
            FROM recipient_notifications rn 
            WHERE rn.reference_id = mmh.match_id 
            AND rn.type = 'match_found'
        )
    ");
    $stmt->execute();

    // Commit transaction
    $conn->commit();

} catch(PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    error_log("Error syncing notifications: " . $e->getMessage());
}
?>
