-- Drop table if exists
DROP TABLE IF EXISTS recipient_registration;

-- Create recipient_registration table
CREATE TABLE recipient_registration (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    medical_condition TEXT NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    organ_required ENUM('kidney', 'liver', 'heart', 'lungs', 'pancreas', 'corneas') NOT NULL,
    organ_reason TEXT NOT NULL,
    id_proof_type VARCHAR(50) NOT NULL,
    id_proof_number VARCHAR(50) NOT NULL,
    id_document VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    ODML_ID VARCHAR(50) NULL,
    request_status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    UNIQUE KEY unique_email (email),
    UNIQUE KEY unique_username (username)
);
