-- Drop existing indexes if they exist
DROP INDEX IF EXISTS idx_email ON recipients;
DROP INDEX IF EXISTS idx_username ON recipients;

-- Drop unnecessary columns
ALTER TABLE recipients
    DROP COLUMN IF EXISTS name,
    DROP COLUMN IF EXISTS dob,
    DROP COLUMN IF EXISTS blood_group,
    DROP COLUMN IF EXISTS medical_records_path,
    DROP COLUMN IF EXISTS id_type,
    DROP COLUMN IF EXISTS id_proof_path,
    DROP COLUMN IF EXISTS policy_agreement,
    DROP COLUMN IF EXISTS medical_records_consent,
    DROP COLUMN IF EXISTS terms_agreement,
    DROP COLUMN IF EXISTS status,
    DROP COLUMN IF EXISTS created_at,
    DROP COLUMN IF EXISTS updated_at;

-- Add columns if they don't exist
ALTER TABLE recipients
    ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL,
    ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other') NULL,
    ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS address TEXT NULL,
    ADD COLUMN IF NOT EXISTS medical_condition TEXT NULL,
    ADD COLUMN IF NOT EXISTS blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    ADD COLUMN IF NOT EXISTS organ_required ENUM('kidney', 'liver', 'heart', 'lungs', 'pancreas', 'corneas') NULL,
    ADD COLUMN IF NOT EXISTS organ_reason TEXT NULL,
    ADD COLUMN IF NOT EXISTS id_proof_type VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS id_proof_number VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS id_document VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS username VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS password VARCHAR(255) NULL;

-- Now modify columns to add NOT NULL constraint
ALTER TABLE recipients
    MODIFY COLUMN full_name VARCHAR(255) NOT NULL,
    MODIFY COLUMN date_of_birth DATE NOT NULL,
    MODIFY COLUMN gender ENUM('male', 'female', 'other') NOT NULL,
    MODIFY COLUMN phone_number VARCHAR(20) NOT NULL,
    MODIFY COLUMN email VARCHAR(255) NOT NULL,
    MODIFY COLUMN address TEXT NOT NULL,
    MODIFY COLUMN medical_condition TEXT NOT NULL,
    MODIFY COLUMN blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    MODIFY COLUMN organ_required ENUM('kidney', 'liver', 'heart', 'lungs', 'pancreas', 'corneas') NOT NULL,
    MODIFY COLUMN organ_reason TEXT NOT NULL,
    MODIFY COLUMN id_proof_type VARCHAR(50) NOT NULL,
    MODIFY COLUMN id_proof_number VARCHAR(50) NOT NULL,
    MODIFY COLUMN id_document VARCHAR(255) NOT NULL,
    MODIFY COLUMN username VARCHAR(50) NOT NULL,
    MODIFY COLUMN password VARCHAR(255) NOT NULL;

-- Add new columns
ALTER TABLE recipients
    ADD COLUMN IF NOT EXISTS ODML_ID VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS request_status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending';

-- Add unique constraints
ALTER TABLE recipients 
    ADD UNIQUE INDEX idx_email (email),
    ADD UNIQUE INDEX idx_username (username);
