USE lifelink_db;

-- Update admin credentials (password: me123)
UPDATE admins 
SET email = 'me@lifelink.com',
    password = '$2y$10$YourNewHashedPasswordHere'
WHERE username = 'admin';
