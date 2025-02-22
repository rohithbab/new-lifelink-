-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('donor', 'hospital', 'admin') NOT NULL,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    read_status TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_type, user_id),
    INDEX idx_read_status (read_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pending Donor Requests table
CREATE TABLE IF NOT EXISTS donor_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id INT NOT NULL,
    hospital_id INT NOT NULL,
    organ_type VARCHAR(50) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    FOREIGN KEY (donor_id) REFERENCES donors(id),
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id),
    INDEX idx_status (status),
    INDEX idx_donor (donor_id),
    INDEX idx_hospital (hospital_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Approved Donors Management table
CREATE TABLE IF NOT EXISTS approved_donors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    donor_id INT NOT NULL,
    hospital_id INT NOT NULL,
    organ_type VARCHAR(50) NOT NULL,
    approval_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    notes TEXT,
    FOREIGN KEY (request_id) REFERENCES donor_requests(id),
    FOREIGN KEY (donor_id) REFERENCES donors(id),
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id),
    INDEX idx_status (status),
    INDEX idx_donor (donor_id),
    INDEX idx_hospital (hospital_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
