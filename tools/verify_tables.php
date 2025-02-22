-- First disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear donor table
DELETE FROM donor;
ALTER TABLE donor AUTO_INCREMENT = 1;

-- Clear recipient_registration table
DELETE FROM recipient_registration;
ALTER TABLE recipient_registration AUTO_INCREMENT = 1;

-- Clear hospitals table
DELETE FROM hospitals;
ALTER TABLE hospitals AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;