-- Insert sample request status notifications
INSERT INTO recipient_notifications (recipient_id, type, reference_id, message, is_read, created_at) VALUES
(1, 'request_status', 1, 'You have requested for Kidney at City Hospital. Your request is pending approval.', 0, NOW()),
(1, 'request_status', 2, 'Great news! Your request for Heart has been approved by General Hospital.', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'request_status', 3, 'Your request for Liver has been rejected by Medical Center. Reason: Medical incompatibility.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Insert sample match found notifications
INSERT INTO recipient_notifications (recipient_id, type, reference_id, message, is_read, created_at) VALUES
(1, 'match_found', 1, 'Great news! A potential donor has been found for your Kidney requirement. This match has been made by City Hospital. You will be contacted by the hospital for further details.', 0, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 'match_found', 2, 'Great news! A potential donor has been found for your Heart requirement. This match has been made by General Hospital. You will be contacted by the hospital for further details.', 1, DATE_SUB(NOW(), INTERVAL 3 DAY));
