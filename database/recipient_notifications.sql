-- Create recipient notifications table
CREATE TABLE recipient_notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_id INT,
    type ENUM('request_status', 'match_found'),
    reference_id INT, -- stores approval_id or match_id based on type
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE, -- true for read notifications
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES recipient_registration(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert notifications for existing requests (pending, approved, rejected)
INSERT INTO recipient_notifications (recipient_id, type, reference_id, message, created_at)
SELECT 
    recipient_id,
    'request_status',
    approval_id,
    CASE 
        WHEN status = 'pending' THEN CONCAT('You have requested for ', required_organ, ' at ', 
            (SELECT name FROM hospitals WHERE hospital_id = hospital_recipient_approvals.hospital_id))
        WHEN status = 'approved' THEN CONCAT('Your request for ', required_organ, ' has been approved by ',
            (SELECT name FROM hospitals WHERE hospital_id = hospital_recipient_approvals.hospital_id))
        WHEN status = 'rejected' THEN CONCAT('Your request for ', required_organ, ' has been rejected by ',
            (SELECT name FROM hospitals WHERE hospital_id = hospital_recipient_approvals.hospital_id),
            CASE WHEN rejection_reason IS NOT NULL THEN CONCAT('. Reason: ', rejection_reason) ELSE '' END)
    END,
    CASE 
        WHEN status = 'pending' THEN request_date
        WHEN status IN ('approved', 'rejected') THEN approval_date
    END
FROM hospital_recipient_approvals
WHERE NOT EXISTS (
    SELECT 1 FROM recipient_notifications 
    WHERE reference_id = hospital_recipient_approvals.approval_id 
    AND type = 'request_status'
);

-- Insert notifications for existing matches
INSERT INTO recipient_notifications (recipient_id, type, reference_id, message, created_at)
SELECT 
    recipient_id,
    'match_found',
    match_id,
    CONCAT('Great news! A potential donor has been found for your ', organ_type, ' requirement. This match has been made by ', 
           recipient_hospital_name, '. You will be contacted by the hospital for further details.'),
    match_date
FROM made_matches_by_hospitals
WHERE NOT EXISTS (
    SELECT 1 FROM recipient_notifications 
    WHERE reference_id = made_matches_by_hospitals.match_id 
    AND type = 'match_found'
);
