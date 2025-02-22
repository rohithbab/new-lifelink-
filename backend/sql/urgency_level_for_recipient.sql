-- SQL command to add urgency_level column to recipient_registration table
ALTER TABLE recipient_registration 
ADD COLUMN urgency_level ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Low' 
COMMENT 'Low: >6 months, Medium: 3-6 months, High: <3 months';

-- Optional: Update existing records to have a default value
UPDATE recipient_registration SET urgency_level = 'Low' WHERE urgency_level IS NULL;
