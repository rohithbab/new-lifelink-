-- First, check if ODML_ID column exists
SET @dbname = 'lifelink_db';
SET @tablename = 'recipient_registration';
SET @columnname = 'odml_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' VARCHAR(50) DEFAULT NULL, ADD UNIQUE INDEX (', @columnname, ')')
));

-- Execute the prepared statement
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Make sure the column is properly configured
ALTER TABLE recipient_registration MODIFY COLUMN odml_id VARCHAR(50) DEFAULT NULL;
ALTER TABLE recipient_registration ADD UNIQUE INDEX IF NOT EXISTS (odml_id);
