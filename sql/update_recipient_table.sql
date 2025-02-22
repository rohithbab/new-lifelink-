-- Add recipient_medical_reports column to recipient_registration table
ALTER TABLE recipient_registration 
ADD COLUMN recipient_medical_reports VARCHAR(255) 
COMMENT 'Path to recipient medical report files (images/pdfs/docs, max 5MB)';
