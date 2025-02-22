-- Drop the existing table if it exists
DROP TABLE IF EXISTS donor;

-- Create the donor table with all required columns
CREATE TABLE donor (
    donor_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    dob DATE NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    medical_conditions TEXT,
    organs_to_donate TEXT NOT NULL,
    medical_reports_path VARCHAR(255),
    id_proof_path VARCHAR(255) NOT NULL,
    reason_for_donation TEXT,
    guardian_name VARCHAR(100) NOT NULL,
    guardian_email VARCHAR(100) NOT NULL,
    guardian_phone VARCHAR(15) NOT NULL,
    guardian_id_proof_path VARCHAR(255) NOT NULL,
    odml_id VARCHAR(20) UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
