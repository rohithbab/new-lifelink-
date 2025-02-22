DROP TABLE IF EXISTS organ_matches;

CREATE TABLE organ_matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(100) NOT NULL,
    donor_email VARCHAR(100) NOT NULL,
    recipient_name VARCHAR(100) NOT NULL,
    recipient_email VARCHAR(100) NOT NULL,
    hospital_name VARCHAR(100) NOT NULL,
    hospital_email VARCHAR(100) NOT NULL,
    organ_type VARCHAR(50) NOT NULL,
    match_date DATE NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Rejected') DEFAULT 'Pending',
    reason_for_match TEXT,
    admin_notes TEXT,
    donor_id_proof_path VARCHAR(255),
    recipient_medical_records_path VARCHAR(255),
    urgency_level ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_urgency (urgency_level),
    INDEX idx_match_date (match_date)
);
