<?php
require_once 'config/db_connect.php';

try {
    // Create the donor_notifications table
    $sql = "CREATE TABLE IF NOT EXISTS donor_notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        donor_id INT,
        type ENUM('request_status', 'match_found'),
        reference_id INT,
        message TEXT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (donor_id) REFERENCES donor(donor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $conn->exec($sql);
    echo "donor_notifications table created successfully!<br>";

    // Now let's populate some test data
    $sql = "INSERT INTO donor_notifications (donor_id, type, reference_id, message, created_at)
            SELECT 
                ha.donor_id,
                'request_status',
                ha.approval_id,
                CASE 
                    WHEN ha.status = 'pending' THEN CONCAT('You have requested to donate ', ha.organ_type, ' at ', h.name)
                    WHEN ha.status = 'approved' THEN CONCAT('Your request to donate ', ha.organ_type, ' has been approved by ', h.name)
                    WHEN ha.status = 'rejected' THEN CONCAT('Your request to donate ', ha.organ_type, ' has been rejected by ', h.name, 
                        CASE WHEN ha.rejection_reason IS NOT NULL THEN CONCAT('. Reason: ', ha.rejection_reason) ELSE '' END)
                END,
                ha.request_date
            FROM hospital_donor_approvals ha
            JOIN hospitals h ON h.hospital_id = ha.hospital_id
            WHERE NOT EXISTS (
                SELECT 1 FROM donor_notifications dn 
                WHERE dn.reference_id = ha.approval_id 
                AND dn.type = 'request_status'
            )";

    $conn->exec($sql);
    echo "Request notifications added successfully!<br>";

    // Add match notifications
    $sql = "INSERT INTO donor_notifications (donor_id, type, reference_id, message, created_at)
            SELECT 
                donor_id,
                'match_found',
                match_id,
                CONCAT('Great news! ', donor_hospital_name, ' has found a potential recipient match for your ', organ_type, ' donation. You will be contacted by the hospital for further details.'),
                match_date
            FROM made_matches_by_hospitals
            WHERE NOT EXISTS (
                SELECT 1 FROM donor_notifications dn 
                WHERE dn.reference_id = made_matches_by_hospitals.match_id 
                AND dn.type = 'match_found'
            )";

    $conn->exec($sql);
    echo "Match notifications added successfully!<br>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
