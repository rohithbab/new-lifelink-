<?php
require_once '../../config/db_connect.php';

try {
    // Create donor_notifications table
    $sql = "CREATE TABLE IF NOT EXISTS donor_notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        donor_id INT,
        type ENUM('request_status', 'match_found'),
        reference_id INT,
        message TEXT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (donor_id) REFERENCES donor(donor_id)
    )";
    
    $conn->exec($sql);
    echo "donor_notifications table created successfully\n";

    // Populate notifications from existing hospital_donor_approvals
    $stmt = $conn->prepare("
        SELECT ha.*, h.name as hospital_name 
        FROM hospital_donor_approvals ha
        JOIN hospitals h ON h.hospital_id = ha.hospital_id
        WHERE NOT EXISTS (
            SELECT 1 FROM donor_notifications dn 
            WHERE dn.reference_id = ha.approval_id 
            AND dn.type = 'request_status'
        )
    ");
    $stmt->execute();
    $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($approvals as $approval) {
        // Create message based on status
        switch ($approval['status']) {
            case 'pending':
                $message = "You have requested to donate {$approval['organ_type']} at {$approval['hospital_name']}.";
                break;
            case 'approved':
                $message = "Your request to donate {$approval['organ_type']} has been approved by {$approval['hospital_name']}.";
                break;
            case 'rejected':
                $reason = $approval['rejection_reason'] ? ". Reason: {$approval['rejection_reason']}" : "";
                $message = "Your request to donate {$approval['organ_type']} has been rejected by {$approval['hospital_name']}{$reason}";
                break;
            default:
                continue;
        }

        // Insert notification
        $stmt = $conn->prepare("
            INSERT INTO donor_notifications (donor_id, type, reference_id, message, created_at)
            VALUES (?, 'request_status', ?, ?, ?)
        ");
        $stmt->execute([
            $approval['donor_id'],
            $approval['approval_id'],
            $message,
            $approval['request_date']
        ]);
    }

    // Populate notifications from existing matches
    $stmt = $conn->prepare("
        SELECT * FROM made_matches_by_hospitals
        WHERE NOT EXISTS (
            SELECT 1 FROM donor_notifications dn 
            WHERE dn.reference_id = made_matches_by_hospitals.match_id 
            AND dn.type = 'match_found'
        )
    ");
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($matches as $match) {
        $message = "Great news! {$match['donor_hospital_name']} has found a potential recipient match for your {$match['organ_type']} donation. You will be contacted by the hospital for further details.";

        // Insert notification
        $stmt = $conn->prepare("
            INSERT INTO donor_notifications (donor_id, type, reference_id, message, created_at)
            VALUES (?, 'match_found', ?, ?, ?)
        ");
        $stmt->execute([
            $match['donor_id'],
            $match['match_id'],
            $message,
            $match['match_date']
        ]);
    }

    echo "Existing notifications populated successfully\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
