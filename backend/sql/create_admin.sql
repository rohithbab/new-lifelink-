USE lifelink_db;

-- Create default admin account (password: admin123)
INSERT INTO admins (username, password, email) 
VALUES ('admin', '$2y$10$8tl8LXgNzX6mPRjGWNyJ8eXMeNPFm9j9XBhXq1k1xFk3yJztYwYwi', 'admin@lifelink.com');
