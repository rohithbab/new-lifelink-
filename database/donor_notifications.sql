-- Create donor_notifications table
CREATE TABLE IF NOT EXISTS donor_notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id INT,
    type ENUM('request_status', 'match_found'),
    reference_id INT,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donor(donor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data (optional, for testing)
INSERT INTO donor_notifications (donor_id, type, reference_id, message, created_at)
VALUES 
(1, 'request_status', 1, 'You have requested to donate kidney at City Hospital.', '2025-01-10 08:00:00'),
(1, 'match_found', 1, 'Great news! City Hospital has found a potential recipient match for your kidney donation. You will be contacted by the hospital for further details.', '2025-01-10 09:00:00');
