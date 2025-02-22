-- Add new columns first
ALTER TABLE hospitals
    ADD COLUMN IF NOT EXISTS license_file VARCHAR(255) AFTER license_number,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Modify existing columns
ALTER TABLE hospitals
    MODIFY hospital_id INT AUTO_INCREMENT,
    MODIFY name VARCHAR(255) NOT NULL,
    MODIFY email VARCHAR(255) NOT NULL UNIQUE,
    MODIFY phone VARCHAR(20) NOT NULL,
    MODIFY address TEXT NOT NULL,
    MODIFY region VARCHAR(100),
    MODIFY license_number VARCHAR(100) NOT NULL UNIQUE,
    MODIFY password VARCHAR(255) NOT NULL,
    MODIFY status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';
