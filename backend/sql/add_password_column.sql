-- Add password column to donor table
ALTER TABLE donor
ADD COLUMN password VARCHAR(255) NOT NULL;
